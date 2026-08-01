<?php

declare(strict_types=1);

namespace Nachoaguirre\WassengerBundle\Provider;

use DateTimeInterface;
use Nachoaguirre\WassengerBundle\Contract\ProviderInterface;
use Nachoaguirre\WassengerBundle\Exception\WassengerApiException;
use Nachoaguirre\WassengerBundle\Model\NumberValidation;
use Nachoaguirre\WassengerBundle\Model\SentMessage;
use Nachoaguirre\WassengerBundle\Service\PhoneNumberNormalizer;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class WassengerProvider implements ProviderInterface
{
    private const BASE_URL = 'https://api.wassenger.com/v1';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly PhoneNumberNormalizer $normalizer,
        private readonly string $apiKey,
        private readonly string $deviceId,
    ) {
    }

    public function sendMessage(string $recipientId, string $message, array $options = []): SentMessage
    {
        $payload = array_merge(
            $this->recipientPayload($recipientId),
            ['message' => $message, 'device' => $this->deviceId],
            $options,
        );

        return $this->postMessage($payload, 'message');
    }

    public function sendMedia(string $recipientId, string $mediaUrl, ?string $caption = null, array $options = []): SentMessage
    {
        $payload = array_merge(
            $this->recipientPayload($recipientId),
            [
                'media' => ['url' => $mediaUrl],
                'device' => $this->deviceId,
            ],
            $options,
        );

        if ($caption !== null && $caption !== '') {
            $payload['message'] = $caption;
        }

        return $this->postMessage($payload, 'media message');
    }

    public function scheduleMessage(string $recipientId, string $message, DateTimeInterface $deliverAt, array $options = []): SentMessage
    {
        $options['deliverAt'] = $deliverAt->format(DateTimeInterface::ATOM);

        return $this->sendMessage($recipientId, $message, $options);
    }

    public function sendTemplate(string $recipientId, string $templateName, array $params = []): SentMessage
    {
        $template = ['name' => $templateName];

        if ($params !== []) {
            $template['params'] = $params;
        }

        $payload = array_merge($this->recipientPayload($recipientId), [
            'template' => $template,
            'live' => true,
        ]);

        return $this->postMessage($payload, 'template');
    }

    public function validateNumber(string $phone): NumberValidation
    {
        $normalized = $this->normalizer->normalize($phone);

        if (!$this->normalizer->isValidFormat($normalized)) {
            return new NumberValidation(exists: false, errorMessage: 'Invalid local format', statusCode: 400);
        }

        try {
            $response = $this->httpClient->request('POST', self::BASE_URL . '/numbers/exists', [
                'headers' => ['Token' => $this->apiKey],
                'json' => ['phone' => $normalized],
            ]);

            $data = $response->toArray();

            return new NumberValidation(
                exists: $data['exists'] ?? false,
                wid: $data['wid'] ?? null,
                formattedPhone: $data['phone'] ?? $normalized,
                isBusiness: $data['isBusiness'] ?? false,
                countryData: $data['country'] ?? []
            );
        } catch (ClientException $e) {
            $data = $e->getResponse()->toArray(false);

            return new NumberValidation(
                exists: false,
                errorMessage: $data['message'] ?? 'Unknown error',
                statusCode: $e->getResponse()->getStatusCode()
            );
        } catch (Throwable $e) {
            return new NumberValidation(
                exists: false,
                errorMessage: $e->getMessage(),
                statusCode: 500
            );
        }
    }

    /**
     * Group chats are addressed by their WhatsApp ID (e.g. "1234567890@g.us")
     * via the "group" field; individual recipients use a normalized "phone".
     */
    private function recipientPayload(string $recipientId): array
    {
        if ($this->normalizer->isGroupId($recipientId)) {
            return ['group' => trim($recipientId)];
        }

        return ['phone' => $this->normalizer->normalize($recipientId)];
    }

    private function postMessage(array $payload, string $context): SentMessage
    {
        try {
            $response = $this->httpClient->request('POST', self::BASE_URL . '/messages', [
                'headers' => ['Token' => $this->apiKey],
                'json' => $payload,
            ]);

            $data = json_decode($response->getContent(), true);

            return SentMessage::fromApiResponse(\is_array($data) ? $data : []);
        } catch (ClientException $e) {
            throw new WassengerApiException(sprintf('API rejected %s (HTTP %d): %s', $context, $e->getResponse()->getStatusCode(), $e->getMessage()), previous: $e);
        } catch (Throwable $e) {
            throw new WassengerApiException(sprintf('Failed to send %s: %s', $context, $e->getMessage()), previous: $e);
        }
    }
}
