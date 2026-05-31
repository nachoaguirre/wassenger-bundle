<?php

declare(strict_types=1);

namespace Nachoaguirre\WassengerBundle\Tests\Unit\Provider;

use Nachoaguirre\WassengerBundle\Exception\WassengerApiException;
use Nachoaguirre\WassengerBundle\Model\NumberValidation;
use Nachoaguirre\WassengerBundle\Provider\WassengerProvider;
use Nachoaguirre\WassengerBundle\Service\PhoneNumberNormalizer;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class WassengerProviderTest extends TestCase
{
    private PhoneNumberNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new PhoneNumberNormalizer();
    }

    private function makeProvider(MockResponse|array $responses): WassengerProvider
    {
        $client = new MockHttpClient(is_array($responses) ? $responses : [$responses]);

        return new WassengerProvider($client, $this->normalizer, 'test-api-key', 'test-device-id');
    }

    // --- sendMessage() ---

    public function testSendMessagePostsCorrectPayload(): void
    {
        $response = new MockResponse('{}', ['http_code' => 200]);
        $provider = $this->makeProvider($response);

        $provider->sendMessage('+5491112345678', 'Hola mundo');

        self::assertSame('POST', $response->getRequestMethod());
        self::assertStringContainsString('/messages', $response->getRequestUrl());

        $body = json_decode($response->getRequestOptions()['body'], true);
        self::assertSame('+5491112345678', $body['phone']);
        self::assertSame('Hola mundo', $body['message']);
        self::assertSame('test-device-id', $body['device']);
    }

    public function testSendMessageNormalizesPhoneNumber(): void
    {
        $response = new MockResponse('{}', ['http_code' => 200]);
        $provider = $this->makeProvider($response);

        $provider->sendMessage('5491112345678', 'Hi');

        $body = json_decode($response->getRequestOptions()['body'], true);
        self::assertSame('+5491112345678', $body['phone']);
    }

    public function testSendMessageSendsApiKeyHeader(): void
    {
        $response = new MockResponse('{}', ['http_code' => 200]);
        $provider = $this->makeProvider($response);

        $provider->sendMessage('+5491112345678', 'test');

        $headers = $response->getRequestOptions()['headers'];
        self::assertContains('Token: test-api-key', $headers);
    }

    // --- sendTemplate() ---

    public function testSendTemplatePostsTemplatePayload(): void
    {
        $response = new MockResponse('{}', ['http_code' => 200]);
        $provider = $this->makeProvider($response);

        $provider->sendTemplate('+5491112345678', 'welcome_message');

        $body = json_decode($response->getRequestOptions()['body'], true);
        self::assertSame('welcome_message', $body['template']['name']);
        self::assertTrue($body['live']);
    }

    // --- validateNumber() ---

    public function testValidateNumberReturnsFalseForInvalidLocalFormat(): void
    {
        $provider = $this->makeProvider([]);

        $result = $provider->validateNumber('+123');

        self::assertInstanceOf(NumberValidation::class, $result);
        self::assertFalse($result->exists);
        self::assertSame('Invalid local format', $result->errorMessage);
        self::assertSame(400, $result->statusCode);
    }

    public function testValidateNumberReturnsSuccessOnExistingNumber(): void
    {
        $apiResponse = [
            'exists' => true,
            'wid' => '5491112345678@c.us',
            'phone' => '+5491112345678',
            'isBusiness' => false,
            'country' => ['name' => 'Argentina', 'code' => 'AR'],
        ];
        $response = new MockResponse(json_encode($apiResponse), ['http_code' => 200]);
        $provider = $this->makeProvider($response);

        $result = $provider->validateNumber('+5491112345678');

        self::assertTrue($result->exists);
        self::assertSame('5491112345678@c.us', $result->wid);
        self::assertSame('+5491112345678', $result->formattedPhone);
        self::assertFalse($result->isBusiness);
        self::assertSame('AR', $result->countryData['code']);
    }

    public function testValidateNumberReturnsFalseOnNonExistingNumber(): void
    {
        $apiResponse = ['exists' => false];
        $response = new MockResponse(json_encode($apiResponse), ['http_code' => 200]);
        $provider = $this->makeProvider($response);

        $result = $provider->validateNumber('+5491199999999');

        self::assertFalse($result->exists);
        self::assertNull($result->wid);
    }

    public function testValidateNumberHandlesApiClientError(): void
    {
        $errorBody = json_encode(['message' => 'Phone not found']);
        $response = new MockResponse($errorBody, ['http_code' => 404]);
        $provider = $this->makeProvider($response);

        $result = $provider->validateNumber('+5491112345678');

        self::assertFalse($result->exists);
        self::assertSame('Phone not found', $result->errorMessage);
        self::assertSame(404, $result->statusCode);
    }

    public function testValidateNumberHandlesNetworkException(): void
    {
        $client = new MockHttpClient(function (): never {
            throw new RuntimeException('Network timeout');
        });
        $provider = new WassengerProvider($client, $this->normalizer, 'key', 'device');

        $result = $provider->validateNumber('+5491112345678');

        self::assertFalse($result->exists);
        self::assertSame('Network timeout', $result->errorMessage);
        self::assertSame(500, $result->statusCode);
    }

    // --- Error paths para sendMessage y sendTemplate ---

    public function testSendMessageThrowsWassengerApiExceptionOnClientError(): void
    {
        $response = new MockResponse('{"error":"unauthorized"}', ['http_code' => 401]);
        $provider = $this->makeProvider($response);

        $this->expectException(WassengerApiException::class);

        $provider->sendMessage('+5491112345678', 'test');
    }

    public function testSendMessageThrowsWassengerApiExceptionOnServerError(): void
    {
        $response = new MockResponse('{"error":"internal"}', ['http_code' => 500]);
        $provider = $this->makeProvider($response);

        $this->expectException(WassengerApiException::class);

        $provider->sendMessage('+5491112345678', 'test');
    }

    public function testSendMessageThrowsOnNetworkFailure(): void
    {
        $client = new MockHttpClient(function (): never {
            throw new RuntimeException('Connection refused');
        });
        $provider = new WassengerProvider($client, $this->normalizer, 'key', 'device');

        $this->expectException(WassengerApiException::class);
        $this->expectExceptionMessage('Connection refused');

        $provider->sendMessage('+5491112345678', 'test');
    }

    public function testSendTemplateThrowsWassengerApiExceptionOnClientError(): void
    {
        $response = new MockResponse('{"error":"bad request"}', ['http_code' => 400]);
        $provider = $this->makeProvider($response);

        $this->expectException(WassengerApiException::class);

        $provider->sendTemplate('+5491112345678', 'welcome');
    }

    public function testSendTemplateIncludesParamsInPayload(): void
    {
        $response = new MockResponse('{}', ['http_code' => 200]);
        $provider = $this->makeProvider($response);

        $provider->sendTemplate('+5491112345678', 'welcome', ['name' => 'John']);

        $body = json_decode($response->getRequestOptions()['body'], true);
        self::assertSame('John', $body['template']['params']['name']);
    }

    public function testSendTemplateOmitsParamsKeyWhenEmpty(): void
    {
        $response = new MockResponse('{}', ['http_code' => 200]);
        $provider = $this->makeProvider($response);

        $provider->sendTemplate('+5491112345678', 'welcome');

        $body = json_decode($response->getRequestOptions()['body'], true);
        self::assertArrayNotHasKey('params', $body['template']);
    }
}
