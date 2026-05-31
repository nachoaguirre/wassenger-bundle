<?php

declare(strict_types=1);

namespace Nachoaguirre\WassengerBundle\EventSubscriber;

use Nachoaguirre\WassengerBundle\Event\WebhookEvent;
use Nachoaguirre\WassengerBundle\Provider\WassengerProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
        private readonly WassengerProvider $provider,
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
        $body = strtolower(trim($payload['data']['body'] ?? ''));
        $from = $payload['data']['from'];

        foreach ($this->greetingsMap as $keyword => $response) {
            if (str_contains($body, $keyword)) {
                $this->provider->sendMessage($from, $response);
                break;
            }
        }
    }
}
