<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Tests\Fixtures;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Returns exactly what get() received, so a test can assert on it.
 *
 * @implements CastsAttributes<array<int, mixed>, never>
 */
class ArgsCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): array
    {
        return [$model::class, $key, $value, $attributes, $model->getAttributes()];
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): never
    {
        throw new LogicException('set() must never be called.');
    }
}
