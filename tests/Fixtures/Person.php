<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Tests\Fixtures;

use Keaton\DataDefinition\DataDefinition;

class Person extends DataDefinition
{
    protected array $aliases = [
        'name' => 'profile.name',
        'email' => 'contact.primary_email',
        'age' => 'profile.age',
        'joined' => 'created',
    ];

    protected array $casts = [
        'age' => 'int',
        'joined' => 'datetime',
    ];

    /** @return array<string, mixed> */
    public static function sample(): array
    {
        return [
            'profile' => ['name' => 'Ana Costa', 'age' => '41', 'nickname' => null],
            'contact' => ['primary_email' => 'ana@example.com'],
            'created' => '2026-03-02T09:15:00Z',
        ];
    }
}
