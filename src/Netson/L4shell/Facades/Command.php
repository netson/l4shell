<?php namespace Netson\L4shell\Facades;

use Illuminate\Support\Facades\Facade;

class Command extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'l4shell'; }
}