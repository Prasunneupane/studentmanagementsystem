<?php

namespace App\Services;


use App\Models\AcademicYears;
use App\Models\ClassSubject;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FiscalYearService 
{
    private const CACHE_KEY = 'active_fiscal_year';

    /**
     * Get active fiscal year.
     *
     * First request:
     *      Cache miss -> DB -> Cache
     *
     * Later requests:
     *      Cache hit -> no DB query
     */
    public function getActive(): ?FiscalYear
    {
        return Cache::rememberForever(
            self::CACHE_KEY,
            function () {
                return FiscalYear::where('is_active', true)->first();
            }
        );
    }

    /**
     * Update active fiscal year.
     */
    public function setActive(int $fiscalYearId): FiscalYear
    {
        return DB::transaction(function () use ($fiscalYearId) {

            FiscalYear::where('is_active', true)
                ->update([
                    'is_active' => false,
                ]);

            $fiscalYear = FiscalYear::findOrFail($fiscalYearId);

            $fiscalYear->update([
                'is_active' => true,
            ]);

            // Update cache immediately
            Cache::forever(
                self::CACHE_KEY,
                $fiscalYear->fresh()
            );

            return $fiscalYear->fresh();
        });
    }

    /**
     * Clear active fiscal year cache.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
    

   
}
