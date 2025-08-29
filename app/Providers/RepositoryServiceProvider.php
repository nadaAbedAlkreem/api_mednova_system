<?php

namespace App\Providers;

use App\Repositories\Eloquent\FriendsRepository;
use App\Repositories\Eloquent\NotificationRepository;
use App\Repositories\INotificationRepositories;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use App\Repositories\Eloquent\CustomerRepository;
use App\Repositories\IUserRepository;
use App\Repositories\IFriendsRepositories;
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
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
