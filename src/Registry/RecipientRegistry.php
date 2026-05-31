<?php

declare(strict_types=1);

namespace Nachoaguirre\WassengerBundle\Registry;

use Nachoaguirre\WassengerBundle\Model\Recipient;

class RecipientRegistry
{
    private array $recipients = [];

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

        return ($recipient && $recipient->enabled) ? $recipient : null;
    }

    public function resolveIdentifier(string $aliasOrPhone): string
    {
        $recipient = $this->get($aliasOrPhone);

        if ($recipient) {
            return $recipient->identifier;
        }

        return $aliasOrPhone;
    }
}
