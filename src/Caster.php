<?php

declare(strict_types=1);

namespace Keaton\DataDefinition;

use BackedEnum;
use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use JsonException;
use Keaton\DataDefinition\Exceptions\CastFailed;
use Keaton\DataDefinition\Exceptions\InvalidCast;
use ReflectionClass;
use ReflectionEnum;
use Stringable;
use Throwable;

/**
 * Turns a raw payload value into the type a definition declares.
 *
 * A cast declaration is one of: a built-in name, a CastsAttributes class or object, a backed
 * enum, a DataDefinition class, or [DataDefinition::class] for a list of them. Anything else
 * is rejected when the definition is built. A value that cannot be cast throws; nothing is
 * quietly turned into 0, '' or null.
 *
 * @internal
 */
class Caster
{
    private const BUILT_IN = [
        'int', 'integer', 'float', 'double', 'string', 'bool', 'boolean',
        'array', 'collection', 'date', 'datetime', 'json',
    ];

    /** Checks that a declaration is usable. Throws InvalidCast if it is not. */
    public static function validate(string $definition, string $key, mixed $type): void
    {
        if ($type instanceof CastsAttributes) {
            return;
        }

        if (is_object($type)) {
            throw InvalidCast::badInstance($definition, $key, $type);
        }

        if (is_array($type)) {
            if (count($type) !== 1 || ! array_key_exists(0, $type) || ! is_string($type[0]) || ! self::isConcreteDefinition($type[0])) {
                throw InvalidCast::badList($definition, $key);
            }

            return;
        }

        if (! is_string($type)) {
            throw InvalidCast::badType($definition, $key, $type);
        }

        if (in_array($type, self::BUILT_IN, true)) {
            return;
        }

        [$class, $arguments] = self::split($type);

        if (! class_exists($class)) {
            throw InvalidCast::unknown($definition, $key, $type);
        }

        if (is_subclass_of($class, CastsAttributes::class)) {
            self::checkConstructor($definition, $key, $class, $arguments);

            return;
        }

        $usable = match (true) {
            $arguments !== [] => throw InvalidCast::unknown($definition, $key, $type),
            enum_exists($class) => is_subclass_of($class, BackedEnum::class) ? true : throw InvalidCast::pureEnum($definition, $key, $class),
            default => self::isConcreteDefinition($class),
        };

        if (! $usable) {
            throw InvalidCast::unsupportedClass($definition, $key, $class);
        }
    }

    /**
     * Casts a present, non-null value. The declaration was already checked by validate().
     *
     * @param  array<array-key, mixed>  $attributes  the whole raw payload
     */
    public static function cast(string $definition, string $key, mixed $type, mixed $value, array $attributes): mixed
    {
        if (! is_string($type) || in_array($type, self::BUILT_IN, true)) {
            return match (true) {
                $type instanceof CastsAttributes => self::callCast($type, $key, $value, $attributes),
                is_array($type) && is_string($type[0] ?? null) && is_subclass_of($type[0], DataDefinition::class) => self::toList($definition, $key, $type[0], $value),
                is_string($type) => self::builtIn($definition, $key, $type, $value),
                default => throw InvalidCast::badType($definition, $key, $type),
            };
        }

        [$class, $arguments] = self::split($type);

        return match (true) {
            is_subclass_of($class, CastsAttributes::class) => self::callCast(new $class(...$arguments), $key, $value, $attributes),
            is_subclass_of($class, BackedEnum::class) => self::toEnum($definition, $key, $class, $value),
            is_subclass_of($class, DataDefinition::class) => self::toDefinition($definition, $key, $class, $value),
            default => throw InvalidCast::unknown($definition, $key, $type),
        };
    }

    /**
     * 'Class:one,two' is Laravel's way to hand a cast constructor arguments, always as strings.
     *
     * @return array{0: string, 1: list<string>}
     */
    private static function split(string $type): array
    {
        $colon = strpos($type, ':');

        return $colon === false ? [$type, []] : [substr($type, 0, $colon), explode(',', substr($type, $colon + 1))];
    }

    /**
     * @param  class-string<CastsAttributes<mixed, mixed>>  $class
     * @param  list<string>  $arguments
     */
    private static function checkConstructor(string $definition, string $key, string $class, array $arguments): void
    {
        $reflection = new ReflectionClass($class);

        if (! $reflection->isInstantiable()) {
            throw InvalidCast::unsupportedClass($definition, $key, $class);
        }

        $constructor = $reflection->getConstructor();
        $required = $constructor?->getNumberOfRequiredParameters() ?? 0;
        $allowed = $constructor === null ? 0 : ($constructor->isVariadic() ? PHP_INT_MAX : $constructor->getNumberOfParameters());

        if (count($arguments) < $required || count($arguments) > $allowed) {
            throw InvalidCast::constructorArguments($definition, $key, $class, count($arguments), $required);
        }
    }

    private static function isConcreteDefinition(string $class): bool
    {
        return is_subclass_of($class, DataDefinition::class) && (new ReflectionClass($class))->isInstantiable();
    }

    private static function builtIn(string $definition, string $key, string $type, mixed $value): mixed
    {
        return match ($type) {
            'int', 'integer' => self::toInt($definition, $key, $type, $value),
            'float', 'double' => self::toFloat($definition, $key, $type, $value),
            'string' => self::toString($definition, $key, $type, $value),
            'bool', 'boolean' => self::toBool($definition, $key, $type, $value),
            'array' => is_array($value) ? $value : throw CastFailed::value($definition, $key, $type, $value, 'expected an array'),
            'collection' => is_array($value) ? new Collection($value) : throw CastFailed::value($definition, $key, $type, $value, 'expected an array'),
            'json' => self::toJson($definition, $key, $type, $value),
            'date' => self::toDate($definition, $key, $type, $value)->startOfDay(),
            'datetime' => self::toDate($definition, $key, $type, $value),
            default => throw InvalidCast::unknown($definition, $key, $type),
        };
    }

