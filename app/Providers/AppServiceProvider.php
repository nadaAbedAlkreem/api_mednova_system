<?php

namespace App\Providers;


use Illuminate\Support\Facades\Gate;
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
        Gate::guessPolicyNamesUsing(function (string $modelClass) {
            if (in_array($modelClass, [
                \App\Models\ConsultationChatRequest::class,
                \App\Models\ConsultationVideoRequest::class,
                \App\Models\Customer::class,

            ])) {
                return \App\Policies\ConsultationPolicy::class;
            }

            return null; // Laravel سيستخدم التسجيل العادي أو يتجاهل
        });
    }
}
