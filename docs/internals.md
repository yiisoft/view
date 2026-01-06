# Internals

## Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```shell
./vendor/bin/phpunit
```

## Mutation testing

The package tests are checked with [Infection](https://infection.github.io/) mutation framework with
[Infection Static Analysis Plugin](https://github.com/Roave/infection-static-analysis-plugin). To run it:

```shell
./vendor/bin/roave-infection-static-analysis-plugin
```

## Static analysis

The code is statically analyzed with [Psalm](https://psalm.dev/). To run static analysis:

```shell
./vendor/bin/psalm
```

## Code style

Use [Rector](https://github.com/rectorphp/rector) to make codebase follow some specific rules or
use either newest or any specific version of PHP:

```shell
./vendor/bin/rector
```

## Dependencies

This package uses [composer-require-checker](https://github.com/maglnet/ComposerRequireChecker) to check if
all dependencies are correctly defined in `composer.json`. To run the checker, execute the following command:

```shell
./vendor/bin/composer-require-checker
```

## Benchmarks

The package provides performance benchmarks for the main use-cases, implemented with [phpbench](https://phpbench.readthedocs.io/).

Benchmarks are located in `tests/Benchmark` and configured via `phpbench.json`.

To run the benchmarks:

```shell
./vendor/bin/phpbench run
```

Or via Composer script:

```shell
composer bench
```
