<?php

declare(strict_types=1);

namespace Nachoaguirre\WassengerBundle\Contract;

interface WhatsappRecipientInterface
{
    public function getWhatsappIdentifier(): string;
}
