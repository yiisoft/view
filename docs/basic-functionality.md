# Basic functionality

The package provides a `Yiisoft\View\View` class with basic functionality for managing views, and
a `Yiisoft\View\WebView` class with advanced functionality for use in a WEB environment. This guide applies to both
classes, but examples will be provided using the `Yiisoft\View\View`. For advanced examples with
`Yiisoft\View\WebView` functionality, see the "[Use in the WEB environment](use-in-web-environment.md)" guide.

To create a `Yiisoft\View\View` class, you must specify two mandatory parameters:

```php
/**
 * @var Psr\EventDispatcher\EventDispatcherInterface $eventDispatcher
 */

$view = new \Yiisoft\View\View(
    '/path/to/views', // Full path to the directory of view templates.
    $eventDispatcher,
);
```

## Creating view templates

In the example below, the view template is a simple PHP script that outputs information about posts in a loop:

```php
<?php

declare(strict_types=1);

/**
 * @var Yiisoft\View\View $this 
 * @var App\Blog\Post[] $posts 
 */
?>

Posts:

<?php foreach ($posts as $post): ?>
    Title: <?= $post->getTitle() . "\n" ?>
    Description: <?= $post->getDescription() . "\n\n"?>
<?php endforeach; ?>
```

Within a view, you can access `$this` which refers to the `Yiisoft\View\View` managing and rendering current view template.
Besides `$this`, there may be other variables in a view, such as `$posts` in the above example.
These variables represent the data that is passed as parameters when rendering the view. Note that `<?=`
does not automatically encode variables for safe use with HTML and you should take care of it.

> Tip: The predefined variables are listed in a comment block at beginning of a view so that they
> can be recognized by IDEs. It is also a good way of documenting your views.

## Rendering

To render the file shown above, two methods are provided: `render()` and `renderFile()`.

The `renderFile()` method accepts a full absolute path of the view file to be rendered,
and an array of parameters (name-value pairs) that will be available in the view template:

```php
$view->renderFile('/path/to/views/blog/posts.php', [
    'posts' => $posts,
]);
```

The `render()` method is a wrapper over `renderFile()` with additional functionality:

```php
$view->render('blog/posts', [
    'posts' => $posts,
]);
```

Instead of an absolute file path, it accepts a name of a view in one of the following formats:

- A name of a view starting with a slash (for example, `/blog/posts`). It will be prepended with
  the base path that was passed to `Yiisoft\View\View` constructor. For example, `/blog/posts`
  will be resolved into `/path/to/views/blog/posts.php`.
- A name of a view without the starting slash (e.g. `blog/posts`). The corresponding view file will be looked for
  in the context (instance of `Yiisoft\View\ViewContextInterface`) set via `$view->withContext()`. If the
  context instance was not set, it will be looked for under the directory containing the view currently being
  rendered.

The view name may omit a file extension. In this case, `.php` will be used as the extension.
For example, the view name `blog/posts` correspond to the file name `blog/posts.php`.
You can change the default file extension as follows:

```php
$view->getDefaultExtension(); // 'php'

$view = $view->withDefaultExtension('tpl');

$view->getDefaultExtension(); // 'tpl'
```

Rendering methods could be called inside views to render nested views:

```php
<?php

declare(strict_types=1);

/** 
 * @var Yiisoft\View\View $this
 * @var App\Blog\Post[] $posts 
 */
?>

Title

<?= $this->renderFile('/path/to/views/any/file.php') ?>

<?= $this->renderFile('blog/posts', ['posts' => $posts]) ?>
```

By default, rendering simply includes a view file as a regular PHP file, captures its output, and returns
it as a string. You can override this behavior by implementing `\Yiisoft\View\TemplateRendererInterface`
and use that implementation via `$view->withRenderers()` method.

```php
$view = $view->withRenderers([
    'tpl' => new MyCustomViewRenderer(),
    'twig' => new \Yiisoft\Yii\Twig\ViewRenderer($environment),
]);
```

During rendering, the file extension will be analyzed and if the array key matches the file extension,
the corresponding renderer will be applied.

## Theming

Theming is a way to replace a set of views with another set of views without the need to touch the original
view rendering code. You can use theming to systematically change the look and feel of an application.

To use theming, you should create and configure a `Yiisoft\View\Theme` instance
and set it using the `setTheme()` method:

