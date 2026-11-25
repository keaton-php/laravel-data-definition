<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Tests\Fixtures;

use Keaton\DataDefinition\DataDefinition;
use stdClass;

class BadCollectionDefinition extends DataDefinition
{
    protected static string $collectionClass = stdClass::class;
}
