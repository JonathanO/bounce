<?php
/**
 * Created by IntelliJ IDEA.
 * User: jono
 * Date: 11/12/2015
 * Time: 21:17
 */

namespace MooDev\Bounce\Symfony;

use MooDev\Bounce\Config\Context;
use MooDev\Bounce\Context\ApplicationContext;
use MooDev\Bounce\Context\XmlContextParser;
use MooDev\Bounce\Proxy\Utils\Base32Hex;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;

class SymfonyApplicationContext extends ApplicationContext
{

    /**
     * @var SymfonyConfigBeanFactory
     */
    private $configBeanFactory;

    /**
     * @return ContainerInterface
     */
    protected function getContainerBuilderCached($contextFile, $customNamespaces, $cacheDir, $isDebug)
    {
        $contextFile = realpath($contextFile);

        $file = $cacheDir . '/' . basename($contextFile) . '.cache';
        $className = "c" . Base32Hex::encode($contextFile);

        $containerConfigCache = new ConfigCache($file, $isDebug);

        if (!$containerConfigCache->isFresh()) {
            $containerBuilder = $this->buildContainer($contextFile, $customNamespaces);

            $dumper = new PhpDumper($containerBuilder);
            $containerConfigCache->write(
                $dumper->dump(array('class' => $className)),
                $containerBuilder->getResources()
            );
        }

        require_once $file;
        return new $className();
    }

    /**
     * SymfonyApplicationContext constructor.
     * @param string $contextFile
     * @param string[] $customNamespaces
     * @param string $cacheDir
     * @param bool $isDebug
     */
    public function __construct($contextFile, $customNamespaces = [], $cacheDir = null, $isDebug = false)
    {
        $this->configBeanFactory = new SymfonyConfigBeanFactory();

        if ($cacheDir) {
            $containerBuilder = $this->getContainerBuilderCached($contextFile, $customNamespaces, $cacheDir, $isDebug);
        } else {
            $containerBuilder = $this->buildContainer($contextFile, $customNamespaces);
        }

        parent::__construct(new SymfonyContainerBeanFactory($containerBuilder));
    }

    protected function importContext(Context $context, ContainerBuilder $container) {
        $container->addResource(new FileResource($context->fileName));
        foreach ($context->childContexts as $childContext) {
            $this->importContext($childContext, $container);
        }
        foreach ($context->beans as $bean) {
            if (empty($bean->name)) {
                // wat.
                continue;
            }
            $container->setDefinition($bean->name, $this->configBeanFactory->create($bean));
        }
    }

    protected function buildContainer($contextFile, $customNamespaces) {
        $containerBuilder = new ContainerBuilder();

        $bounceParser = new XmlContextParser($contextFile, $customNamespaces);
        $bounceContext = $bounceParser->getContext();

        $this->importContext($bounceContext, $containerBuilder);

        $containerBuilder->compile();
        return $containerBuilder;
    }

}