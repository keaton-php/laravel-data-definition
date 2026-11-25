<!-- nav-group: Start here -->
# Installation

## Requirements

- PHP 8.2 or higher
- Laravel 11.33.2 or higher, 12 or 13 (Laravel 13 needs PHP 8.3)

The package depends on `illuminate/support`, `illuminate/contracts` and `illuminate/database`. It only uses `illuminate/database` for the `Model` type in Laravel's cast contract, and it never opens a database connection.

## Installing the package

You can install the package via composer:

```bash
composer require keaton-php/laravel-data-definition
```

The package has no service provider, config file or migration.

The test matrix covers Laravel 11 on PHP 8.2 to 8.4, Laravel 12 on PHP 8.2 to 8.5 and Laravel 13 on PHP 8.3 to 8.5.

**Next:** [Basic usage](03-basic-usage.md).