```php
$theme = new \Yiisoft\View\Theme([
    '/path/to/views' => '/path/to/views/themes/basic/views',
]);

$view->setTheme($theme);
```

The first parameter of the `Theme` class accepts an array, which is a path map for mapping view directories to the
corresponding theme versions. For example, if you call `$view->render('blog/posts')`, you will be rendering the
view file `/path/to/views/blog/posts.php`. However, if you enable theming as shown above, the view file
`/path/to/views/themes/basic/views/blog/posts.php` will be rendered instead.

In addition to view files, themes could contain images, CSS styles, JS scripts and other assets.
To have access to these assets in a view, two more optional parameters should be
passed when creating an instance of the `Yiisoft\View\Theme`:

```php
$theme = new \Yiisoft\View\Theme(
    ['/path/to/views' => '/path/to/views/themes/basic/views'],
    '/path/to/views/themes/basic/assets', // The base directory that contains the themed assets.
    '/path/to/public/themes/basic/assets', // The base URL of the themed assets.
);

$view->setTheme($theme);
```

In a view, you can access the theme using the `getTheme()` method and manage assets as follows:

```php
<?php

declare(strict_types=1);

/** @var \Yiisoft\View\View $this */

$theme = $this->getTheme();

// Returns: '/path/to/views/themes/basic/assets'
$basePath = $theme->getBasePath();

// Returns: '/path/to/public/themes/basic/assets'
$baseUrl = $theme->getBasePath();

// Returns: '/path/to/views/themes/basic/assets/img/logo.svg'
$file = $theme->getPath('img/logo.svg');

// Returns: '/path/to/public/themes/basic/assets/img/logo.svg'
$url = $theme->getUrl('img/logo.svg');
```

## Localization

Two languages are defined for localization with the default value `en`:

- `$language` - The target language that the file should be localized to.
- `$sourceLanguage` - The language that the original file is in.

You can change default values:

```php
$view = $view->withSourceLanguage('es');
$view->setLanguage('fr');
```

In order to use multiple languages it is necessary to create subdirectories at directory level matching template files
of the view. For example, if there is a view `/path/to/views/blog/posts.php` and we translate it into Russian, create
a subdirectory `ru-RU` or `ru`. In this subdirectory, create a file for the Russian language:
`/path/to/views/blog/ru/posts.php`.

To localize the file, use the `localize($file, $language, $sourceLanguage)` method:

```php
$file = '/path/to/views/blog/posts.php';

// Returns: '/path/to/views/blog/posts.php'
$view->localize($file);

// Returns: '/path/to/views/blog/posts.php'
$view->localize($file, 'en');

// Returns: '/path/to/views/blog/ru/posts.php'
$view->localize($file, 'ru');

// Returns: '/path/to/views/blog/ru/posts.php'
$view->localize($file, 'ru-RU');

// Returns: '/path/to/views/blog/ru/posts.php'
$view->localize($file, 'ru', 'en');

// Returns: '/path/to/views/blog/posts.php'
$view->localize($file, 'ru', 'ru');
```

File choice is based on the specified language code. A file with the same name will be looked
for under the subdirectory whose name is the same as the language code. For example, given the file
`/path/to/views/blog/posts.php` and the language code `ru-RU`, the localized file will be looked
for as `/path/to/views/blog/ru-RU/posts.php`. If the file is not found, it will try a fallback
with just a language code that is `ru` i.e. `/path/to/views/blog/ru/posts.php`.

> If the target file is not found, the original file will be returned.
> If the target and the source language codes are the same, the original file will be returned.

## Sharing data among views

You can use blocks and common parameters to share data among views. Blocks allow you to specify the string content
of a view in one place while displaying it in another. First, in a content view, define one or multiple blocks:

```php
<?php

declare(strict_types=1);

/** @var Yiisoft\View\View $this */

$this->setBlock('block-id-1', '...content of block1...');

$this->setBlock('block-id-2', '...content of block2...');
```

Then, display the blocks if there are any, or the default content if the block is not defined:

