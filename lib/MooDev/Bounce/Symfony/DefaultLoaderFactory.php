<?php
/**
 * Created by IntelliJ IDEA.
 * User: jono
 * Date: 12/12/2015
 * Time: 12:56
 */

namespace MooDev\Bounce\Symfony;


use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DefaultLoaderFactory implements LoaderFactory
{

    private $customNamespaces;

    /**
     * DefaultLoaderFactory constructor.
     * @param string[] $customNamespaces
     */
    public function __construct(array $customNamespaces = [])
    {
        $this->customNamespaces = $customNamespaces;
    }


    /**
     * @param ContainerInterface $container
     * @return LoaderInterface
     */
    public function getLoader(ContainerInterface $container)
    {
        $fileLocator = new FileLocator();
        return new BounceFileLoader($container, $fileLocator, $this->customNamespaces);
    }
}