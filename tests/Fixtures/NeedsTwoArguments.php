<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Tests\Fixtures;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/** @implements CastsAttributes<string, never> */
class NeedsTwoArguments implements CastsAttributes
{
    public function __construct(
        public readonly string $first,
        public readonly string $second,
    ) {}

    public function get(Model $model, string $key, mixed $value, array $attributes): string
    {
        return $this->first.$this->second;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): never
    {
        throw new LogicException('set() must never be called.');
    }
}
