<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Tests\Fixtures;

use Keaton\DataDefinition\DataDefinition;

/**
 * A definition whose aliases and casts come from the payload's reserved '__config' key, set up
 * in boot(). It lets one test build many differently-shaped definitions without a class each.
 */
class Configurable extends DataDefinition
{
    /**
     * @param  array<array-key, mixed>  $aliases
     * @param  array<array-key, mixed>  $casts
     * @param  array<array-key, mixed>  $payload
     */
    public static function define(array $aliases = [], array $casts = [], array $payload = []): static
    {
        return static::make($payload + ['__config' => ['aliases' => $aliases, 'casts' => $casts]]);
    }

    protected function boot(): void
    {
        $config = $this->data()['__config'] ?? [];

        $this->aliases = $config['aliases'] ?? [];
        $this->casts = $config['casts'] ?? [];
    }
}
