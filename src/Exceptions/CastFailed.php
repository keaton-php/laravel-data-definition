<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Exceptions;

/** A value is present in the payload but cannot be cast to the declared type. */
class CastFailed extends DataDefinitionException
{
    public static function value(string $definition, string $key, string $type, mixed $value, ?string $reason = null): self
    {
        return new self("{$definition}: cannot cast '{$key}' to {$type}: got ".self::describe($value).($reason === null ? '.' : " ({$reason})."));
    }

    public static function describe(mixed $value): string
    {
        return match (true) {
            is_string($value) => "'".(strlen($value) > 40 ? substr($value, 0, 40).'...' : $value)."'",
            is_int($value) => (string) $value,
            is_float($value) => var_export($value, true),
            is_bool($value) => $value ? 'true' : 'false',
            default => get_debug_type($value),
        };
    }
}
