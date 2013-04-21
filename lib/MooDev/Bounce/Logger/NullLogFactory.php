<?php
/**
 * @author Jonathan Oddy <jonathan@moo.com>
 * @copyright Copyright (c) 2012, MOO Print Ltd.
 * @license ISC
 */
namespace MooDev\Bounce\Logger;

use Psr\Log\NullLogger;

class NullLogFactory
{
    public static function getLog($class) {
        return new NullLogger();
    }
}
