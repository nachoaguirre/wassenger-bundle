<?php

declare(strict_types=1);

namespace Nachoaguirre\WassengerBundle\Model;

class SentMessage
{
    public function __construct(
        public readonly ?string $id = null,
        public readonly ?string $status = null,
        public readonly ?string $deliverAt = null,
        public readonly array $raw = [],
    ) {
    }

    public static function fromApiResponse(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            status: $data['status'] ?? null,
            deliverAt: $data['deliverAt'] ?? null,
            raw: $data,
        );
    }
}
