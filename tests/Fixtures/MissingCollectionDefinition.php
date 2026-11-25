<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Tests\Fixtures;

use Keaton\DataDefinition\DataDefinition;

class MissingCollectionDefinition extends DataDefinition
{
    protected static string $collectionClass = 'No\\Such\\Collection';
}
