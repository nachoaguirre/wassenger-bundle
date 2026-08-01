<?php

declare(strict_types=1);

namespace Nachoaguirre\WassengerBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class NachoaguirreWassengerBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
