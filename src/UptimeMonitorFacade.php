<?php

namespace Squareconcepts\UptimeMonitor;

use Illuminate\Support\Facades\Facade;

class UptimeMonitorFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'uptime-monitor';
    }
}
