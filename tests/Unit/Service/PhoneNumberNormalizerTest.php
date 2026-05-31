<?php

declare(strict_types=1);

namespace Nachoaguirre\WassengerBundle\Tests\Unit\Service;

use Nachoaguirre\WassengerBundle\Service\PhoneNumberNormalizer;
use PHPUnit\Framework\TestCase;

final class PhoneNumberNormalizerTest extends TestCase
{
    private PhoneNumberNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new PhoneNumberNormalizer();
    }

    // --- normalize() ---

    public function testNormalizeAddsPlusToLongNumber(): void
    {
        self::assertSame('+5491112345678', $this->normalizer->normalize('5491112345678'));
    }

    public function testNormalizePreservesExistingPlus(): void
    {
        self::assertSame('+5491112345678', $this->normalizer->normalize('+5491112345678'));
    }

    public function testNormalizeStripsSpacesAndDashes(): void
    {
        self::assertSame('+5491112345678', $this->normalizer->normalize('+549 11 1234-5678'));
    }

    public function testNormalizeStripsParentheses(): void
    {
        self::assertSame('+15551234567', $this->normalizer->normalize('+1 (555) 123-4567'));
    }

    public function testNormalizeDoesNotAddPlusToShortNumber(): void
    {
        // 8 chars or fewer without '+' → no plus added
        self::assertSame('12345678', $this->normalizer->normalize('12345678'));
    }

    // --- isValidFormat() ---

    public function testIsValidFormatReturnsTrueForValidE164(): void
    {
        self::assertTrue($this->normalizer->isValidFormat('+5491112345678'));
    }

    public function testIsValidFormatReturnsTrueForNumberWithoutPlusWhenLongEnough(): void
    {
        // normalize() will add '+', result is '+5491112345678' → valid
        self::assertTrue($this->normalizer->isValidFormat('5491112345678'));
    }

    public function testIsValidFormatReturnsFalseForTooShortNumber(): void
    {
        self::assertFalse($this->normalizer->isValidFormat('+123456'));
    }

    public function testIsValidFormatReturnsFalseForTooLongNumber(): void
    {
        self::assertFalse($this->normalizer->isValidFormat('+1234567890123456'));
    }

    public function testIsValidFormatReturnsFalseForNumberStartingWithZero(): void
    {
        // E.164 country codes never start with 0
        self::assertFalse($this->normalizer->isValidFormat('+05491112345678'));
    }

    public function testIsValidFormatReturnsFalseForLetters(): void
    {
        self::assertFalse($this->normalizer->isValidFormat('not-a-phone'));
    }
}
