<!-- nav-group: Basic usage -->
# Casting

Without casts, a definition returns values exactly as they are in the payload. You can change that with `$casts`. The keys are aliases or dot-paths, and each value says how to read the value at that key.

## Built-in casts

| Cast | Accepts | Returns |
|---|---|---|
| `int`, `integer` | an int, a float without a fractional part, or an integer string such as `'42'` | `int` |
| `float`, `double` | an int, a float or a numeric string | `float` |
| `string` | a string, an int, a float or a `Stringable` | `string` |
| `bool`, `boolean` | a bool, the int `1` or `0`, or `'1'`, `'0'`, `'true'`, `'false'`, `'yes'`, `'no'`, `'on'`, `'off'` in any case | `bool` |
| `array` | an array | the array |
| `collection` | an array | an `Illuminate\Support\Collection` |
| `json` | a string holding a JSON object or list | the decoded array |
| `date` | a date string, a unix timestamp or a `DateTimeInterface` | a `Carbon` at the start of the day |
| `datetime` | the same as `date` | a `Carbon` |

A date string is parsed with `Carbon::parse()`. A string without an offset uses your default timezone, and a unix timestamp is read as UTC. The `bool` cast is strict, so `1.0`, `'2'` and `' true'` are not accepted.

A value that a built-in cast can't read throws `CastFailed`. The message names the key, the cast and the value.

```php
<?php

use Keaton\DataDefinition\DataDefinition;

class Invoice extends DataDefinition
{
    protected array $casts = ['total' => 'float'];
}
```

```php
<?php

use Keaton\DataDefinition\Exceptions\CastFailed;

try {
    Invoice::make(['total' => 'about 150'])->total;
} catch (CastFailed $e) {
    echo $e->getMessage(), "\n";
}
```

```text
Invoice: cannot cast 'total' to float: got 'about 150' (expected a number or a numeric string).
```

## Casting by alias or by path

You can key a cast by the alias or by the dot-path. Reading `$definition->age` looks for a cast under `age` first, and then under the path behind it. `get('user.age')` only looks under `user.age`. If a value should be cast however it is read, key the cast by its dot-path.

A cast key that doesn't match anything is never used and doesn't throw, so check the spelling.

## Enums

Use a backed enum class as the cast, and the value in the payload must be one of its case values. An int-backed enum also accepts an integer string such as `'3'`.

A value that isn't a case throws `CastFailed`. There is no fallback case, so you'll notice when an API starts sending a new status. A pure enum throws `InvalidCast` when the definition is made.

```php
<?php

enum ShipmentStatus: string
{
    case Packed = 'packed';
    case InTransit = 'in_transit';
}
```

```php
<?php

use Keaton\DataDefinition\DataDefinition;

class Parcel extends DataDefinition
{
    protected array $casts = ['status' => ShipmentStatus::class];
}
```

```php
<?php

use Keaton\DataDefinition\Exceptions\CastFailed;

$parcel = Parcel::make(['status' => 'in_transit']);

var_dump($parcel->status);

try {
    $parcel->with(['status' => 'lost'])->status;
} catch (CastFailed $e) {
    echo $e->getMessage(), "\n";
}
```

```text
enum(ShipmentStatus::InTransit)
Parcel: cannot cast 'status' to ShipmentStatus: got 'lost' (not a value of that enum).
```

**Next:** [Custom casts](06-custom-casts.md).
