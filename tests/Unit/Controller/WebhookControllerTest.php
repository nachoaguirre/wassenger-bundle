<?php

declare(strict_types=1);

namespace Nachoaguirre\WassengerBundle\Tests\Unit\Controller;

use Nachoaguirre\WassengerBundle\Controller\WebhookController;
use Nachoaguirre\WassengerBundle\Event\WebhookEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class WebhookControllerTest extends TestCase
{
    private EventDispatcherInterface&MockObject $dispatcher;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
    }

    private function makeController(?string $secret = null): WebhookController
    {
        return new WebhookController($this->dispatcher, $secret);
    }

    private function makeRequest(array $payload, array $headers = []): Request
    {
        $request = Request::create('/webhook', 'POST', [], [], [], [], json_encode($payload));
        $request->headers->set('Content-Type', 'application/json');

        foreach ($headers as $name => $value) {
            $request->headers->set($name, $value);
        }

        return $request;
    }

    // --- Sin secret configurado ---

    public function testDispatchesEventWithoutSecret(): void
    {
        $controller = $this->makeController(null);

        $this->dispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(
                self::isInstanceOf(WebhookEvent::class),
                WebhookEvent::NAME,
            );

        $response = $controller($this->makeRequest(['event' => 'message:in', 'data' => []]));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    // --- Validación de token ---

    public function testReturns401WhenTokenIsMissing(): void
    {
        $controller = $this->makeController('my-secret');

        $this->dispatcher->expects(self::never())->method('dispatch');

        $response = $controller($this->makeRequest(['event' => 'message:in']));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testReturns401WhenTokenIsWrong(): void
    {
        $controller = $this->makeController('correct-secret');

        $this->dispatcher->expects(self::never())->method('dispatch');

        $response = $controller(
            $this->makeRequest(['event' => 'message:in'], ['X-Wassenger-Token' => 'wrong-secret'])
        );

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testDispatchesEventWhenTokenIsCorrect(): void
    {
        $controller = $this->makeController('my-secret');

        $this->dispatcher->expects(self::once())->method('dispatch');

        $response = $controller(
            $this->makeRequest(['event' => 'message:in'], ['X-Wassenger-Token' => 'my-secret'])
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    // --- Validación de payload ---

    public function testReturns400WhenPayloadIsEmpty(): void
    {
        $controller = $this->makeController();

        $request = Request::create('/webhook', 'POST', [], [], [], [], '');
        $this->dispatcher->expects(self::never())->method('dispatch');

        $response = $controller($request);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testReturns400WhenEventKeyIsMissing(): void
    {
        $controller = $this->makeController();

        $this->dispatcher->expects(self::never())->method('dispatch');

        $response = $controller($this->makeRequest(['data' => 'no-event-key']));

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testReturns400WhenPayloadIsNotJson(): void
    {
        $controller = $this->makeController();

        $request = Request::create('/webhook', 'POST', [], [], [], [], 'not-json');
        $this->dispatcher->expects(self::never())->method('dispatch');

        $response = $controller($request);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    // --- Evento despachado correctamente ---

    public function testDispatchedEventCarriesCorrectPayloadAndType(): void
    {
        $controller = $this->makeController();

        $payload = ['event' => 'message:in', 'data' => ['from' => '+549111', 'body' => 'hola']];

        $this->dispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(
                self::callback(function (WebhookEvent $event) use ($payload): bool {
                    return $event->getEventType() === 'message:in'
                        && $event->getPayload() === $payload;
                }),
                WebhookEvent::NAME,
            );

        $controller($this->makeRequest($payload));
    }
}
