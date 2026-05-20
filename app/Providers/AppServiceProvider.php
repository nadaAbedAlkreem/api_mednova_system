<?php

namespace App\Providers;


use App\Models\Admin;
use App\Models\Customer;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
        Scramble::afterOpenApiGenerated(function (OpenApi $openApi) {
            $openApi->secure(
                SecurityScheme::http('bearer', 'JWT')
            );
        });

        Gate::define('viewApiDocs', function () {
            $admin = auth()->guard('admin')->user();
            \Illuminate\Support\Facades\Log::info('بيانات الآدمن المحاول للدخول: ' . json_encode($admin));
            return $admin && in_array($admin->email, ['super_admin@gmail.com']);
        });
        Scramble::configure()
            ->routes(function (Route $route) {
                return Str::startsWith($route->uri, 'api/');
            });

        // كود الـ guessPolicyNamesUsing الخاص بك...
        Gate::guessPolicyNamesUsing(function (string $modelClass) {
            if (in_array($modelClass, [
                \App\Models\ConsultationChatRequest::class,
                \App\Models\ConsultationVideoRequest::class,
                \App\Models\Customer::class,
            ])) {
                return \App\Policies\ConsultationPolicy::class;
            }
            return null;
        });
    }
}
