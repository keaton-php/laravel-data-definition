<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Tests\Fixtures;

use Keaton\DataDefinition\DataDefinition;

class Member extends DataDefinition
{
    protected static string $collectionClass = MemberCollection::class;

    protected array $aliases = [
        'name' => 'full_name',
        'seats' => 'seats',
    ];

    protected array $casts = [
        'seats' => 'int',
    ];
}
