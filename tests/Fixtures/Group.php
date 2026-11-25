<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Tests\Fixtures;

use Keaton\DataDefinition\DataDefinition;

class Group extends DataDefinition
{
    protected array $aliases = [
        'lead' => 'lead',
        'contacts' => 'people',
        'members' => 'staff',
    ];

    protected array $casts = [
        'lead' => Contact::class,
        'contacts' => [Contact::class],
        'members' => [Member::class],
    ];
}
