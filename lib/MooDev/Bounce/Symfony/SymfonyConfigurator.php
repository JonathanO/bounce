<?php
/**
 * Created by IntelliJ IDEA.
 * User: jono
 * Date: 12/12/2015
 * Time: 13:31
 */

namespace MooDev\Bounce\Symfony;


use MooDev\Bounce\Config\Configurable;

class SymfonyConfigurator
{

    public function configure($thing)
    {
        if ($thing instanceof Configurable) {
            $thing->configure();
        }
    }

}