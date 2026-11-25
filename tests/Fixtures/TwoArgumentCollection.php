<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Tests\Fixtures;

use Illuminate\Support\Collection;

/** @extends Collection<array-key, mixed> */
class TwoArgumentCollection extends Collection
{
    /** @param  array<array-key, mixed>  $items */
    public function __construct(array $items, public readonly string $label)
    {
        parent::__construct($items);
    }
}
