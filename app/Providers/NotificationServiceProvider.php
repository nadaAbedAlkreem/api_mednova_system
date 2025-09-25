<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Omnix\NotificationManager;
use App\Repositories\ELoquent\OmnixWhatsAppNotificationRepository;

class NotificationServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(NotificationManager::class, function ($app) {
            $manager = new NotificationManager();
            $manager->registerDriver('whatsapp', new OmnixWhatsAppNotificationRepository());
            return $manager;
        });
    }
}