```php
<?php

declare(strict_types=1);

/** @var Yiisoft\View\View $this */
?>
...

<?php if ($this->hasBlock('block-id-1')): ?>
    <?= $this->getBlock('block-id-1') ?>
<?php else: ?>
    ... default content for block1 ...
<?php endif; ?>

...

<?php if ($this->hasBlock('block-id-2')): ?>
    <?= $this->getBlock('block-id-2') ?>
<?php else: ?>
    ... default content for block2 ...
<?php endif; ?>

...
```

General parameters are used in the same way, but unlike blocks, the value can be of any type.
This is convenient if you need to set some data that will be available in all views:

```php
$view->setParameter('urlGenerator', new MyUrlGenerator());
$view->setParameter(SomeObject::class, new SomeObject());

$view->render('blog/posts');
```

Using the `urlGenerator` in all views:

```php
<?php

declare(strict_types=1);

/** @var Yiisoft\View\View $this */
/** @var App\Blog\Post[] $posts */

$urlGenerator = $this->getParameter('urlGenerator');
?>

<?php if ($this->hasParameter(SomeObject::class)): ?>
    <?= $this
        ->getParameter(SomeObject::class)
        ->getContent() ?>
<?php endif; ?>

Posts:

<?php foreach ($posts as $post): ?>
    Title: <?= $post->getTitle() . "\n" ?>
    Description: <?= $post->getDescription() . "\n"?>
    URL: <?= $urlGenerator->generate($post->getUrl()) . "\n\n"?>
<?php endforeach; ?>
```

You can not call the `hasParameter()` method, but pass the default value to the `getParameter()` method.
At the same time, if the default value is not passed, and the requested parameter does not exist,
an `InvalidArgumentException` exception will be thrown.

```php
// return "default-value"
$view->getParameter('parameter-name', 'default-value');

// throw InvalidArgumentException
$view->getParameter('parameter-name');
```

To delete data, use `removeBlock('id')` and `removeParameter('id')` methods.

## Content caching

In some cases, caching content can significantly improve performance of your application. For example,
if a page displays a summary of yearly sales in a table, you can store this table in a cache to eliminate
the time needed to generate this table for each request.

To cache content, the `Yiisoft\View\Cache\CachedContent` class is provided, which is used in the following way:

```php
/**
 * @var Yiisoft\Cache\CacheInterface $cache
 * @var Yiisoft\View\View $view
 */

use Yiisoft\View\Cache\CachedContent;

// Creating a cached content instance
$cachedContent = new CachedContent('cache-id', $cache);

// Trying to get content from the cache
$content = $cachedContent->get();

// If the content is not in the cache, then we will generate it and add it to the cache
if ($content === null) {
  // Generating content
  $content = $view->render('view/name');
  // Adding content to the cache
  $cachedContent->cache($content); 
}

// Content output
echo $content;
```

In addition to the content, the `Yiisoft\View\Cache\CachedContent::cache()` method
accepts three additional optional arguments:

- `$ttl (int)` - The TTL of the cached content. Default is `60`.
- `$dependency (Yiisoft\Cache\Dependency\Dependency|null)` - The dependency of the cached content. Default is `null`.
- `$beta (float)` - The value for calculating the range that is used for "Probably early expiration". Default is `1.0`.

