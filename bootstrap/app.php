<?php

use App\Http\Middleware\CheckActivePatient;
use App\Http\Middleware\CheckIsAdmin;
use App\Http\Middleware\CheckIsDoctor;
use App\Http\Middleware\CheckIsDoctorApproved;
use App\Http\Middleware\CheckOwnerOrStaff;
use App\Http\Middleware\CheckIsPatient;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'is_admin'=>CheckIsAdmin::class,
            'approved_doctor'=>CheckIsDoctorApproved::class,
            'is_doctor'=>CheckIsDoctor::class,
            'active_patient'=>CheckActivePatient::class,
            'admin-or-doctor-or-Owner'=>CheckOwnerOrStaff::class,
            'is_patient'=>CheckIsPatient::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
