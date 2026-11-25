<!-- nav-group: Start here -->
# Introduction

Laravel Data Definition wraps an array payload, like a webhook body or an API response, in a small immutable object. You name the deep paths and their types once, and a value of the wrong type throws an exception. It is never quietly turned into something else.

## Before and after

Say a lead comes in from a webhook, and you need four values from its payload. The usual way is to read each one by hand and cast it:

```php
<?php

use Illuminate\Support\Carbon;

$payload = [
    'created_time' => '2026-03-02T09:15:00Z',
    'campaign' => ['utm_source' => 'google'],
    'contact' => ['fields' => [
        'email' => 'ana@example.com',
        'monthly_budget' => 'n/a',
    ]],
];

$email = $payload['contact']['fields']['email'];
$source = $payload['campaign']['utm_source'];
$at = Carbon::parse($payload['created_time']);
$budget = (int) $payload['contact']['fields']['monthly_budget'];

echo "{$email} via {$source} on {$at->format('j M Y')}", PHP_EOL;
echo "budget: {$budget}", PHP_EOL;
```

```text
ana@example.com via google on 2 Mar 2026
budget: 0
```

That reads the payload. But this lead typed `n/a` for the budget, and `(int)` turned it into `0`. Nothing failed, so that zero is what gets saved.

Here's the same payload read with the package. You write a small class once. `$aliases` gives each deep path a short name, and `$casts` says what type it should be:

```php
<?php

use Keaton\DataDefinition\DataDefinition;

class Lead extends DataDefinition
{
    protected array $aliases = [
        'email'  => 'contact.fields.email',
        'budget' => 'contact.fields.monthly_budget',
        'source' => 'campaign.utm_source',
        'at'     => 'created_time',
    ];

    protected array $casts = [
        'budget' => 'int',
        'at'     => 'datetime',
    ];
}
```

Then you read the lead by those names:

```php
<?php

use Keaton\DataDefinition\Exceptions\CastFailed;

$payload = [
    'created_time' => '2026-03-02T09:15:00Z',
    'campaign' => ['utm_source' => 'google'],
    'contact' => ['fields' => [
        'email' => 'ana@example.com',
        'monthly_budget' => 'n/a',
    ]],
];

$lead = Lead::make($payload);

echo "{$lead->email} via {$lead->source} on {$lead->at->format('j M Y')}", PHP_EOL;

try {
    echo "budget: {$lead->budget}", PHP_EOL;
} catch (CastFailed $e) {
    echo $e->getMessage(), PHP_EOL;
}
```

```text
ana@example.com via google on 2 Mar 2026
Lead: cannot cast 'budget' to int: got 'n/a' (expected an integer or a plain integer string).
```

The first line is the same. The budget wasn't a number, so the package threw an exception that names the field and the value, and there is no zero to save. The paths and the types now live in one class, so every part of your app reads a lead the same way.

## What else it does

- Cast a value to a backed enum. A value that isn't one of its cases throws, so you notice when an API starts sending a new status. See [Casting](05-casting.md).
- Cast a sub-object to another definition, and a list to a collection of them. See [Nesting and collections](07-nesting-and-collections.md).
- Use your own cast classes. They implement `CastsAttributes`, the same contract you use for an Eloquent model. See [Custom casts](06-custom-casts.md).
- `getOrFail()` throws when a value is missing, and the message names the path. A missing key and a `null` are not the same thing. See [Basic usage](03-basic-usage.md).
- `toArray()` and `json()` return the declared values by their short names, so you can pass a definition to a response. See [Copies and output](04-copies-and-output.md).
- `makeMany()` wraps every item of a list or a generator. See [Nesting and collections](07-nesting-and-collections.md).

## When to use it

Use this package for payloads you receive and don't control.

If you only read a few values, Laravel's `Fluent` is enough. It has dot access and, from Laravel 11.34, typed getters such as `integer()`. It doesn't have declared casts, aliases or write protection.

If you define the data yourself, or you need validation or transformation, use [`spatie/laravel-data`](https://github.com/spatie/laravel-data).

This package doesn't validate against rules, write back into the payload, integrate with Eloquent models or generate classes from a sample payload.

**Next:** [Installation](02-installation.md).
