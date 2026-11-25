<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Exceptions;

/** A definition class is declared wrongly: a bad alias, or a bad $collectionClass. */
class InvalidDefinition extends DataDefinitionException
{
    public static function aliasName(string $definition, mixed $alias): self
    {
        $shown = is_string($alias) ? "'{$alias}'" : get_debug_type($alias);

        return new self("{$definition}: alias names must be non-empty strings without a dot, got {$shown}.");
    }

    public static function aliasPath(string $definition, string $alias, mixed $path): self
    {
        $shown = is_string($path) ? "'{$path}'" : get_debug_type($path);

        return new self("{$definition}: the path for alias '{$alias}' must be a non-empty string with no empty segments (no leading, trailing or doubled dot), got {$shown}.");
    }

    public static function aliasShadows(string $definition, string $alias): self
    {
        return new self("{$definition}: the alias '{$alias}' would shadow a method or property of the class itself. Pick another name.");
    }

    public static function collectionClass(string $definition, string $class): self
    {
        return new self("{$definition}: \$collectionClass '{$class}' must be Illuminate\\Support\\Collection or a class that extends it, can be built, and takes the items array as its one constructor argument.");
    }
}
