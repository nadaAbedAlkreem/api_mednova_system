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
use Laravel\Sanctum\PersonalAccessToken;

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

        Scramble::configure()
            ->routes(function (Route $route) {
                return Str::startsWith($route->uri, 'api/');
            });

        Gate::define('viewApiDocs', function () {
            $admin = null;

            // 1. الفحص عبر التوكن الممرر في الرابط (Query Parameter)
            if (request()->has('token')) {
                $tokenString = request()->query('token');

                // جلب التوكن والتأكد من أنه يخص موديل الـ Admin
                $token = PersonalAccessToken::findToken($tokenString);

                if ($token && $token->tokenable instanceof Admin) {
                    $admin = $token->tokenable;
                }
            }

            // 2. إذا لم يكن هناك توكن في الرابط، جرب الفحص عبر الجلسة الافتراضية للآدمن (كخيار احتياطي)
            if (! $admin) {
                $admin = auth()->guard('admin')->user();
            }

            // طباعة النتيجة بدقة في الـ Log
            Log::info('نتيجة فحص حارس الديكومنتشن النهائية: ' . json_encode($admin));

            // 3. التحقق من الإيميل والصلاحية
            return $admin && in_array($admin->email, ['super_admin@gmail.com']);
        });

        // أضف هذا الجزء هنا للسماح بالوصول في بيئة الـ staging دون قيود

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
