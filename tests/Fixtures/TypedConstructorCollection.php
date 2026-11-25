<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Tests\Fixtures;

use Illuminate\Support\Collection;

/** @extends Collection<array-key, mixed> */
class TypedConstructorCollection extends Collection
{
    public function __construct(public readonly string $label = 'none')
    {
        parent::__construct([]);
    }
}
