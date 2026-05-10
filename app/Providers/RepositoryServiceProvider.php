<?php

namespace App\Providers;

use App\Repositories\Eloquent\OmnixSubscribeRepository;
use App\Repositories\Eloquent\OmnixWebhookRepository;
use App\Repositories\Eloquent\OmnixWhatsAppNotificationRepository;
use App\Repositories\Eloquent\WithdrawalRepository;
use App\Repositories\IOmnixNotificationRepositories;
use App\Repositories\IOmnixSubscribeRepositories;
use App\Repositories\IOmnixWebhookRepositories;
use App\Repositories\IWithdrawalRepositories;
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
//        $this->app->bind(IOmnixSubscribeRepositories::class, OmnixSubscribeRepository::class);
//        $this->app->bind(IOmnixNotificationRepositories::class, OmnixWhatsAppNotificationRepository::class);
//        $this->app->bind(IOmnixWebhookRepositories::class, OmnixWebhookRepository::class);

        // Manual binding: interface name differs from model name (WithdrawalRequest → Withdrawal)
        $this->app->bind(IWithdrawalRepositories::class, WithdrawalRepository::class);

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
