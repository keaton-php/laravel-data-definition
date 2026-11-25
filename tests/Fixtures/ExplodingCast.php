<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Tests\Fixtures;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/** @implements CastsAttributes<never, never> */
class ExplodingCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): never
    {
        throw new RuntimeException('the cast itself failed');
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): never
    {
        throw new RuntimeException('the cast itself failed');
    }
}
