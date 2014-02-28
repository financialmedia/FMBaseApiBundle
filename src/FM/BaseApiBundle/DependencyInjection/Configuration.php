<?php

namespace FM\BaseApiBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('fm_base_api');
        $rootNode
            ->children()
                ->scalarNode('token_host')
                    ->isRequired()
                ->end()
                ->scalarNode('host')
                    ->isRequired()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
