<!-- nav-group: Advanced -->
# Nesting and collections

## Nested definitions

Use another definition class as the cast to wrap the value at that path in it. For a list, put the class in an array, like `[Teammate::class]`. A list becomes a collection of definitions and keeps the payload's keys.

```php
<?php

use Keaton\DataDefinition\DataDefinition;

class Teammate extends DataDefinition
{
    protected array $aliases = ['name' => 'full_name'];
}
```

```php
<?php

use Keaton\DataDefinition\DataDefinition;

class Team extends DataDefinition
{
    protected array $aliases = ['members' => 'people'];

    protected array $casts = [
        'lead' => Teammate::class,
        'members' => [Teammate::class],
    ];
}
```

```php
<?php

$team = Team::make([
    'lead' => ['full_name' => 'Ana Costa'],
    'people' => [['full_name' => 'Ben Ito'], ['full_name' => 'Cleo Park']],
]);

echo $team->lead->name, "\n";
echo $team->members->count(), ' members: ', $team->members->pluck('name')->implode(', '), "\n";
```

```text
Ana Costa
2 members: Ben Ito, Cleo Park
```

The nested definition has its own aliases and casts. A sub-object that is `null` or missing stays `null`. A `null` list stays `null`, and an empty list becomes an empty collection.

The value has to be an array or an object. Anything else throws `CastFailed`, and the message names the key and, for a list, the position of the item (`'members.1'`). A cast must name one `DataDefinition` subclass that can be instantiated. An abstract class, two classes or an empty array throws `InvalidCast` when the definition is made.

## Making many definitions

`makeMany()` wraps every item of an iterable and returns a collection that keeps the keys. Generators work too. An item that isn't an array, an `Arrayable` or an object throws `InvalidData`.

A definition implements `ArrayAccess`, so collection helpers such as `sum('seats')` can read aliases.

## Using your own collection class

Set `$collectionClass` to get your own collection class back from `makeMany()` and from a list cast of the definition. It must be `Illuminate\Support\Collection` or a subclass that takes the items as its only constructor argument. Anything else throws `InvalidDefinition` the first time `makeMany()` is called.

```php
<?php

use Illuminate\Support\Collection;

class SubscriberCollection extends Collection
{
    public function totalSeats(): int
    {
        return $this->sum('seats');
    }
}
```

```php
<?php

use Keaton\DataDefinition\DataDefinition;

class Subscriber extends DataDefinition
{
    protected static string $collectionClass = SubscriberCollection::class;
}
```

```php
<?php

$subscribers = Subscriber::makeMany(['ana' => ['seats' => '5'], 'ben' => ['seats' => '3']]);

echo get_class($subscribers), ' ', $subscribers->keys()->implode(', '), ' ', $subscribers->totalSeats(), "\n";
```

```text
SubscriberCollection ana, ben 8
```

**Next:** [Reference](08-reference.md).
