<?php namespace Tekton\Services\Providers;

use \Tekton\Services\Instagram;

class InstagramProvider extends \Tekton\Support\ServiceProvider {

    function register() {
        $this->config = app('config')->get('services.instagram');

        $this->app->singleton('services.instagram', function() {
            return new Instagram($this->config);
        });
    }

    function boot() {

    }
}
