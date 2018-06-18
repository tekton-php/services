<?php namespace Tekton\Services\Providers;

use Illuminate\Support\ServiceProvider;
use Tekton\Services\Instagram;

class InstagramProvider extends ServiceProvider
{
    function provides()
    {
        return ['services.instagram'];
    }

    function register()
    {
        $this->app->singleton('services.instagram', function() {
            $config = app('config')->get('services.instagram');

            return new Instagram($config);
        });
    }
}
