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
                    ->isRequired()
                    ->children()
                        ->arrayNode('wassenger')
                            ->isRequired()
                            ->children()
                                ->scalarNode('api_key')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                    ->info('Wassenger API key. Use an env variable: "%env(WASSENGER_API_KEY)%"')
                                ->end()
                                ->scalarNode('device_id')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                    ->info('Wassenger device ID. Use an env variable: "%env(WASSENGER_DEVICE_ID)%"')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('webhook_secret')
                    ->defaultNull()
                    ->info('Secret token for webhook verification.')
                    ->validate()
                        ->ifTrue(static fn (mixed $v): bool => $v !== null && trim((string) $v) === '')
                        ->thenInvalid('webhook_secret cannot be an empty string.')
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
