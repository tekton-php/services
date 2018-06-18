<?php namespace Tekton\Services\Providers;

use Illuminate\Support\ServiceProvider;
use Tekton\Services\Youtube;

class YoutubeProvider extends ServiceProvider
{
    function provides()
    {
        return ['services.youtube'];
    }

    function register()
    {
        $this->app->singleton('services.youtube', function() {
            $config = app('config')->get('services.youtube');

            return new Youtube($config);
        });
    }
}
