<?php

declare(strict_types=1);

namespace Nachoaguirre\WassengerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('nachoaguirre_wassenger');

        $treeBuilder->getRootNode()
            ->children()
                ->booleanNode('enable_greetings')
                    ->defaultTrue()
                    ->info('Enable or disable automatic greetings messages.')
                ->end()
                ->arrayNode('providers')
                    ->children()
                        ->arrayNode('wassenger')
                            ->children()
                                ->scalarNode('api_key')->isRequired()->end()
                                ->scalarNode('device_id')->isRequired()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('recipients')
                    ->useAttributeAsKey('alias')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('identifier')->isRequired()->end()
                            ->booleanNode('enabled')->defaultTrue()->end()
                            ->enumNode('type')
                                ->values(['individual', 'group'])
                                ->defaultValue('individual')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
