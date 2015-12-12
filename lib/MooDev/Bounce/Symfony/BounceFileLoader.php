<?php
/**
 * Created by IntelliJ IDEA.
 * User: jono
 * Date: 12/12/2015
 * Time: 12:40
 */

namespace MooDev\Bounce\Symfony;


use MooDev\Bounce\Config\Context;
use MooDev\Bounce\Context\XmlContextParser;
use MooDev\Bounce\Proxy\ProxyGeneratorFactory;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\FileLoader;

class BounceFileLoader extends FileLoader
{

    /**
     * @var string[]
     */
    private $customNamespaces;

    /**
     * @var ProxyGeneratorFactory
     */
    private $proxyGeneratorFactory;

    public function __construct(ContainerBuilder $container, FileLocatorInterface $locator, ProxyGeneratorFactory $proxyGeneratorFactory, $customNamespaces = [])
    {
        parent::__construct($container, $locator);
        $this->proxyGeneratorFactory = $proxyGeneratorFactory;
        $this->customNamespaces = $customNamespaces;
    }

    /**
     * Loads a resource.
     *
     * @param mixed $resource The resource
     * @param string|null $type The resource type or null if unknown
     *
     * @throws \Exception If something went wrong
     */
    public function load($resource, $type = null)
    {
        $path = $this->locator->locate($resource);

        $bounceParser = new XmlContextParser($path, $this->customNamespaces);
        $bounceContext = $bounceParser->getContext();

        $this->importContext($bounceContext);


    }

    /**
     * Returns whether this class supports the given resource.
     *
     * @param mixed $resource A resource
     * @param string|null $type The resource type or null if unknown
     *
     * @return bool True if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource) && 'xml' === pathinfo($resource, PATHINFO_EXTENSION);
    }

    protected function importContext(Context $context) {
        $configBeanFactory = new SymfonyConfigBeanFactory($this->proxyGeneratorFactory->getLookupMethodProxyGenerator($context->uniqueId));
        foreach ($context->childContexts as $childContext) {
            $this->importContext($childContext);
        }
        foreach ($context->beans as $bean) {
            if (empty($bean->name)) {
                // TODO: wat.
                continue;
            }
            $this->container->setDefinition($bean->name, $configBeanFactory->create($bean));
        }
        $this->container->addResource(new FileResource($context->fileName));
    }

}