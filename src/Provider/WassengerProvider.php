<?php

declare(strict_types=1);

namespace Nachoaguirre\WassengerBundle\Provider;

use Nachoaguirre\WassengerBundle\Contract\ProviderInterface;
use Nachoaguirre\WassengerBundle\Exception\WassengerApiException;
use Nachoaguirre\WassengerBundle\Model\NumberValidation;
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

    public function sendMessage(string $recipientId, string $message, array $options = []): void
    {
        $phone = $this->normalizer->normalize($recipientId);

        try {
            $response = $this->httpClient->request('POST', self::BASE_URL . '/messages', [
                'headers' => ['Token' => $this->apiKey],
                'json' => array_merge([
                    'phone' => $phone,
                    'message' => $message,
                    'device' => $this->deviceId,
                ], $options),
            ]);

            $response->getContent();
        } catch (ClientException $e) {
            throw new WassengerApiException(sprintf('API rejected message (HTTP %d): %s', $e->getResponse()->getStatusCode(), $e->getMessage()), previous: $e);
        } catch (Throwable $e) {
            throw new WassengerApiException('Failed to send message: ' . $e->getMessage(), previous: $e);
        }
    }

    public function sendTemplate(string $recipientId, string $templateName, array $params = []): void
    {
        $phone = $this->normalizer->normalize($recipientId);

        $template = ['name' => $templateName];

        if ($params !== []) {
            $template['params'] = $params;
        }

        try {
            $response = $this->httpClient->request('POST', self::BASE_URL . '/messages', [
                'headers' => ['Token' => $this->apiKey],
                'json' => [
                    'phone' => $phone,
                    'template' => $template,
                    'live' => true,
                ],
            ]);

            $response->getContent();
        } catch (ClientException $e) {
            throw new WassengerApiException(sprintf('API rejected template (HTTP %d): %s', $e->getResponse()->getStatusCode(), $e->getMessage()), previous: $e);
        } catch (Throwable $e) {
            throw new WassengerApiException('Failed to send template: ' . $e->getMessage(), previous: $e);
        }
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
}
