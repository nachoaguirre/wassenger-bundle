<?php

declare(strict_types=1);

namespace Nachoaguirre\WassengerBundle\Contract;

use DateTimeInterface;
use Nachoaguirre\WassengerBundle\Model\NumberValidation;
use Nachoaguirre\WassengerBundle\Model\SentMessage;

interface ProviderInterface
{
    public function sendMessage(string $recipientId, string $message, array $options = []): SentMessage;

    public function sendMedia(string $recipientId, string $mediaUrl, ?string $caption = null, array $options = []): SentMessage;

    public function scheduleMessage(string $recipientId, string $message, DateTimeInterface $deliverAt, array $options = []): SentMessage;

    public function sendTemplate(string $recipientId, string $templateName, array $params = []): SentMessage;

    public function validateNumber(string $phone): NumberValidation;
}
