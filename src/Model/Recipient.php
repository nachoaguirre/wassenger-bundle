<?php

declare(strict_types=1);

namespace Nachoaguirre\WassengerBundle\Model;

class Recipient
{
    public function __construct(
        public readonly string $identifier,
        public readonly string $alias,
        public readonly string $type = 'individual', // 'individual' or 'group'
        public readonly bool $enabled = true,
    ) {
    }
}
