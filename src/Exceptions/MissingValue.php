<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Exceptions;

/** getOrFail() was asked for a path the payload does not contain. */
class MissingValue extends DataDefinitionException
{
    public static function at(string $definition, string $key, string $path): self
    {
        return new self($key === $path
            ? "{$definition}: the payload has nothing at '{$path}'."
            : "{$definition}: the payload has nothing at '{$path}' (alias '{$key}').");
    }
}
