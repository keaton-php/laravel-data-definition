<!-- nav-group: Basic usage -->
# Basic usage

A definition is a class that extends `DataDefinition`. `$aliases` maps a short name to a dot-path, and `$casts` says how to read the value behind a name.

```php
<?php

use Keaton\DataDefinition\DataDefinition;

class Customer extends DataDefinition
{
    protected array $aliases = [
        'email' => 'data.contact.primary_email',
        'joined' => 'data.created',
        'seats' => 'data.seats',
    ];

    protected array $casts = [
        'joined' => 'datetime',
        'seats' => 'int',
    ];
}
```

`ExamplePayloads::customer()` returns a small sample payload. You can find it in [tests/Fixtures/DocsHelpers.php](https://github.com/keaton-php/laravel-data-definition/blob/1.x/tests/Fixtures/DocsHelpers.php).

## Reading values

You can read an alias as a property, as an array offset or with `get()`. A key that isn't an alias is read as a dot-path into the raw payload. `get($key, $default)` returns the default only when the path is missing.

```php
<?php

$customer = Customer::make(ExamplePayloads::customer());

echo $customer->email, "\n";
echo $customer['email'], "\n";
echo $customer->get('email'), "\n";
echo $customer->get('data.contact.primary_email'), "\n";
echo $customer->get('nickname', 'none'), "\n";
```

```text
ana@example.com
ana@example.com
ana@example.com
ana@example.com
none
```

## Null and missing values

A key that holds `null` is not the same as a key that isn't there. `has()` returns `true` for a key that holds `null`, and `get()` only returns the default for a missing path. `isset()` behaves like it does in PHP, so it returns `false` for `null`.

```php
<?php

$customer = Customer::make(ExamplePayloads::customer());

var_dump($customer->has('data.contact.phone'), $customer->has('data.contact.nickname'));
var_dump($customer->get('data.contact.phone', 'n/a'), $customer->get('data.contact.nickname', 'n/a'));
var_dump(isset($customer['data.contact.phone']));
```

```text
bool(true)
bool(false)
NULL
string(3) "n/a"
bool(false)
```

Casts follow the same rule. A cast only runs on a value that is present and not `null`. It doesn't run on the default you pass.

## Requiring a value

`getOrFail()` works like `get()`, but it throws `MissingValue` when the path is missing. A `null` that is present is returned.

It is also a good way to catch a misspelled alias. A normal read of a misspelled alias is a read of a missing path, which returns `null`.

```php
<?php

use Keaton\DataDefinition\Exceptions\MissingValue;

$customer = Customer::make(ExamplePayloads::customer());

var_dump($customer->emial);

foreach (['emial', 'seats'] as $key) {
    try {
        Customer::make(['data' => []])->getOrFail($key);
    } catch (MissingValue $e) {
        echo $e->getMessage(), "\n";
    }
}
```

```text
NULL
Customer: the payload has nothing at 'emial'.
Customer: the payload has nothing at 'data.seats' (alias 'seats').
```

When the key is an alias, the message includes the path behind it.

**Next:** [Copies and output](04-copies-and-output.md).
