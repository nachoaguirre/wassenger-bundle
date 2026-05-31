<?php

declare(strict_types=1);

namespace Nachoaguirre\WassengerBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class WebhookEvent extends Event
{
    public const NAME = 'wassenger.webhook_received';

    public function __construct(
        private readonly array $payload,
        private readonly string $eventType,
    ) {
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }
}
