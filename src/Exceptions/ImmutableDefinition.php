<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Exceptions;

/** Something tried to change a definition. Use with() to get a changed copy. */
class ImmutableDefinition extends DataDefinitionException
{
    public static function write(string $definition, string $how): self
    {
        return new self("{$definition} is immutable: {$how} is not allowed. Use with() to get a changed copy.");
    }
}
