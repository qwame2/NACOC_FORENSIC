<?php

namespace App\Providers;

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
        \Illuminate\Pagination\Paginator::useTailwind();

        \Illuminate\Support\Facades\Gate::policy(\App\Models\ForensicCase::class, \App\Policies\ForensicCasePolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\MultipleSuspectCase::class, \App\Policies\ForensicCasePolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\SingleSuspectCase::class, \App\Policies\ForensicCasePolicy::class);

        \Illuminate\Support\Facades\Event::listen(\Illuminate\Database\Events\MigrationsEnded::class, function () {
            \App\Services\SchemaCache::flushAll();
        });

        if (file_exists(app_path('Helpers/LabExaminationHelper.php'))) {
            require_once app_path('Helpers/LabExaminationHelper.php');
        }
    }
}
