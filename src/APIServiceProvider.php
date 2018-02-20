<?php

namespace Tokenly\LaravelApiProvider;

use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

/*
* APIServiceProvider
*/
class APIServiceProvider extends ServiceProvider
{

    public function boot()
    {
        // migrations
        $this->loadMigrationsFrom(__DIR__.'/../migrations');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('Tokenly\LaravelApiProvider\Contracts\APIUserRepositoryContract', function($app) {
            return $app->make(Config::get('api.userRepositoryClass', 'Tokenly\LaravelApiProvider\Repositories\UserRepository'));
        });

        $this->app->bind('Tokenly\LaravelApiProvider\Contracts\APIUserContract', function($app) {
            return $app->make(Config::get('api.userClass', 'Tokenly\LaravelApiProvider\Model\APIUser'));
        });

        $this->app->bind('api.catchErrors', function($app) {
            return $app->make('Tokenly\LaravelApiProvider\Middleware\HandleAPIErrors');
        });
        $this->app->bind('api.publicAuth', function($app) {
            return $app->make('Tokenly\LaravelApiProvider\Middleware\AuthenticatePublicAPIRequest');
        });
        $this->app->bind('api.protectedAuth', function($app) {
            return $app->make('Tokenly\LaravelApiProvider\Middleware\AuthenticateProtectedAPIRequest');
        });

        // add artisan commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                'Tokenly\LaravelApiProvider\Commands\NewAPIUserCommand',
                'Tokenly\LaravelApiProvider\Commands\ListAPIUsersCommand',
                'Tokenly\LaravelApiProvider\Commands\MakeAPIModelCommand',
                'Tokenly\LaravelApiProvider\Commands\MakeAPIRespositoryCommand',
            ]);
        }
    }


}

