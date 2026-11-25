<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Exceptions;

/** A cast is declared in a way the package cannot use. Thrown when the definition is built, before any value is read. */
class InvalidCast extends DataDefinitionException
{
    public static function key(string $definition, mixed $key): self
    {
        return new self("{$definition}: cast keys must be non-empty strings (an alias or a clean dot-path with no empty segment), got ".(is_string($key) ? "'{$key}'" : get_debug_type($key)).'.');
    }

    public static function unknown(string $definition, string $key, string $type): self
    {
        return new self("{$definition}: the cast '{$type}' for '{$key}' is not a built-in cast, a CastsAttributes class, a backed enum or a DataDefinition.");
    }

    public static function unsupportedClass(string $definition, string $key, string $type): self
    {
        return new self("{$definition}: '{$type}' for '{$key}' is a class this package will not construct for you. Wrap it in a CastsAttributes class, a backed enum or a DataDefinition.");
    }

    public static function pureEnum(string $definition, string $key, string $type): self
    {
        return new self("{$definition}: '{$type}' for '{$key}' is a pure enum. Only backed enums can be cast to, because a payload value has to map to a case.");
    }

    public static function badList(string $definition, string $key): self
    {
        return new self("{$definition}: a list cast for '{$key}' must be written as [SomeDefinition::class], one DataDefinition class in an array.");
    }

    public static function badInstance(string $definition, string $key, object $cast): self
    {
        return new self("{$definition}: the cast object for '{$key}' is a ".$cast::class.', which does not implement CastsAttributes.');
    }

    public static function constructorArguments(string $definition, string $key, string $class, int $given, int $required): self
    {
        return new self("{$definition}: '{$class}' for '{$key}' is given {$given} constructor arguments but needs ".($required > $given ? "at least {$required}" : 'fewer').". Write them as '{$class}:one,two', or pass an instance.");
    }

    public static function badType(string $definition, string $key, mixed $type): self
    {
        return new self("{$definition}: the cast for '{$key}' must be a string, a CastsAttributes object or [SomeDefinition::class], got ".get_debug_type($type).'.');
    }
}
