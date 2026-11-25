<?php

declare(strict_types=1);

namespace Keaton\DataDefinition;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Conditionable;
use JsonSerializable;
use Keaton\DataDefinition\Exceptions\ImmutableDefinition;
use Keaton\DataDefinition\Exceptions\InvalidCast;
use Keaton\DataDefinition\Exceptions\InvalidData;
use Keaton\DataDefinition\Exceptions\InvalidDefinition;
use Keaton\DataDefinition\Exceptions\MissingValue;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

/**
 * An immutable wrapper around a raw array payload.
 *
 * Subclass it, name the paths you care about in $aliases, say how to read them in $casts, and
 * read values off the object like properties. Nothing on it can be written; with() returns a
 * changed copy.
 *
 * @implements ArrayAccess<string|int, mixed>
 * @implements Arrayable<string, mixed>
 */
abstract class DataDefinition implements Arrayable, ArrayAccess, Jsonable, JsonSerializable
{
    use Conditionable;

    /** The collection makeMany() returns. Must be Collection or a class that extends it. */
    protected static string $collectionClass = Collection::class;

    /**
     * Alias => dot-path into the payload, for example 'email' => 'contact.primary_email'.
     *
     * @var array<string, string>
     */
    protected array $aliases = [];

    /**
     * Alias or dot-path => cast. The alias is looked up first, then the dot-path.
     *
     * @var array<string, mixed>
     */
    protected array $casts = [];

    /** @var array<array-key, mixed> */
    private readonly array $data;

    /**
     * The aliases and casts as they stood once boot() had run and they had been checked. Reads use
     * these, so code in a subclass that edits $aliases or $casts later cannot change a definition.
     *
     * @var array<string, string>
     */
    private readonly array $builtAliases;

    /** @var array<string, mixed> */
    private readonly array $builtCasts;

    /**
     * @param  array<array-key, mixed>  $data
     */
    final public function __construct(array $data)
    {
        $this->data = $data;

        $this->boot();
        $this->validate();

        $this->builtAliases = $this->aliases;
        $this->builtCasts = $this->casts;
    }

    /**
     * Wrap a payload: an array, an Arrayable (its toArray()), or any other object (its public
     * properties). Anything else throws.
     *
     * @param  array<array-key, mixed>|Arrayable<array-key, mixed>|object  $data
     */
    public static function make(mixed $data): static
    {
        return new static(match (true) {
            is_array($data) => $data,
            $data instanceof Arrayable => $data->toArray(),
            is_object($data) => get_object_vars($data),
            default => throw InvalidData::payload(self::label(), $data),
        });
    }

    /**
     * Wrap every item. Keys are kept.
     *
     * @param  iterable<array-key, mixed>  $items
     * @return Collection<array-key, static>
     */
    public static function makeMany(iterable $items): Collection
    {
        $class = static::$collectionClass;

        if (! is_a($class, Collection::class, true) || ! self::canBeBuiltFromItems($class)) {
            throw InvalidDefinition::collectionClass(self::label(), $class);
        }

        $made = [];

        foreach ($items as $key => $item) {
            $made[$key] = static::make($item);
        }

        return new $class($made);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        [$found, $value] = $this->find($key);

        return $found ? $this->cast($key, $value) : $default;
    }

    public function getOrFail(string $key): mixed
    {
        [$found, $value] = $this->find($key);

        return $found ? $this->cast($key, $value) : throw MissingValue::at(self::label(), $key, $this->pathFor($key));
    }

    /** True when the payload holds something at the path, even null. */
    public function has(string $key): bool
    {
        return $this->find($key)[0];
    }

    /**
     * A copy with the given changes applied. Keys are aliases or dot-paths; missing parents are created.
     *
     * @param  array<string, mixed>  $changes
     */
    public function with(array $changes): static
    {
        $data = $this->data;

        foreach ($changes as $key => $value) {
            $data = Path::with(self::label(), $data, $this->pathFor($this->checkKey($key)), $value);
        }

        return new static($data);
    }

    /** @return array<array-key, mixed> the raw payload, untouched */
    public function data(): array
    {
        return $this->data;
    }

    /**
     * Every declared alias, resolved and cast, in declaration order. A missing path is null.
     * A nested definition, or a collection of them, becomes an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $array = [];

        foreach (array_keys($this->builtAliases) as $alias) {
            $value = $this->get($alias);

            $array[$alias] = $value instanceof Arrayable ? $value->toArray() : $value;
        }

        return $array;
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @param  int  $options  json_encode() flags; JSON_THROW_ON_ERROR is always added
     */
    public function toJson($options = 0): string
    {
        return json_encode($this, $options | JSON_THROW_ON_ERROR);
    }

