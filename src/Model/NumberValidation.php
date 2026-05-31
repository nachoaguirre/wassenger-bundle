<?php

declare(strict_types=1);

namespace Nachoaguirre\WassengerBundle\Model;

class NumberValidation
{
    public function __construct(
        public readonly bool $exists,
        public readonly ?string $wid = null,
        public readonly ?string $formattedPhone = null,
        public readonly bool $isBusiness = false,
        public readonly array $countryData = [],
        public readonly ?string $errorMessage = null,
        public readonly int $statusCode = 200,
    ) {
    }
}
