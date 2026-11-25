<!-- nav-group: Advanced -->
# Custom casts

A custom cast is a class that implements Laravel's `CastsAttributes` contract, the same contract you use for an Eloquent model.

## Writing a cast class

This cast builds a `Weight` value object from a number and the unit next to it in the payload. `Weight` is a small helper in [tests/Fixtures/DocsHelpers.php](https://github.com/keaton-php/laravel-data-definition/blob/1.x/tests/Fixtures/DocsHelpers.php).

```php
<?php

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class WeightCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): Weight
    {
        return new Weight((float) $value, $attributes['weight_unit'] ?? 'kg');
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): never
    {
        throw new LogicException('A DataDefinition never writes, so set() is never called.');
    }
}
```

```php
<?php

use Keaton\DataDefinition\DataDefinition;

$shipment = new class(['weight' => '2.4', 'weight_unit' => 'lb']) extends DataDefinition
{
    protected array $casts = ['weight' => WeightCast::class];
};

echo $shipment->weight, ' = ', $shipment->weight->toKilograms(), " kg\n";
```

```text
2.4 lb = 1.09 kg
```

`get()` receives four arguments:

- `$model`: the contract asks for an Eloquent model, and a definition doesn't have one. The cast gets a placeholder model that holds the payload as its attributes. It isn't saved or connected to a database, so read from `$value` and `$attributes` instead.
- `$key`: the key that was read, which is an alias or a dot-path.
- `$value`: the raw value at the path. It is never `null`, because a `null` is returned without calling the cast.
- `$attributes`: the whole raw payload. `WeightCast` uses it to read `weight_unit`.

A definition never writes, so `set()` is never called, but the contract still requires it. An exception thrown in your cast is not wrapped.

## Passing arguments

You can pass arguments in the class string, as `'Class:one,two'`. They reach the constructor as strings. The number of arguments has to match the constructor. Too few or too many throws `InvalidCast` when the definition is made.

```php
<?php

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class DivideBy implements CastsAttributes
{
    public function __construct(private string $divisor) {}

    public function get(Model $model, string $key, mixed $value, array $attributes): float
    {
        return $value / (int) $this->divisor;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): never
    {
        throw new LogicException('Never called.');
    }
}
```

```php
<?php

use Keaton\DataDefinition\DataDefinition;

$price = new class(['cents' => 1999]) extends DataDefinition
{
    protected array $casts = ['cents' => DivideBy::class.':100'];
};

var_dump($price->cents);
```

```text
float(19.99)
```

PHP doesn't allow `new` in a property default, so `$casts` can't hold an object there. To use a cast object, set it in `boot()`, which runs before the casts are checked: `$this->casts['cents'] = new DivideBy('100');`.

A class name that isn't a cast, an enum or a definition throws `InvalidCast`.

**Next:** [Nesting and collections](07-nesting-and-collections.md).
