<?php

namespace App\Providers;

use App\Interface\GuardianInterface;
use App\Interface\SubjectInterface;
use App\Interface\TeacherInterfacce;
use App\Repositories\GuardianRepository;
use App\Repositories\LocationInterface;
use App\Repositories\LocationRepository;
use App\Repositories\SubjectRepository;
use App\Repositories\TeacherRepository;
use App\Services\GuardianService;
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
        $this->app->bind(GuardianInterface::class, GuardianService::class);
        $this->app->bind(SubjectInterface::class, SubjectRepository::class);
        $this->app->bind(TeacherInterfacce::class, TeacherRepository::class);
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
