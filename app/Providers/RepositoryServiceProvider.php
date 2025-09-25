<?php

namespace App\Providers;

use App\Repositories\Eloquent\OmnixLogRepository;
use App\Repositories\Eloquent\OmnixWebhookRepository;
use App\Repositories\Eloquent\OmnixWhatsAppNotificationRepository;
use App\Repositories\IOmnixLogRepositories;
use App\Repositories\IOmnixNotificationRepositories;
use App\Repositories\IOmnixWebhookRepositories;
use App\Services\Omnix\NotificationManager;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {


        $this->app->bind(IOmnixLogRepositories::class, OmnixLogRepository::class);
        $this->app->bind(IOmnixNotificationRepositories::class, OmnixWhatsAppNotificationRepository::class);
        $this->app->bind(IOmnixWebhookRepositories::class, OmnixWebhookRepository::class);

        foreach($this->getModels() as $model){
              $this->app->bind(
                 "App\Repositories\I{$model}Repositories",
                 "App\Repositories\Eloquent\\{$model}Repository");
         }

    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

     public function getModels()
     {
         $files = File::files(app_path('Models'));

         return collect($files)->map(function ($file) {
             return pathinfo($file, PATHINFO_FILENAME);
         });
     }
}
