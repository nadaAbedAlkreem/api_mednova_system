<?php

namespace App\Providers;

use App\Models\ConsultationChatRequest;
use App\Models\ConsultationVideoRequest;
use App\Policies\ConsultationPolicy;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;


class AuthServiceProvider extends ServiceProvider
{
//    protected $policies = [
//        ConsultationChatRequest::class => ConsultationPolicy::class,
//        ConsultationVideoRequest::class => ConsultationPolicy::class,
//    ];
    /**
     * Register services.
     */
    public function register(): void
    {
     }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
    }
}
