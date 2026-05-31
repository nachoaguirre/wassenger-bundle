<?php

declare(strict_types=1);

namespace Nachoaguirre\WassengerBundle\Controller;

use Nachoaguirre\WassengerBundle\Event\WebhookEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class WebhookController
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ?string $webhookSecret = null,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        if ($this->webhookSecret) {
            $token = (string) $request->headers->get('X-Wassenger-Token', '');

            if (!hash_equals($this->webhookSecret, $token)) {
                return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
            }
        }

        $content = $request->getContent();
        $payload = json_decode($content, true);

        if (!$payload || !isset($payload['event'])) {
            return new JsonResponse(['error' => 'Invalid payload'], Response::HTTP_BAD_REQUEST);
        }

        $event = new WebhookEvent($payload, $payload['event']);

        $this->eventDispatcher->dispatch($event, WebhookEvent::NAME);

        return new JsonResponse(['status' => 'success', 'message' => 'Event dispatched']);
    }
}
