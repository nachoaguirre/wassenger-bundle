<?php

declare(strict_types=1);

namespace Nachoaguirre\WassengerBundle\Service;

class PhoneNumberNormalizer
{
    public function normalize(string $phone): string
    {
        $clean = preg_replace('/[^\d+]/', '', $phone);

        if ($clean === '' || $clean === '+') {
            return '';
        }

        if (!str_starts_with($clean, '+') && \strlen($clean) > 8) {
            $clean = "+$clean";
        }

        return $clean;
    }

    public function isValidFormat(string $phone): bool
    {
        $normalized = $this->normalize($phone);

        return preg_match('/^\+[1-9]\d{7,14}$/', $normalized) === 1;
    }
}
