# Laravel Blade Linter

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jbboehr/laravel-blade-linter.svg?style=flat-square)](https://packagist.org/packages/jbboehr/laravel-blade-linter)
[![ci](https://github.com/jbboehr/laravel-blade-linter/actions/workflows/ci.yml/badge.svg)](https://github.com/jbboehr/laravel-blade-linter/actions/workflows/ci.yml)
[![Test Coverage](https://api.codeclimate.com/v1/badges/91da0bd0a4a06c57fc94/test_coverage)](https://codeclimate.com/github/jbboehr/laravel-blade-linter/test_coverage)
[![Maintainability](https://api.codeclimate.com/v1/badges/91da0bd0a4a06c57fc94/maintainability)](https://codeclimate.com/github/jbboehr/laravel-blade-linter/maintainability)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE.md)

Performs syntax-checks of your Blade templates. Just that.

## Installation

You can install the package via composer:

```bash
composer require --dev jbboehr/laravel-blade-linter
```

## Usage

```bash
php artisan blade:lint
```

Or if you want to lint specific templates or directories:

```bash
php artisan blade:lint resources/views/
```

### Testing

``` bash
composer test
```

## Credits

- [All Contributors](./graphs/contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
