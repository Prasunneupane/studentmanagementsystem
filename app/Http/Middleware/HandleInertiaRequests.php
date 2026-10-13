<?php

namespace App\Http\Middleware;

use App\Interface\CompanyInfoInterface;
use App\Services\PermissionService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    protected $permissionService;
    public function __construct(PermissionService $permissionService, private CompanyInfoInterface $companyInfoService)
    {
        $this->permissionService = $permissionService;
    }

    
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'auth' => fn () => $request->user()
                ?   [
                        'user' => $request->user(),
                        'permissions' => $request->user() 
                            ? $this->permissionService->getNavigationPermissions($request->user())
                            : null,
                    ]:null,
            'ziggy' => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            // Same closure pattern as 'auth' above — resolves on every full
            // page load, but cheaply: CompanyInfoService caches the active
            // row, so this is a DB hit only once per cache TTL, not per request.
            'companyInfo' => fn () => $this->companyInfoService->getActiveCompanyInfo(),
        ];
    }
}
