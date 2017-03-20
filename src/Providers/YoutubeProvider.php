<?php namespace Tekton\Services\Providers;

use \Tekton\Services\Youtube;

class YoutubeProvider extends \Tekton\Support\ServiceProvider {

    function register() {
        $this->config = app('config')->get('services.youtube');

        $this->app->singleton('services.youtube', function() {
            return new Youtube($this->config);
        });
    }

    function boot() {

    }
}
