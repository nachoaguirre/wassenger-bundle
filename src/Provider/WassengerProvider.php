<?php

declare(strict_types=1);

namespace Nachoaguirre\WassengerBundle\Provider;

use Exception;
use Nachoaguirre\WassengerBundle\Contract\ProviderInterface;
use Nachoaguirre\WassengerBundle\Model\NumberValidation;
use Nachoaguirre\WassengerBundle\Service\PhoneNumberNormalizer;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WassengerProvider implements ProviderInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private PhoneNumberNormalizer $normalizer,
        private string $apiKey,
        private string $deviceId,
    ) {
    }

    public function sendMessage(string $recipientId, string $message, array $options = []): void
    {
        $phone = $this->normalizer->normalize($recipientId);

        $this->httpClient->request('POST', 'https://api.wassenger.com/v1/messages', [
            'headers' => [
                'Token' => $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'phone' => $phone,
                'message' => $message,
                'device' => $this->deviceId,
            ],
        ]);
    }

    public function sendTemplate(string $recipientId, string $templateName, array $params = []): void
    {
        $this->httpClient->request('POST', 'https://api.wassenger.com/v1/messages', [
            'headers' => [
                'Token' => $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'phone' => $recipientId,
                'template' => [
                    'name' => $templateName,
                ],
                'live' => true,
            ],
        ]);
    }

    public function validateNumber(string $phone): NumberValidation
    {
        $phone = $this->normalizer->normalize($phone);

        if (!$this->normalizer->isValidFormat($phone)) {
            return new NumberValidation(exists: false, errorMessage: 'Invalid local format');
        }

        try {
            $response = $this->httpClient->request('POST', 'https://api.wassenger.com/v1/numbers/exists', [
                'headers' => [
                    'Token' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => ['phone' => $phone],
            ]);

            $data = $response->toArray();

            return new NumberValidation(
                exists: $data['exists'] ?? false,
                wid: $data['wid'] ?? null,
                formattedPhone: $data['phone'] ?? $phone,
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
        } catch (Exception $e) {
            return new NumberValidation(
                exists: false,
                errorMessage: $e->getMessage(),
                statusCode: 500
            );
        }
    }
}
