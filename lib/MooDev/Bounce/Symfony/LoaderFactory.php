<?php
/**
 * Created by IntelliJ IDEA.
 * User: jono
 * Date: 12/12/2015
 * Time: 12:54
 */

namespace MooDev\Bounce\Symfony;


use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

interface LoaderFactory
{

    /**
     * @param ContainerInterface $container
     * @return LoaderInterface
     */
    public function getLoader(ContainerInterface $container);

}