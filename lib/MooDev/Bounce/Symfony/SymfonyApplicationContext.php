<?php
/**
 * Created by IntelliJ IDEA.
 * User: jono
 * Date: 11/12/2015
 * Time: 21:17
 */

namespace MooDev\Bounce\Symfony;

use MooDev\Bounce\Context\ApplicationContext;
use MooDev\Bounce\Proxy\Utils\Base32Hex;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;

class SymfonyApplicationContext extends ApplicationContext
{

    /**
     * @return ContainerInterface
     */
    protected function getContainerBuilderCached($contextFile, $cacheDir, $isDebug, $loaderFactory)
    {
        $contextFile = realpath($contextFile);

        $file = $cacheDir . '/' . basename($contextFile) . '.cache';
        $className = "c" . Base32Hex::encode($contextFile);

        $containerConfigCache = new ConfigCache($file, $isDebug);

        if (!$containerConfigCache->isFresh()) {
            $containerBuilder = $this->buildContainer($contextFile, $loaderFactory);

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
    public function __construct($contextFile, array $customNamespaces = [], $cacheDir = null, $isDebug = false, LoaderFactory $loaderFactory = null)
    {
        if ($loaderFactory === null) {
            $loaderFactory = new DefaultLoaderFactory($customNamespaces);
        }

        if ($cacheDir) {
            $containerBuilder = $this->getContainerBuilderCached($contextFile, $cacheDir, $isDebug, $loaderFactory);
        } else {
            $containerBuilder = $this->buildContainer($contextFile, $loaderFactory);
        }

        parent::__construct(new SymfonyContainerBeanFactory($containerBuilder));
    }

    protected function buildContainer($contextFile, LoaderFactory $loaderFactory) {
        $containerBuilder = new ContainerBuilder();

        $loader = $loaderFactory->getLoader($containerBuilder);
        $loader->load($contextFile);

        $containerBuilder->compile();
        return $containerBuilder;
    }

}