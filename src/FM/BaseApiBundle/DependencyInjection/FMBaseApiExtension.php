<?php

namespace FM\BaseApiBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class FMBaseApiExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $this->setParameters($container, $config);
    }

    protected function setParameters(ContainerBuilder $container, array $config)
    {
        $this->setConfigParameters($container, $config, array('fm_api'));
    }

    protected function setConfigParameters(ContainerBuilder $container, array $config, array $prefixes = array())
    {
        foreach ($config as $key => $value) {
            $newPrefixes = array_merge($prefixes, array($key));

            if (is_array($value) && !is_numeric(key($value))) {
                $this->setConfigParameters($container, $value, $newPrefixes);

                continue;
            }

            $name = implode('.', $newPrefixes);
            $container->setParameter($name, $value);
        }
    }
}
