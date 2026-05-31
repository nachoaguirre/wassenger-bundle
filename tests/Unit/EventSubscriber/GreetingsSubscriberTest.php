<?php

declare(strict_types=1);

namespace Nachoaguirre\WassengerBundle\Tests\Unit\EventSubscriber;

use Nachoaguirre\WassengerBundle\Contract\ProviderInterface;
use Nachoaguirre\WassengerBundle\Event\WebhookEvent;
use Nachoaguirre\WassengerBundle\EventSubscriber\GreetingsSubscriber;
use Nachoaguirre\WassengerBundle\Exception\WassengerApiException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GreetingsSubscriberTest extends TestCase
{
    private ProviderInterface&MockObject $provider;

    protected function setUp(): void
    {
        $this->provider = $this->createMock(ProviderInterface::class);
    }

    private function makeSubscriber(bool $enabled = true): GreetingsSubscriber
    {
        return new GreetingsSubscriber($this->provider, $enabled);
    }

    private function makeEvent(string $type, array $data = []): WebhookEvent
    {
        return new WebhookEvent(['event' => $type, 'data' => $data], $type);
    }

    // --- Suscripción al evento correcto ---

    public function testSubscribesToWebhookEventName(): void
    {
        $events = GreetingsSubscriber::getSubscribedEvents();

        self::assertArrayHasKey(WebhookEvent::NAME, $events);
    }

    // --- Disabled ---

    public function testDoesNotSendWhenDisabled(): void
    {
        $this->provider->expects(self::never())->method('sendMessage');

        $subscriber = $this->makeSubscriber(enabled: false);
        $subscriber->onMessageReceived($this->makeEvent('message:in', [
            'from' => '+5491112345678',
            'body' => 'hola',
        ]));
    }

    // --- Tipo de evento ---

    public function testDoesNotSendForNonIncomingMessageEvent(): void
    {
        $this->provider->expects(self::never())->method('sendMessage');

        $subscriber = $this->makeSubscriber();
        $subscriber->onMessageReceived($this->makeEvent('message:out', [
            'from' => '+5491112345678',
            'body' => 'hola',
        ]));
    }

    // --- Payload incompleto ---

    public function testDoesNotSendWhenFromIsMissing(): void
    {
        $this->provider->expects(self::never())->method('sendMessage');

        $subscriber = $this->makeSubscriber();
        $subscriber->onMessageReceived($this->makeEvent('message:in', ['body' => 'hola']));
    }

    public function testDoesNotSendWhenBodyIsEmpty(): void
    {
        $this->provider->expects(self::never())->method('sendMessage');

        $subscriber = $this->makeSubscriber();
        $subscriber->onMessageReceived($this->makeEvent('message:in', [
            'from' => '+5491112345678',
            'body' => '   ',
        ]));
    }

    // --- Keyword matching ---

    public function testSendsReplyOnMatchingKeyword(): void
    {
        $this->provider
            ->expects(self::once())
            ->method('sendMessage')
            ->with('+5491112345678', self::stringContains('Hola'));

        $subscriber = $this->makeSubscriber();
        $subscriber->onMessageReceived($this->makeEvent('message:in', [
            'from' => '+5491112345678',
            'body' => 'hola amigo',
        ]));
    }

    public function testMatchingIsCaseInsensitive(): void
    {
        $this->provider->expects(self::once())->method('sendMessage');

        $subscriber = $this->makeSubscriber();
        $subscriber->onMessageReceived($this->makeEvent('message:in', [
            'from' => '+5491112345678',
            'body' => 'HELLO there',
        ]));
    }

    public function testOnlyFirstMatchingSendMessageIsCalled(): void
    {
        // Body contains both 'hola' and 'hello' but only one reply should be sent
        $this->provider->expects(self::once())->method('sendMessage');

        $subscriber = $this->makeSubscriber();
        $subscriber->onMessageReceived($this->makeEvent('message:in', [
            'from' => '+5491112345678',
            'body' => 'hola hello',
        ]));
    }

    public function testNoReplyForUnknownKeyword(): void
    {
        $this->provider->expects(self::never())->method('sendMessage');

        $subscriber = $this->makeSubscriber();
        $subscriber->onMessageReceived($this->makeEvent('message:in', [
            'from' => '+5491112345678',
            'body' => 'goodbye',
        ]));
    }

    // --- Resiliencia ante errores de API ---

    public function testDoesNotThrowWhenSendMessageFails(): void
    {
        $this->provider
            ->method('sendMessage')
            ->willThrowException(new WassengerApiException('API error'));

        $subscriber = $this->makeSubscriber();

        // Must not propagate the exception
        $subscriber->onMessageReceived($this->makeEvent('message:in', [
            'from' => '+5491112345678',
            'body' => 'hola',
        ]));

        $this->expectNotToPerformAssertions();
    }
}
