<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Tests\Fixtures;

use Keaton\DataDefinition\DataDefinition;

class AbstractCollectionDefinition extends DataDefinition
{
    protected static string $collectionClass = AbstractCollection::class;
}
