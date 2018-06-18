<?php namespace Tekton\Services\Facades;

class Youtube extends \Dynamis\Facade
{
    protected static function getFacadeAccessor()
    {
        return 'services.youtube';
    }
}
