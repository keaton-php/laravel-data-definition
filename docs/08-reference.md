<!-- nav-group: Reference -->
# Reference

## Exceptions

All exceptions extend `Keaton\DataDefinition\Exceptions\DataDefinitionException`, which extends `RuntimeException`.

| Exception | Thrown when |
|---|---|
| `InvalidData` | `make()` gets something that isn't an array, an `Arrayable` or an object. A key is empty, has an empty dot segment or isn't a string or an int. `with()` would write through a value that isn't an array. |
| `InvalidDefinition` | An alias name or path is malformed, an alias has the name of a property or of a public or protected method, or `$collectionClass` isn't a collection. |
| `InvalidCast` | A cast is declared in a way the package can't use. |
| `CastFailed` | A value is present but can't be cast. |
| `MissingValue` | `getOrFail()` finds nothing at the path. |
| `ImmutableDefinition` | Something tried to write to a definition. |

`InvalidDefinition` and `InvalidCast` are thrown when the definition is made with `make()` or `new`, even if you never read the value. An invalid `$collectionClass` throws the first time `makeMany()` is called.

## The boot method

`boot()` is called once when a definition is made, before its aliases and casts are checked. It takes no arguments and does nothing by default. You can override it to set aliases or casts based on the payload, which you can read with `data()`.

```php
<?php

use Keaton\DataDefinition\DataDefinition;

class FlexibleContact extends DataDefinition
{
    protected function boot(): void
    {
        $this->aliases['email'] = array_key_exists('contact', $this->data())
            ? 'contact.email'
            : 'email_address';
    }
}
```

```php
<?php

echo FlexibleContact::make(['contact' => ['email' => 'ana@example.com']])->email, "\n";
echo FlexibleContact::make(['email_address' => 'ben@example.com'])->email, "\n";
```

```text
ana@example.com
ben@example.com
```

A definition also uses Laravel's `Conditionable` trait, so `when()` and `unless()` are available.

## Rules

- An alias name is a non-empty string without a dot. It can't be the name of a property of the class, or of a public or protected method such as `data`, `get`, `has`, `with`, `json`, `toArray`, `make`, `boot` or `when`.
- An alias path is a dot-path without empty segments. Every dot splits the path, so a payload key that contains a dot can't be reached.
- A cast is a built-in name, a `CastsAttributes` class name (optionally with arguments, as `'Class:one,two'`), a `CastsAttributes` object, a backed enum class, a `DataDefinition` subclass, or `[Definition::class]` for a list. They are checked in that order. Anything else throws `InvalidCast`.
- `isset()` and `offsetExists()` return `true` when the path exists and isn't `null`. They don't run casts.
- Aliases and casts are read once, when the definition is made. Changing `$aliases` or `$casts` afterward has no effect.

## Static analysis

A definition reads keys through `__get()`, so PHPStan reports `$customer->email` as an undefined property. You can add `@property-read` annotations to your definition class, or add `Keaton\DataDefinition\DataDefinition` to `universalObjectCratesClasses` in your `phpstan.neon`.
