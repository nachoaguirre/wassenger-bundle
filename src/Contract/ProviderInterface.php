<?php

declare(strict_types=1);

namespace Nachoaguirre\WassengerBundle\Contract;

use Nachoaguirre\WassengerBundle\Model\NumberValidation;

interface ProviderInterface
{
    public function sendMessage(string $recipientId, string $message, array $options = []): void;

    public function sendTemplate(string $recipientId, string $templateName, array $params = []): void;

    public function validateNumber(string $phone): NumberValidation;
}