    private static function toInt(string $definition, string $key, string $type, mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            if (! is_finite($value)) {
                throw CastFailed::value($definition, $key, $type, $value, 'it is not a finite number');
            }

            if (floor($value) !== $value) {
                throw CastFailed::value($definition, $key, $type, $value, 'it has a fractional part');
            }

            if ($value < -9.2233720368547758E+18 || $value >= 9.2233720368547758E+18) {
                throw CastFailed::value($definition, $key, $type, $value, 'it is outside the integer range');
            }

            return (int) $value;
        }

        if (is_string($value)) {
            $int = filter_var($value, FILTER_VALIDATE_INT);

            if ($int !== false) {
                return $int;
            }
        }

        throw CastFailed::value($definition, $key, $type, $value, 'expected an integer or a plain integer string');
    }

    private static function toFloat(string $definition, string $key, string $type, mixed $value): float
    {
        $float = match (true) {
            is_int($value), is_float($value) => (float) $value,
            is_string($value) && is_numeric($value) => (float) $value,
            default => throw CastFailed::value($definition, $key, $type, $value, 'expected a number or a numeric string'),
        };

        return is_finite($float) ? $float : throw CastFailed::value($definition, $key, $type, $value, 'it is not a finite number');
    }

    private static function toString(string $definition, string $key, string $type, mixed $value): string
    {
        return match (true) {
            is_string($value) => $value,
            is_int($value), is_float($value), $value instanceof Stringable => (string) $value,
            default => throw CastFailed::value($definition, $key, $type, $value, 'expected a string, a number or a Stringable'),
        };
    }

    private static function toBool(string $definition, string $key, string $type, mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === 1 || $value === 0) {
            return $value === 1;
        }

        if (is_string($value)) {
            $word = strtolower($value);

            if (in_array($word, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }

            if (in_array($word, ['0', 'false', 'no', 'off'], true)) {
                return false;
            }
        }

        throw CastFailed::value($definition, $key, $type, $value, "expected true or false, 1 or 0, or one of '1', '0', 'true', 'false', 'yes', 'no', 'on', 'off' in any case");
    }

    /** @return array<array-key, mixed> */
    private static function toJson(string $definition, string $key, string $type, mixed $value): array
    {
        if (! is_string($value)) {
            throw CastFailed::value($definition, $key, $type, $value, 'expected a string holding JSON');
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw CastFailed::value($definition, $key, $type, $value, 'it is not valid JSON');
        }

        return is_array($decoded) ? $decoded : throw CastFailed::value($definition, $key, $type, $value, 'it is JSON, but not an object or a list');
    }

    private static function toDate(string $definition, string $key, string $type, mixed $value): Carbon
    {
        try {
            return match (true) {
                $value instanceof DateTimeInterface => Carbon::instance($value)->copy(),
                is_int($value) => Carbon::createFromTimestampUTC($value),
                is_string($value) && trim($value) !== '' => Carbon::parse($value),
                default => throw CastFailed::value($definition, $key, $type, $value, 'expected a date string, a unix timestamp or a DateTimeInterface'),
            };
        } catch (CastFailed $failed) {
            throw $failed;
        } catch (Throwable) {
            throw CastFailed::value($definition, $key, $type, $value, 'it is not a date Carbon can read');
        }
    }

    /**
     * @param  class-string<BackedEnum>  $enum
     */
    private static function toEnum(string $definition, string $key, string $enum, mixed $value): BackedEnum
    {
        $candidate = null;

        if ((string) (new ReflectionEnum($enum))->getBackingType() === 'int') {
            $candidate = is_int($value) ? $value : (is_string($value) && filter_var($value, FILTER_VALIDATE_INT) !== false ? (int) $value : null);
        } elseif (is_string($value)) {
            $candidate = $value;
        }

        $case = $candidate === null ? null : $enum::tryFrom($candidate);

        return $case ?? throw CastFailed::value($definition, $key, $enum, $value, 'not a value of that enum');
    }

    /**
     * @param  class-string<DataDefinition>  $class
     */
    private static function toDefinition(string $definition, string $key, string $class, mixed $value): DataDefinition
    {
        if (! is_array($value) && ! is_object($value)) {
            throw CastFailed::value($definition, $key, $class, $value, 'expected an array or an object');
        }

        return $class::make($value);
    }

    /**
     * @param  class-string<DataDefinition>  $class
     * @return Collection<array-key, DataDefinition>
     */
    private static function toList(string $definition, string $key, string $class, mixed $value): Collection
    {
        if (! is_array($value)) {
            throw CastFailed::value($definition, $key, "{$class}[]", $value, 'expected an array of arrays');
        }

        foreach ($value as $position => $item) {
            if (! is_array($item) && ! is_object($item)) {
                throw CastFailed::value($definition, "{$key}.{$position}", $class, $item, 'expected an array or an object');
            }
        }

        return $class::makeMany($value);
    }

    /**
     * @param  CastsAttributes<mixed, mixed>  $cast
     * @param  array<array-key, mixed>  $attributes
     */
    private static function callCast(CastsAttributes $cast, string $key, mixed $value, array $attributes): mixed
    {
        return $cast->get((new PayloadModel)->setRawAttributes($attributes, true), $key, $value, $attributes);
    }
}
