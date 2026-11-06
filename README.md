<div align="center">
    <h1>Laravel Multi Region Databases</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/tpetry/laravel-multiregion"><img src="https://img.shields.io/packagist/v/tpetry/laravel-multiregion.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/tpetry/laravel-multiregion"><img src="https://img.shields.io/packagist/php-v/tpetry/laravel-multiregion.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/tpetry/laravel-multiregion"><img src="https://badge.laravel.cloud/badge/tpetry/laravel-multiregion?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/tpetry/laravel-multiregion/actions"><img src="https://img.shields.io/github/actions/workflow/status/tpetry/laravel-multiregion/tests.yml?branch=main&label=Tests&style=flat-square" alt="GitHub Workflow Status (main)"></a>
    <a href="https://packagist.org/packages/tpetry/laravel-multiregion"><img src="https://img.shields.io/packagist/dt/tpetry/laravel-multiregion.svg?style=flat-square" alt="Total Downloads"></a>
</p>

Middleware to create error-free multi-region applications

## Installation

You can install the package via Composer:

```bash
composer require tpetry/laravel-multiregion
```

### Publishing the Configuration File

```bash
php artisan vendor:publish --tag="multiregion-config"
```

## Usage

<!-- Add a basic usage example here. -->

### Testing that every route uses multi-region middleware

A forgotten `multiRegionRead()`, `multiRegionWrite()` or `noMultiRegion()` on a route can lead to stale data. An architecture test can catch them. Add the `ExpectsRoutes` trait to your tests in `tests/Pest.php`:

```php
use Tpetry\MultiRegion\Pest\ExpectsRoutes;

pest()->extend(TestCase::class)
    ->use(ExpectsRoutes::class)
    ->in('Feature', 'Arch');
```

Routes registered by the framework or packages can't get the middleware. List them in the `ignore` option of the `config/multiregion.php` configuration file, by their URI (starting with a slash) or their name. Both may contain wildcards:

```php
'ignore' => [
    '/_boost/*',
    '/_inertia/*',
    '/storage/*',
    '/up',
    'horizon.*',
],
```

And create an architecture test, e.g. `tests/Arch/MultiRegionTest.php`. It ignores the configured routes, and more can be added with `ignoring()`:

```php
arch()
    ->expectRoutes()
    ->toUseMultiRegionMiddleware()
    ->ignoring([
        'crm.page.*',
    ]);
```

The test fails for the first route without one of the middleware.

### Listing the multi-region mode of every route

The `multiregion:routes` command lists every route with its multi-region mode: `uses multiRegionRead()` and `uses multiRegionWrite()` in green, `uses noMultiRegion()` in yellow, `ignored (pattern)` (by the configuration) in gray, and `missing` in bold red. It exits with an error code when a route has no multi-region middleware, so it can also be used in CI:

```bash
php artisan multiregion:routes
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## License

Laravel Multi Region Databases is open-sourced software licensed under the [MIT license](LICENSE.md).
