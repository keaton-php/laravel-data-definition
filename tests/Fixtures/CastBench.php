<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Tests\Fixtures;

use Keaton\DataDefinition\DataDefinition;

/** One alias per built-in cast; each alias reads the payload key named after its cast. */
class CastBench extends DataDefinition
{
    protected array $aliases = [
        'asInt' => 'int',
        'asInteger' => 'integer',
        'asFloat' => 'float',
        'asDouble' => 'double',
        'asString' => 'string',
        'asBool' => 'bool',
        'asBoolean' => 'boolean',
        'asArray' => 'array',
        'asCollection' => 'collection',
        'asDate' => 'date',
        'asDatetime' => 'datetime',
        'asJson' => 'json',
    ];

    protected array $casts = [
        'asInt' => 'int',
        'asInteger' => 'integer',
        'asFloat' => 'float',
        'asDouble' => 'double',
        'asString' => 'string',
        'asBool' => 'bool',
        'asBoolean' => 'boolean',
        'asArray' => 'array',
        'asCollection' => 'collection',
        'asDate' => 'date',
        'asDatetime' => 'datetime',
        'asJson' => 'json',
    ];
}