For more information about caching and cache options, see the documentation of the
[yiisoft/cache package](https://github.com/yiisoft/cache).

### Dynamic Content

When caching content, you may encounter the situation where a large fragment of content is relatively
static except one or a few places. For example, a page header may display a main menu bar together with
a name of the current user. Another problem is that the content being cached may contain PHP code that must
be executed for every request. Both problems can be solved by using the `Yiisoft\View\Cache\DynamicContent` class.

```php
/**
 * @var Yiisoft\Cache\CacheInterface $cache
 * @var Yiisoft\View\View $view
 */

use Yiisoft\View\Cache\CachedContent;
use Yiisoft\View\Cache\DynamicContent;

// Creating a dynamic content instance
$dynamicContent = new DynamicContent(
    'dynamic-id',
    static function (array $parameters): string {
        return strtoupper("{$parameters['a']} - {$parameters['b']}");
    },
    ['a' => 'string-a', 'b' => 'string-b']
);

// Creating a cached content instance
$cachedContent = new CachedContent('cache-id', $cache, [$dynamicContent]);

// Trying to get content from the cache
$content = $cachedContent->get();

// If the content is not in the cache, then we will generate it and add it to the cache
if ($content === null) {
  // Generating content
  // In the view, we call `$dynamicContent->placeholder()`
  $content = $view->render('view/name', ['dynamicContent' => $dynamicContent]);
  // Adding content to the cache
  $cachedContent->cache($content); 
}

// Content output
echo $content;
```

A dynamic content means a fragment of output that should not be cached even if it is enclosed within a fragment cache.
You may call `$dynamicContent->placeholder()` within a cached fragment to insert dynamic content at the desired place
of the view, like the following:

```php
<?php

declare(strict_types=1);

/**
 * @var Yiisoft\View\View $this
 * @var Yiisoft\View\Cache\DynamicContent $dynamicContent  
 */
?>

Content to be cached ...

<?= $dynamicContent->placeholder() ?>

Content to be cached ...
```


For caching content fragments, it is much more convenient to use dynamic content using the
`Yiisoft\Yii\Widgets\FragmentCache` widget from the
[yiisoft/yii-widgets](https://github.com/yiisoft/yii-widgets) package:

```php
use Yiisoft\View\Cache\DynamicContent;
use Yiisoft\Yii\Widgets\FragmentCache;

// Creating a dynamic content instance
$dynamicContent = new DynamicContent(
    'dynamic-id',
    static function (array $parameters): string {
        return strtoupper("{$parameters['a']} - {$parameters['b']}");
    },
    ['a' => 'string-a', 'b' => 'string-b']
);

// We use the widget as a wrapper over the content that should be cached:
FragmentCache::widget()
    ->id('cache-id')
    ->dynamicContents($dynamicContent)
    ->begin();
    echo 'Content to be cached ...';
    echo $dynamicContent->placeholder();
    echo 'Content to be cached ...';
FragmentCache::end();
```

### Variations

Content being cached may be varied according to some parameters. For example, for a Web application supporting
multiple languages, the same piece of view code may generate the content in different languages. Therefore, you
may want to make the cached content varied according to the current application language.

To specify cache variations, you need to pass the third parameter to the constructor,
which should be an array of string values, each representing a particular variation factor.
For example, to make the cached content varied by the language, you may use the following code:

```php
/**
 * @var Yiisoft\Cache\CacheInterface $cache
 * @var Yiisoft\View\Cache\DynamicContent $dynamicContent
 */

$cachedContent = new \Yiisoft\View\Cache\CachedContent(
    'cache-id',
    $cache,
    [$dynamicContent],
    ['ru'],
);
```

## View Events

The `Yiisoft\View\View` class triggers several events during the view rendering process. Events are classes:

- `Yiisoft\View\Event\View\BeforeRender` - triggered at the beginning of rendering a view template.
  Listeners of this event may set `$event->stopPropagation()` to be false to cancel the rendering process.
- `Yiisoft\View\Event\View\AfterRender` - triggered at the ending of rendering a view template.
  Listeners of this event can get data about the rendering results using the methods of this class.
- `Yiisoft\View\Event\View\PageBegin` - triggered by the call of `Yiisoft\View\View::beginPage()` in the view template.
- `Yiisoft\View\Event\View\PageEnd` - triggered by the call of `Yiisoft\View\View::endPage()` in the view template.

These events are passed to the `Psr\EventDispatcher\EventDispatcherInterface` implementation,
which is specified in the constructor when the `Yiisoft\View\View` instance is initialized.

In order for the `Yiisoft\View\Event\View\PageBegin` and `Yiisoft\View\Event\View\PageEnd` events to be triggered,
you must call the corresponding methods in the view template:

```php
<?php

declare(strict_types=1);

/**
 * @var Yiisoft\View\View $this
 * @var App\Blog\Post[] $posts 
 */
?>
<?php $this->beginPage() ?>

...

Posts:

<?php foreach ($posts as $post): ?>
    Title: <?= $post->getTitle() . "\n" ?>
    Description: <?= $post->getDescription() . "\n\n"?>
<?php endforeach; ?>

...

<?php $this->endPage() ?>
```

## Using with event loop

The `Yiisoft\View\View` and `Yiisoft\View\WebView` instances are stateful, so when you build long-running applications
with tools like [Swoole](https://www.swoole.co.uk/) or [RoadRunner](https://roadrunner.dev/) you should reset
the state at every request. For this purpose, you can use the `clear()` method.
