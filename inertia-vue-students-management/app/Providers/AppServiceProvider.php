<?php

namespace App\Providers;

use App\Repositories\LocationInterface;
use App\Repositories\LocationRepository;
use Illuminate\Support\ServiceProvider;
use App\Contracts\StudentRepositoryInterface;
use App\Contracts\StudentServiceInterface;
use App\Services\StudentService;
use App\Repositories\StudentRepository;
use Inertia\Inertia;
use Illuminate\Support\Facades\Session;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LocationInterface::class, LocationRepository::class);
        $this->app->bind(StudentRepositoryInterface::class, StudentRepository::class);
        $this->app->bind(StudentServiceInterface::class, StudentService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Inertia::share([
        'jwt_token' => fn () => session('jwt_token'),
    ]);
    }
}
