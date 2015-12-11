<?php
/**
 * Created by IntelliJ IDEA.
 * User: jono
 * Date: 11/12/2015
 * Time: 22:11
 */

namespace MooDev\Bounce\Symfony;


use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

interface SymfonyAwareValueProvider
{
    /***
     * @return mixed|Definition|Reference
     */
    public function getSymfonyValue();
}