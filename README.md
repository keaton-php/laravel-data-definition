# Laravel Data Definition

[![Latest Version on Packagist](https://img.shields.io/packagist/v/keaton-php/laravel-data-definition.svg?style=flat-square)](https://packagist.org/packages/keaton-php/laravel-data-definition)
[![Tests](https://img.shields.io/github/actions/workflow/status/keaton-php/laravel-data-definition/tests.yml?branch=1.x&label=tests&style=flat-square)](https://github.com/keaton-php/laravel-data-definition/actions)
[![Total Downloads](https://img.shields.io/packagist/dt/keaton-php/laravel-data-definition.svg?style=flat-square)](https://packagist.org/packages/keaton-php/laravel-data-definition)

Laravel Data Definition wraps an array payload, like a webhook body or an API response, in a small immutable object. You name the deep paths and their types once, and a value of the wrong type throws an exception. It is never quietly turned into something else.

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

## Typed values and copies

With a number in the budget you get an integer back, and `at` is a Carbon instance. A definition can't be changed after it is made, but `with()` returns a changed copy.

```php
<?php

$lead = Lead::make([
    'created_time' => '2026-03-02T09:15:00Z',
    'contact' => ['fields' => ['monthly_budget' => '2500']],
]);

$raised = $lead->with(['budget' => '4000']);

var_dump($lead->budget, $raised->budget);
echo $lead->at->format('l, j M Y'), PHP_EOL;
```

```text
int(2500)
int(4000)
Monday, 2 Mar 2026
```

## What else it does

- Cast a value to a backed enum. A value that isn't one of its cases throws, so you notice when an API starts sending a new status.
- Cast a sub-object to another definition, and a list to a collection of them.
- Use your own cast classes. They implement `CastsAttributes`, the same contract you use for an Eloquent model.
- `getOrFail()` throws when a value is missing, and the message names the path. A missing key and a `null` are not the same thing.
- `toArray()` and `json()` return the declared values by their short names, so you can pass a definition to a response.
- `makeMany()` wraps every item of a list or a generator.

## When to use it

Use this package for payloads you receive and don't control. It doesn't validate or transform data.

If you define the data yourself, or you need validation, use [spatie/laravel-data](https://github.com/spatie/laravel-data). If you only read a few values, Laravel's `Fluent` is enough. It reads dot-paths and has typed getters, but it is writable, has no aliases, and its `integer()` returns `0` for `"n/a"`.

## Installation

You can install the package via composer:

```bash
composer require keaton-php/laravel-data-definition
```

The package requires PHP 8.2 or higher and Laravel 11, 12 or 13. It has no service provider, config file or migration.

## Documentation

You'll find the documentation on [kevineaton.dev/docs/laravel-data-definition](https://kevineaton.dev/docs/laravel-data-definition/). [Basic usage](https://kevineaton.dev/docs/laravel-data-definition/basic-usage/) is a good place to start. The same pages are in the [docs](docs) folder of this repository:

- [Introduction](docs/index.md)
- [Installation](docs/02-installation.md)
- [Basic usage](docs/03-basic-usage.md)
- [Copies and output](docs/04-copies-and-output.md)
- [Casting](docs/05-casting.md)
- [Custom casts](docs/06-custom-casts.md)
- [Nesting and collections](docs/07-nesting-and-collections.md)
- [Reference](docs/08-reference.md)

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Kevin Eaton](https://github.com/keaton-php)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
