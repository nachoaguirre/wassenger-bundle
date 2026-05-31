<?php

declare(strict_types=1);

namespace Nachoaguirre\WassengerBundle\EventSubscriber;

use Nachoaguirre\WassengerBundle\Contract\ProviderInterface;
use Nachoaguirre\WassengerBundle\Event\WebhookEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Throwable;

class GreetingsSubscriber implements EventSubscriberInterface
{
    private array $greetingsMap = [
        'hola' => '¡Hola! ¿Cómo puedo ayudarte? 😊',
        'hello' => 'Hello! How can I help you today? 🚀',
        'hi' => 'Hi there! Hope you are having a great day! ✨',
        'bonjour' => 'Bonjour! Comment puis-je vous aider? 🇫🇷',
        'ciao' => 'Ciao! Come posso aiutarti? 🇮🇹',
        'hallo' => 'Hallo! Wie kann ich dir helfen? 🇩🇪',
    ];

    public function __construct(
        private readonly ProviderInterface $provider,
        private readonly bool $enabled = true,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            WebhookEvent::NAME => 'onMessageReceived',
        ];
    }

    public function onMessageReceived(WebhookEvent $event): void
    {
        if (!$this->enabled || $event->getEventType() !== 'message:in') {
            return;
        }

        $payload = $event->getPayload();

        if (!isset($payload['data']['from'])) {
            return;
        }

        $body = strtolower(trim($payload['data']['body'] ?? ''));
        $from = $payload['data']['from'];

        foreach ($this->greetingsMap as $keyword => $response) {
            if (str_contains($body, $keyword)) {
                try {
                    $this->provider->sendMessage($from, $response);
                } catch (Throwable) {
                    // Do not re-throw: the webhook handler must always return 2xx to avoid retries
                }
                break;
            }
        }
    }
}
