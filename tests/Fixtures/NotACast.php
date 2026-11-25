<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Tests\Fixtures;

class NotACast
{
    public function __construct(public readonly mixed $value = null) {}
}
