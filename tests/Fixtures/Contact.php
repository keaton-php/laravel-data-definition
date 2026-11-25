<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Tests\Fixtures;

use Keaton\DataDefinition\DataDefinition;

class Contact extends DataDefinition
{
    protected array $aliases = [
        'name' => 'full_name',
        'email' => 'mail',
    ];
}
