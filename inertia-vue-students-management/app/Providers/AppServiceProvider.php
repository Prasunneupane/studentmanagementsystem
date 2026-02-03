<?php

namespace App\Providers;

use App\Interface\ClassSubjectInterface;
use App\Interface\GuardianInterface;
use App\Interface\PermissionInterface;
use App\Interface\RoleInterface;
use App\Interface\SubjectInterface;
use App\Interface\TeacherInterfacce;
use App\Interface\UserInterface;
use App\Models\Permission;
use App\Models\Roles;
use App\Observers\PermissionObserver;
use App\Observers\RolesObserver;
use App\Repositories\LocationInterface;
use App\Repositories\LocationRepository;
use App\Repositories\PermissionRepository;
use App\Repositories\RoleRepository;
use App\Repositories\SubjectRepository;
use App\Repositories\TeacherRepository;
use App\Repositories\UserRepository;
use App\Services\ClassSubjectService;
use App\Services\GuardianService;
use Illuminate\Support\ServiceProvider;
use App\Contracts\StudentRepositoryInterface;
use App\Contracts\StudentServiceInterface;
use App\Services\StudentService;
use App\Repositories\StudentRepository;
use Inertia\Inertia;

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
        $this->app->bind(RoleInterface::class, RoleRepository::class);
        $this->app->bind(PermissionInterface::class, PermissionRepository::class);
        $this->app->bind(UserInterface::class, UserRepository::class);
        $this->app->bind(ClassSubjectInterface::class, ClassSubjectService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Inertia::share([
        'jwt_token' => fn () => session('jwt_token'),
    ]);
        Roles::observe(RolesObserver::class);
        Permission::observe(PermissionObserver::class);
    }
}
