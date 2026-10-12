<?php

namespace App\Services;

use App\Interface\CompanyInfoInterface;
use App\Models\CompanyInfo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CompanyInfoService implements CompanyInfoInterface
{
    /** Cache::remember() key for the active company record (see getActiveCompanyInfo()). */
    private const ACTIVE_CACHE_KEY = 'company_info.active';

    private const ACTIVE_CACHE_TTL_MINUTES = 60;

    public function getAllCompanyInfo(): array
    {
        return CompanyInfo::query()
            ->orderByDesc('id')
            ->get()
            ->map(fn (CompanyInfo $company) => $this->formatCompanyInfo($company))
            ->values()
            ->toArray();
    }

    public function getCompanyInfoById(int $id): array
    {
        return $this->formatCompanyInfo(CompanyInfo::findOrFail($id));
    }

    public function createCompanyInfo(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $logoPath = null;

            try {
                if (isset($data['logo']) && $data['logo'] instanceof UploadedFile) {
                    $logoPath = $data['logo']->store('company-info/logo', 'public');
                }

                $isActive = (bool) ($data['is_active'] ?? true);
                if ($isActive) {
                    $this->deactivateAllExcept(null);
                }

                $company = CompanyInfo::create([
                    'company_name' => $data['company_name'],
                    'company_address' => $data['company_address'] ?? null,
                    'company_pan_no' => $data['company_pan_no'] ?? null,
                    'company_phone_no' => $data['company_phone_no'] ?? null,
                    'company_mobile_no' => $data['company_mobile_no'] ?? null,
                    'company_email' => $data['company_email'] ?? null,
                    'company_website' => $data['company_website'] ?? null,
                    'logo' => $logoPath,
                    'is_active' => $isActive,
                    'created_by' => Auth::id(),
                ]);

                $this->forgetActiveCache();

                return $this->getCompanyInfoById($company->id);
            } catch (\Throwable $e) {
                if ($logoPath && Storage::disk('public')->exists($logoPath)) {
                    Storage::disk('public')->delete($logoPath);
                }

                throw $e;
            }
        });
    }

    public function updateCompanyInfo(int $id, array $data): array
    {
        return DB::transaction(function () use ($id, $data) {
            $company = CompanyInfo::findOrFail($id);
            $update = [];

            foreach (['company_name', 'company_address', 'company_pan_no', 'company_phone_no', 'company_mobile_no', 'company_email', 'company_website'] as $field) {
                if (array_key_exists($field, $data)) {
                    $update[$field] = $data[$field];
                }
            }

            if (isset($data['logo']) && $data['logo'] instanceof UploadedFile) {
                if ($company->logo && Storage::disk('public')->exists($company->logo)) {
                    Storage::disk('public')->delete($company->logo);
                }
                $update['logo'] = $data['logo']->store('company-info/logo', 'public');
            }

            if (array_key_exists('is_active', $data)) {
                $isActive = (bool) $data['is_active'];
                if ($isActive) {
                    $this->deactivateAllExcept($company->id);
                }
                $update['is_active'] = $isActive;
            }

            $company->update($update);
            $this->forgetActiveCache();

            return $this->getCompanyInfoById($company->id);
        });
    }

    public function deleteCompanyInfo(int $id): bool
    {
        // Soft toggle, same convention as Events — never hard-deletes a record
        // that invoices/other pages may already have cached a logo URL for.
        $company = CompanyInfo::findOrFail($id);
        $result = $company->update(['is_active' => false]);
        $this->forgetActiveCache();

        return $result;
    }

    public function getActiveCompanyInfo(): ?array
    {
        return Cache::remember(self::ACTIVE_CACHE_KEY, now()->addMinutes(self::ACTIVE_CACHE_TTL_MINUTES), function () {
            $company = CompanyInfo::where('is_active', true)->latest('id')->first();

            return $company ? $this->formatCompanyInfo($company) : null;
        });
    }

    /**
     * Only one row is ever "the" active company (the one the shared prop,
     * invoice headers, print pages, etc. all read) — enforced here rather
     * than with a DB constraint so create/update can stay simple transactions.
     */
    private function deactivateAllExcept(?int $exceptId): void
    {
        CompanyInfo::where('is_active', true)
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->update(['is_active' => false]);
    }

    private function forgetActiveCache(): void
    {
        Cache::forget(self::ACTIVE_CACHE_KEY);
    }

    private function formatCompanyInfo(CompanyInfo $company): array
    {
        return [
            'id' => $company->id,
            'company_name' => $company->company_name,
            'company_address' => $company->company_address,
            'company_pan_no' => $company->company_pan_no,
            'company_phone_no' => $company->company_phone_no,
            'company_mobile_no' => $company->company_mobile_no,
            'company_email' => $company->company_email,
            'company_website' => $company->company_website,
            'logo' => $company->logo,
            'logo_url' => $this->resolveLogoUrl($company->logo),
            'is_active' => $company->is_active,
        ];
    }

    private function resolveLogoUrl(?string $path): ?string
    {
        return $path ? asset('storage/' . $path) : null;
    }
}
