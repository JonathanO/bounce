<?php
/**
 * Created by IntelliJ IDEA.
 * User: jono
 * Date: 12/12/2015
 * Time: 12:56
 */

namespace MooDev\Bounce\Symfony;


use MooDev\Bounce\Proxy\ProxyGeneratorFactory;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DefaultLoaderFactory implements LoaderFactory
{

    private $customNamespaces;

    private $proxyGeneratorFactory;

    /**
     * DefaultLoaderFactory constructor.
     * @param string[] $customNamespaces
     * @param ProxyGeneratorFactory $proxyGeneratorFactory
     */
    public function __construct(array $customNamespaces = [], ProxyGeneratorFactory $proxyGeneratorFactory)
    {
        $this->proxyGeneratorFactory = $proxyGeneratorFactory;
        $this->customNamespaces = $customNamespaces;
    }


    /**
     * @param ContainerInterface $container
     * @return LoaderInterface
     */
    public function getLoader(ContainerInterface $container)
    {
        $fileLocator = new FileLocator();
        return new BounceFileLoader($container, $fileLocator, $this->proxyGeneratorFactory, $this->customNamespaces);
    }
}