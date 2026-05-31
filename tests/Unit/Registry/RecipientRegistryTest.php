<?php

declare(strict_types=1);

namespace Nachoaguirre\WassengerBundle\Tests\Unit\Registry;

use InvalidArgumentException;
use Nachoaguirre\WassengerBundle\Model\Recipient;
use Nachoaguirre\WassengerBundle\Model\RecipientType;
use Nachoaguirre\WassengerBundle\Registry\RecipientRegistry;
use Nachoaguirre\WassengerBundle\Service\PhoneNumberNormalizer;
use PHPUnit\Framework\TestCase;

final class RecipientRegistryTest extends TestCase
{
    private RecipientRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new RecipientRegistry(new PhoneNumberNormalizer());
    }

    private function makeRecipient(
        string $identifier,
        string $alias,
        RecipientType $type = RecipientType::INDIVIDUAL,
        bool $enabled = true,
    ): Recipient {
        return new Recipient($identifier, $alias, $type, $enabled);
    }

    // --- get() ---

    public function testGetReturnsRegisteredRecipient(): void
    {
        $recipient = $this->makeRecipient('+5491112345678', 'john');
        $this->registry->addRecipient($recipient);

        self::assertSame($recipient, $this->registry->get('john'));
    }

    public function testGetReturnsNullForUnknownAlias(): void
    {
        self::assertNull($this->registry->get('nobody'));
    }

    // --- getActive() ---

    public function testGetActiveReturnsEnabledRecipient(): void
    {
        $recipient = $this->makeRecipient('+5491112345678', 'alice', enabled: true);
        $this->registry->addRecipient($recipient);

        self::assertSame($recipient, $this->registry->getActive('alice'));
    }

    public function testGetActiveReturnsNullForDisabledRecipient(): void
    {
        $recipient = $this->makeRecipient('+5491112345678', 'bob', enabled: false);
        $this->registry->addRecipient($recipient);

        self::assertNull($this->registry->getActive('bob'));
    }

    public function testGetActiveReturnsNullForUnknownAlias(): void
    {
        self::assertNull($this->registry->getActive('ghost'));
    }

    // --- resolveIdentifier() ---

    public function testResolveIdentifierReturnsPhoneForKnownAlias(): void
    {
        $recipient = $this->makeRecipient('+5491112345678', 'support');
        $this->registry->addRecipient($recipient);

        self::assertSame('+5491112345678', $this->registry->resolveIdentifier('support'));
    }

    public function testResolveIdentifierReturnsRawValueWhenItLooksLikeAPhone(): void
    {
        // Contains digits → treated as a direct phone number, no exception
        self::assertSame('+5491112345678', $this->registry->resolveIdentifier('+5491112345678'));
    }

    public function testResolveIdentifierThrowsWhenAliasNotFoundAndNoDigits(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"unknown-alias"');

        $this->registry->resolveIdentifier('unknown-alias');
    }

    public function testAddRecipientOverwritesPreviousWithSameAlias(): void
    {
        $first = $this->makeRecipient('+111', 'sales');
        $second = $this->makeRecipient('+222', 'sales');

        $this->registry->addRecipient($first);
        $this->registry->addRecipient($second);

        self::assertSame('+222', $this->registry->get('sales')?->identifier);
    }

    public function testGroupRecipientIsStoredCorrectly(): void
    {
        $group = $this->makeRecipient('group-id-xyz', 'team', RecipientType::GROUP);
        $this->registry->addRecipient($group);

        $found = $this->registry->get('team');

        self::assertNotNull($found);
        self::assertSame(RecipientType::GROUP, $found->type);
    }
}
