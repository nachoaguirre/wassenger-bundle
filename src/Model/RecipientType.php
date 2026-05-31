<?php

declare(strict_types=1);

namespace Nachoaguirre\WassengerBundle\Model;

enum RecipientType: string
{
    case INDIVIDUAL = 'individual';
    case GROUP = 'group';
}