    public function json(int $flags = 0): string
    {
        return $this->toJson($flags);
    }

    public function __get(string $name): mixed
    {
        return $this->get($name);
    }

    /** Like isset() everywhere in PHP: false for null. Use has() to tell null from missing. */
    public function __isset(string $name): bool
    {
        [$found, $value] = $this->find($name);

        return $found && $value !== null;
    }

    public function __set(string $name, mixed $value): void
    {
        throw ImmutableDefinition::write(self::label(), "setting '{$name}'");
    }

    public function __unset(string $name): void
    {
        throw ImmutableDefinition::write(self::label(), "unsetting '{$name}'");
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->__isset($this->offsetKey($offset));
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($this->offsetKey($offset));
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw ImmutableDefinition::write(self::label(), 'assigning to an offset');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw ImmutableDefinition::write(self::label(), 'unsetting an offset');
    }

    /** Runs when the definition is built, before its aliases and casts are checked, so it may adjust them. Does nothing by default. */
    protected function boot(): void {}

    /** @return array{0: bool, 1: mixed} */
    private function find(string $key): array
    {
        return Path::find($this->data, $this->pathFor($this->checkKey($key)));
    }

    private function pathFor(string $key): string
    {
        return $this->builtAliases[$key] ?? $key;
    }

    private function cast(string $key, mixed $value): mixed
    {
        $type = $this->builtCasts[$key] ?? $this->builtCasts[$this->pathFor($key)] ?? null;

        // A present null stays null: casts only ever see a value.
        if ($type === null || $value === null) {
            return $value;
        }

        return Caster::cast(self::label(), $key, $type, $value, $this->data);
    }

    private function checkKey(mixed $key): string
    {
        return is_string($key) && ! in_array('', explode('.', $key), true) ? $key : throw InvalidData::key(self::label(), $key);
    }

    /**
     * True for a collection class that can be built from one array of items.
     *
     * @param  class-string<Collection<array-key, mixed>>  $class
     */
    private static function canBeBuiltFromItems(string $class): bool
    {
        $reflection = new ReflectionClass($class);

        if (! $reflection->isInstantiable()) {
            return false;
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return true;
        }

        if ($constructor->getNumberOfRequiredParameters() > 1 || ($constructor->getNumberOfParameters() < 1 && ! $constructor->isVariadic())) {
            return false;
        }

        return self::typeAcceptsArray($constructor->getParameters()[0]->getType());
    }

    /** True when a declared parameter type can take the array of items (no type at all counts). */
    private static function typeAcceptsArray(?ReflectionType $type): bool
    {
        if ($type === null) {
            return true;
        }

        if ($type instanceof ReflectionNamedType) {
            return in_array($type->getName(), ['array', 'iterable', 'mixed'], true);
        }

        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $member) {
                if (self::typeAcceptsArray($member)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function offsetKey(mixed $offset): string
    {
        return $this->checkKey(is_int($offset) ? (string) $offset : $offset);
    }

    /** True when an alias would hide a property, or a public or protected method, of the class. */
    private function shadows(string $alias): bool
    {
        if (property_exists($this, $alias) || property_exists(self::class, $alias)) {
            return true;
        }

        return method_exists($this, $alias) && ! (new ReflectionMethod($this, $alias))->isPrivate();
    }

    /** The class name for messages; an anonymous class's name carries a file path after a null byte. */
    private static function label(): string
    {
        return explode("\0", static::class)[0];
    }

    private function validate(): void
    {
        foreach ($this->aliases as $alias => $path) {
            if (! is_string($alias) || $alias === '' || str_contains($alias, '.')) {
                throw InvalidDefinition::aliasName(self::label(), $alias);
            }

            if (! is_string($path) || in_array('', explode('.', $path), true)) {
                throw InvalidDefinition::aliasPath(self::label(), $alias, $path);
            }

            if ($this->shadows($alias)) {
                throw InvalidDefinition::aliasShadows(self::label(), $alias);
            }
        }

        foreach ($this->casts as $key => $type) {
            if (! is_string($key) || in_array('', explode('.', $key), true)) {
                throw InvalidCast::key(self::label(), $key);
            }

            Caster::validate(self::label(), $key, $type);
        }
    }
}
