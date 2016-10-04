<?php

namespace Ucsf\RestOrmBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ucsf_rest_orm');

        $rootNode
            ->children()
                ->arrayNode('connections')->isRequired()->cannotBeEmpty()
                    ->prototype('array')
                        ->children()
                            ->scalarNode('base_uri')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('username')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('password')->isRequired()->cannotBeEmpty()->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('entity_managers')->isRequired()->cannotBeEmpty()
                    ->prototype('array')
                        ->children()
                            ->scalarNode('connection')->isRequired()->cannotBeEmpty()->end()
                            ->arrayNode('commands')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('method')->isRequired()->cannotBeEmpty()->end()
                                        ->scalarNode('path')->isRequired()->cannotBeEmpty()->end()
                                        ->scalarNode('class')->isRequired()->cannotBeEmpty()->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('repositories')
                                ->prototype('array')
                                    ->children()
                                        ->arrayNode('persist')
                                            ->children()
                                                ->scalarNode('method')->isRequired()->cannotBeEmpty()->end()
                                                ->scalarNode('path')->isRequired()->cannotBeEmpty()->end()
                                            ->end()
                                        ->end()
                                        ->arrayNode('all')
                                            ->children()
                                                ->scalarNode('method')->isRequired()->cannotBeEmpty()->end()
                                                ->scalarNode('path')->isRequired()->cannotBeEmpty()->end()
                                            ->end()
                                        ->end()
                                        ->arrayNode('find')
                                            ->prototype('array')
                                                ->children()
                                                    ->scalarNode('method')->isRequired()->cannotBeEmpty()->end()
                                                    ->scalarNode('path')->isRequired()->cannotBeEmpty()->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();


        return $treeBuilder;
    }
}
