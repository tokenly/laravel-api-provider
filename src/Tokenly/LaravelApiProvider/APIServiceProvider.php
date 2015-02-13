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
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->bindConfig();

        $this->app->bind('Tokenly\LaravelApiProvider\Contracts\APIUserRepositoryContract', function($app) {
            return $app->make(Config::get('api.userRepositoryClass'));
        });

        $this->app->bind('Tokenly\LaravelApiProvider\Contracts\APIUserContract', function($app) {
            return $app->make(Config::get('api.userClass'));
        });

        $this->app->bind('api.catchErrors', function($app) {
            return $app->make('Tokenly\LaravelApiProvider\Middleware\HandleAPIErrors');
        });
        $this->app->bind('api.publicAuth', function($app) {
            return $app->make('Tokenly\LaravelApiProvider\Middleware\AuthenticatePublicAPIRequest');
        });
        $this->app->bind('api.protectedAuth', function($app) {
            return $app->make('Tokenly\LaravelApiProvider\Middleware\AuthenticateProtectedPIRequest');
        });

    }

    protected function bindConfig()
    {
        $config = [];

        $config = [
            'api.userRepositoryClass' => env('API_USER_REPOSITORY_CLASS', 'Tokenly\LaravelApiProvider\Repositories\UserRepository'),
            'api.userClass' => env('API_USER_CLASS', 'Tokenly\LaravelApiProvider\Model\APIUser'),
        ];

        // set the laravel config
        Config::set($config);

        return $config;
    }

}

