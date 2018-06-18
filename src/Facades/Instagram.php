<?php namespace Tekton\Services\Facades;

class Instagram extends \Dynamis\Facade
{
    protected static function getFacadeAccessor()
    {
        return 'services.instagram';
    }
}
