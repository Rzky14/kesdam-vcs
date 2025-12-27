<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\User::class => \App\Policies\UserPolicy::class,
        \App\Models\Schedule::class => \App\Policies\SchedulePolicy::class,
        \App\Models\Surat::class => \App\Policies\SuratPolicy::class,
    ];

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
        // Register policies
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }
        
        // Route model binding untuk parameter 'surat' menggunakan model Dokumen
        \Illuminate\Support\Facades\Route::model('surat', \App\Models\Surat::class);
    }
}



