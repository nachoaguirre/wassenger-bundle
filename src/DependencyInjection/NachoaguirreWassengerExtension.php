<?php

declare(strict_types=1);

namespace Nachoaguirre\WassengerBundle\DependencyInjection;

use Nachoaguirre\WassengerBundle\Controller\WebhookController;
use Nachoaguirre\WassengerBundle\EventSubscriber\GreetingsSubscriber;
use Nachoaguirre\WassengerBundle\Model\Recipient;
use Nachoaguirre\WassengerBundle\Provider\WassengerProvider;
use Nachoaguirre\WassengerBundle\Registry\RecipientRegistry;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class NachoaguirreWassengerExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yaml');

        $providerDefinition = $container->getDefinition(WassengerProvider::class);
        $providerDefinition->setArgument('$apiKey', $config['providers']['wassenger']['api_key']);
        $providerDefinition->setArgument('$deviceId', $config['providers']['wassenger']['device_id']);

        $webhookDefinition = $container->getDefinition(WebhookController::class);
        $webhookDefinition->setArgument('$webhookSecret', $config['webhook_secret'] ?? null);

        $greetingsDefinition = $container->getDefinition(GreetingsSubscriber::class);
        $greetingsDefinition->setArgument('$enabled', $config['enable_greetings']);

        $registryDefinition = $container->getDefinition(RecipientRegistry::class);
        foreach ($config['recipients'] as $alias => $data) {
            $registryDefinition->addMethodCall('addRecipient', [
                new Definition(Recipient::class, [
                    $data['identifier'],
                    $alias,
                    $data['type'],
                    $data['enabled'],
                ]),
            ]);
        }
    }
}
