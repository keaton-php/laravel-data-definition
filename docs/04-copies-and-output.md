<!-- nav-group: Basic usage -->
# Copies and output

A definition is read-only. To change one you make a copy with `with()`. To get the data out you use `toArray()` or `json()`.

## Immutability

Setting or unsetting a property or an array offset throws `ImmutableDefinition`.

```php
<?php

use Keaton\DataDefinition\Exceptions\ImmutableDefinition;

$customer = Customer::make(ExamplePayloads::customer());

try {
    $customer->email = 'x@example.com';
} catch (ImmutableDefinition $e) {
    echo $e->getMessage(), "\n";
}
```

```text
Customer is immutable: setting 'email' is not allowed. Use with() to get a changed copy.
```

Don't add public properties to a definition class. The package can't intercept an assignment to a real property.

## Making a changed copy

`with()` returns a new definition of the same class with your changes applied to the raw payload. The original isn't changed. The keys can be aliases or dot-paths, and missing parents are created. You can set a value to `null`, but you can't remove a path.

```php
<?php

$customer = Customer::make(ExamplePayloads::customer());

$moved = $customer->with([
    'email' => 'ana@new-home.example',
    'data.plan.name' => 'team',
]);

echo $customer->email, ' -> ', $moved->email, "\n";
echo $moved->get('data.plan.name'), "\n";
var_dump($customer->has('data.plan'));
```

```text
ana@example.com -> ana@new-home.example
team
bool(false)
```

`with()` won't write through a value that isn't an array. Here `with(['data.seats.limit' => 10])` throws `InvalidData`, because `data.seats` is a string.

The built-in casts build a new value on every read, so changing a Carbon date you got from a definition doesn't change the definition. Objects you put in the payload yourself, such as a `DateTime`, are not copied. It's best to build payloads from plain arrays, for example with `json_decode($body, true)`.

## Output and JSON

`toArray()` returns every declared alias, cast, in the order they were declared. Nested definitions and collections become arrays too. Keys without an alias are left out, and an alias whose path is missing is `null`.

`json()`, `toJson()` and `json_encode()` all return the same JSON. `json()` and `toJson()` accept the usual flags.

```php
<?php

$customer = Customer::make(ExamplePayloads::customer());

echo $customer->json(), "\n";
echo $customer->json(JSON_PRETTY_PRINT), "\n";
```

```text
{"email":"ana@example.com","joined":"2026-03-02T09:15:00.000000Z","seats":5}
{
    "email": "ana@example.com",
    "joined": "2026-03-02T09:15:00.000000Z",
    "seats": 5
}
```

A definition is `JsonSerializable` and `Arrayable`, so you can pass it to `response()->json()` or return it from a resource. A value that can't be encoded throws `JsonException`. Values that are cast to objects, such as dates and enums, stay objects in `toArray()`.

`data()` returns a copy of the raw payload, including the keys that don't have an alias. When you make a definition from an `Arrayable` or an object, the payload is its `toArray()` or its public properties.

**Next:** [Casting](05-casting.md).
