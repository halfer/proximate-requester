<?php

/**
 * A trait for server utilities
 */

namespace Proximate\Tests\Integration;

trait PortChooser
{
    /**
     * Cycles through a port choice depending on the current UNIX time
     *
     * @param integer $min
     * @param integer $max
     * @param integer
     */
    protected static function choosePort($min, $max)
    {
        $range = $max - $min + 1;
        $mod = time() % $range;
        $port = $min + $mod;

        return $port;
    }
}
