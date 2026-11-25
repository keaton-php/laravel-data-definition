<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Exceptions;

/** Something that is not a usable payload, key or list of changes was handed in. */
class InvalidData extends DataDefinitionException
{
    public static function payload(string $definition, mixed $data): self
    {
        return new self("{$definition}::make() expects an array, an Arrayable or an object, got ".get_debug_type($data).'.');
    }

    public static function key(string $definition, mixed $key): self
    {
        return new self("{$definition}: a key must be a non-empty string with no empty dot segment, got ".(is_string($key) ? "'{$key}'" : get_debug_type($key)).'.');
    }

    public static function blockedPath(string $definition, string $path, string $blockedAt): self
    {
        return new self("{$definition}::with() cannot set '{$path}': '{$blockedAt}' holds a value that is not an array.");
    }
}
