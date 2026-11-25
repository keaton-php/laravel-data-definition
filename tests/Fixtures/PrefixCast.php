<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Tests\Fixtures;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/** @implements CastsAttributes<string, never> */
class PrefixCast implements CastsAttributes
{
    public function __construct(
        private readonly string $prefix,
        private readonly string $suffix = '',
    ) {}

    public function get(Model $model, string $key, mixed $value, array $attributes): string
    {
        return $this->prefix.$value.$this->suffix;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): never
    {
        throw new LogicException('set() must never be called.');
    }
}
