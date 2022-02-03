<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px">
    </a>
    <h1 align="center">Yii View Rendering Library</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/view/v/stable.png)](https://packagist.org/packages/yiisoft/view)
[![Total Downloads](https://poser.pugx.org/yiisoft/view/downloads.png)](https://packagist.org/packages/yiisoft/view)
[![Build Status](https://github.com/yiisoft/view/workflows/build/badge.svg)](https://github.com/yiisoft/view/actions?query=workflow%3Abuild)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/view/badges/coverage.png?s=31d80f1036099e9d6a3e4d7738f6b000b3c3d10e)](https://scrutinizer-ci.com/g/yiisoft/view/?branch=master)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/yiisoft/view/badges/quality-score.png?s=b1074a1ff6d0b214d54fa5ab7abbb90fc092471d)](https://scrutinizer-ci.com/g/yiisoft/view/?branch=master)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fview%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/view/master)
[![static analysis](https://github.com/yiisoft/view/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/view/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/view/coverage.svg)](https://shepherd.dev/github/yiisoft/view)

This library provides templates rendering abstraction supporting layout-view-subview hierarchy, custom renderers with
PHP-based as default and more. It is used in [Yii Framework](https://www.yiiframework.com/) but is supposed
to be usable separately.

## Requirements

- PHP 7.4 or higher.

## Installation

The package could be installed via composer:

```shell
composer require yiisoft/view --prefer-dist
```

## General usage

The package provides two use cases for managing view templates:

- [Basic functionality](docs/basic-functionality.md) for use in any environment.
- Advanced functionality for [use in a WEB environment](docs/use-in-web-environment.md).

### State of `View` and `WebView` services

While being immutable and, by itself, stateless, both `View` and `WebView` services have sets of stateful and mutable
data.

`View` service:
- parameters,
- blocks,
- theme,
- language.

`WebView` service:
- parameters,
- blocks,
- theme,
- language,
- title,
- meta and link tags,
- JS/CSS strings,
- JS/CSS files.

The state of `View` and `WebView` is not cloned when the services are cloned. So when
using `with*()`, both new and old instances are sharing the same set of stateful mutable data. It allows, for example,
to get `WebView` via type-hinting in a controller and change context path:

```php
final class BlogController {
    private WebView $view;
    public function __construct (WebView $view) {
        $this->view = $view->withContextPath(__DIR__.'/views');
    }
}
```

... and then register CSS in a widget:

```php
final class LastPosts extends Widget 
{    
    private WebView $view;
    public function __construct (WebView $view) {
        $this->view = $view;
    }
    protected function run(): string
    {
        ...
        $this->view->registerCss('.lastPosts { background: #f1f1f1; }');
        ...
    }
}
```

To get a deep cloned `View` or `WebView` use `withClearedState()`: 

```php
$view = $view->withClearedState();
```

## Extensions
  
- [yiisoft/yii-view](https://github.com/yiisoft/yii-view) - a wrapper that is used in
  [Yii Framework]((https://www.yiiframework.com/)). Adds additional functionality for a WEB
  environment and compatibility with [PSR-7](https://www.php-fig.org/psr/psr-7) interfaces.
- [yiisoft/view-twig](https://github.com/yiisoft/view-twig) - an extension that provides a view renderer that will
  allow you to use the [Twig](https://twig.symfony.com) view template engine, instead of the default PHP renderer.

## Testing

### Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```shell
./vendor/bin/phpunit
```

### Mutation testing

The package tests are checked with [Infection](https://infection.github.io/) mutation framework with
[Infection Static Analysis Plugin](https://github.com/Roave/infection-static-analysis-plugin). To run it:

```shell
./vendor/bin/roave-infection-static-analysis-plugin
```

### Static analysis

The code is statically analyzed with [Psalm](https://psalm.dev/). To run static analysis:

```shell
./vendor/bin/psalm
```

## License

The Yii View Rendering Library is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).

### Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

### Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3en)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)
