<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/993323" height="100px">
    </a>
    <h1 align="center">Yii View Rendering Library</h1>
    <br>
</p>

This library provides PHP-based templates rendering.
It is used in [Yii Framework] but is supposed to be usable separately.

[Yii Framework]: https://www.yiiframework.com

For license information check the [LICENSE](LICENSE.md)-file.

[![Latest Stable Version](https://poser.pugx.org/yiisoft/view/v/stable.png)](https://packagist.org/packages/yiisoft/view)
[![Total Downloads](https://poser.pugx.org/yiisoft/view/downloads.png)](https://packagist.org/packages/yiisoft/view)
[![Build Status](https://github.com/yiisoft/view/workflows/build/badge.svg)](https://github.com/yiisoft/view/actions?query=workflow%3Abuild)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/yii-web/badges/coverage.png?s=31d80f1036099e9d6a3e4d7738f6b000b3c3d10e)](https://scrutinizer-ci.com/g/yiisoft/yii-web/)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/yiisoft/yii-web/badges/quality-score.png?s=b1074a1ff6d0b214d54fa5ab7abbb90fc092471d)](https://scrutinizer-ci.com/g/yiisoft/yii-web/)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fview%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/view/master)
[![static analysis](https://github.com/yiisoft/view/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/view/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/view/coverage.svg)](https://shepherd.dev/github/yiisoft/view)


## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist yiisoft/view
```

or add

```
"yiisoft/view": "^1.0"
```

to the require section of your `composer.json`.

## Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```php
./vendor/bin/phpunit
```

## Mutation testing

The package tests are checked with [Infection](https://infection.github.io/) mutation framework. To run it:

```php
./vendor/bin/infection
```

## Static analysis

The code is statically analyzed with [Phan](https://github.com/phan/phan/wiki). To run static analysis:

```php
./vendor/bin/phan
```
