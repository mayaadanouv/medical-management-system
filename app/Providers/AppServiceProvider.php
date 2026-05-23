<?php

namespace App\Providers;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Patient;
use App\Observers\DepartmentObserver;
use App\Observers\DoctorObserver;
use App\Observers\PatientObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Doctor::observe(DoctorObserver::class);
        Patient::observe(PatientObserver::class);
        Department::observe(DepartmentObserver::class);
    }
}
