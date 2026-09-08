<?php

namespace App\Providers;

use App\Models\RouteModel;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Route model binding for RouteModel
        Route::model('route', RouteModel::class);
    }
}