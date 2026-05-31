<?php

declare(strict_types=1);

namespace Nachoaguirre\WassengerBundle\Service;

class PhoneNumberNormalizer
{
    public function normalize(string $phone): string
    {
        $clean = preg_replace('/[^\d+]/', '', $phone);

        if (!str_starts_with($clean, '+') && strlen($clean) > 8) {
            $clean = '+' . $clean;
        }

        return $clean;
    }

    public function isValidFormat(string $phone): bool
    {
        $normalized = $this->normalize($phone);
        $length = strlen($normalized);

        return $length >= 8 && $length <= 16 && str_starts_with($normalized, '+');
    }
}
