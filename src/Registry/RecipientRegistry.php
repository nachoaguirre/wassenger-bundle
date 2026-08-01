<?php

declare(strict_types=1);

namespace Nachoaguirre\WassengerBundle\Registry;

use InvalidArgumentException;
use Nachoaguirre\WassengerBundle\Model\Recipient;
use Nachoaguirre\WassengerBundle\Service\PhoneNumberNormalizer;

class RecipientRegistry
{
    private array $recipients = [];

    public function __construct(private readonly PhoneNumberNormalizer $normalizer)
    {
    }

    public function addRecipient(Recipient $recipient): void
    {
        $this->recipients[$recipient->alias] = $recipient;
    }

    public function get(string $alias): ?Recipient
    {
        return $this->recipients[$alias] ?? null;
    }

    public function getActive(string $alias): ?Recipient
    {
        $recipient = $this->get($alias);

        return $recipient?->enabled ? $recipient : null;
    }

    public function resolveIdentifier(string $aliasOrPhone): string
    {
        $recipient = $this->get($aliasOrPhone);

        if ($recipient !== null) {
            return $recipient->identifier;
        }

        if ($this->normalizer->isGroupId($aliasOrPhone) || $this->normalizer->isValidFormat($aliasOrPhone)) {
            return $aliasOrPhone;
        }

        throw new InvalidArgumentException(\sprintf('The recipient alias "%s" was not found in the registry.', $aliasOrPhone));
    }

    /** @return array<string, Recipient> */
    public function all(): array
    {
        return $this->recipients;
    }

    public function count(): int
    {
        return \count($this->recipients);
    }
}
