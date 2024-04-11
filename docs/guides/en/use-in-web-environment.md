<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px">
    </a>
    <h1 align="center">Yii View Rendering Library</h1>
    <br>
</p>

# Use in the web environment

This guide describes extra functionality of the `Yiisoft\View\WebView` class intended for use in a web environment.
Please read the [Basic Functionality](basic-functionality.md) guide first.

To create `Yiisoft\View\WebView` class, you must specify two mandatory parameters:

```php
/**
 * @var Psr\EventDispatcher\EventDispatcherInterface $eventDispatcher
 */

$view = new \Yiisoft\View\WebView(
    '/path/to/views', // Full path to the directory of view templates.
    $eventDispatcher,
);
```

## Creating view templates

In an example below, a view is a simple PHP script that outputs information about posts in a loop.
Note that for example purpose, we've greatly simplified the template code. In practice, you may
want to add more content to it, such as `<head>` tags, main menu, etc.

```php
<?php

declare(strict_types=1);

/**
 * @var Yiisoft\View\WebView $this 
 * @var App\Blog\Post[] $posts
 */

use Yiisoft\Html\Html;
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title><?= Html::encode($this->getTitle()) ?></title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>
    <h1>Posts</h1>
    <?php foreach ($posts as $post): ?>
        <h2><?= Html::encode($post->getTitle()) ?></h2>
        <p><?= Html::encode($post->getDescription()) ?></p>
    <?php endforeach; ?>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
```

To have scripts and tags registered and rendered correctly, special methods are called in the example above:

- `beginPage()` - This method should be called at the very beginning of the view template.
- `endPage()` - This method should be called at the very end of the view template.
- `head()` - This method should be called within the `<head>` section of an HTML page. It generates a placeholder which
  will be replaced with the registered head HTML code (e.g. link tags, meta tags) when a page finishes rendering.
- `beginBody()` - This method should be called at the beginning of the `<body>` section. It generates a placeholder
  which will be replaced by the registered HTML code (e.g. СSS, JavaScript) targeted at the body begin position.
- `endBody()` - This method should be called at the end of the `<body>` section. It generates a placeholder which
  will be replaced by the registered HTML code (e.g. JavaScript) targeted at the body end position.

When registering [CSS](#registering-css), [JavaScript](#registering-javascript) and [link tags](#registering-link-tags),
you must specify the position in which this tag will be rendered. Positions are implemented by public constants:

- `POSITION_HEAD` - In the head section. Corresponds to the `head()` method.
- `POSITION_BEGIN` - At the beginning of the body section. Corresponds to the `beginBody()` method.
- `POSITION_END` - At the end of the body section. Corresponds to the `endBody()` method.
- `POSITION_READY` - Executed when HTML document composition is ready.
- `POSITION_LOAD` - Executed when HTML page is completely loaded.

Every Web page should have a title. You can set the title in this way:

```php
$view->setTitle('My page title');
```

Then in the view, make sure you have the following code in the `<head>` section:

```php
<title><?= \Yiisoft\Html\Html::encode($this->getTitle()) ?></title>
```

## Rendering

In addition to the `render()` and `renderFile()` methods, two `renderAjax()` and `renderAjaxString()`
methods have been added for the web environment to render AJAX requests.

The `renderAjax()` method is like `render()` except that it will surround the view being rendered with
the calls of `beginPage()`, `head()`, `beginBody()`, `endBody()` and `endPage()`. By doing so, the method can
inject JavaScript, CSS, and files, registered in the view template, into the rendering result.

```php
$view->renderAjax('blog/posts', [
    'posts' => $posts,
]);
```

The `renderAjaxString()` method is like `renderAjax()`, but only accepts a ready-made string for rendering.

```php
$view->renderAjaxString('content');
```

## Registering meta tags

Web pages usually need to generate various meta tags. Like page title, meta tags appear
in the `<head>` section. To specify which meta tags to add, two methods are provided:

```php
// Creates a meta tag from an array of attributes:
$view->registerMeta(['name' => 'keywords', 'content' => 'yii, framework, php']);

// Creates a meta tag from a `Yiisoft\Html\Tag\Meta` instance:
$view->registerMetaTag(\Yiisoft\Html\Html::meta()
    ->name('robots')
    ->content('noindex'));
```

The above code will register meta tags in a `Yiisoft\View\WebView` instance. The tags are added after the
view template finishes rendering. The following HTML code will be generated and inserted at the place
where you call `Yiisoft\View\WebView::head()` in the view template:

```html
<meta name="keywords" content="yii, framework, php">
<meta name="robots" content="noindex">
```

Note that if you call `registerMeta()` or `registerMetaTag()` many times, it will register many meta tags,
regardless whether the meta tags are the same or not. To make sure there is only a single instance of a meta tag of
the same type, you can specify a key as a second parameter when calling the methods. For example, the following code
registers two `description` meta tags. However, only the second one will be rendered.

```php
$view->registerMeta(
    ['name' => 'description', 'content' => 'This is my cool website made with Yii!'],
    'description',
);

$view->registerMetaTag(
    \Yiisoft\Html\Html::meta()
        ->name('description')
        ->content('This website is about funny raccoons.'),
    'description',
);
```

## Registering link tags

Like [meta tags](#registering-meta-tags), link tags are useful often, such as customizing favicon, pointing to
RSS feed or delegating OpenID to another server. You can work with link tags in a similar way as meta tags by using
`registerLink()` or `registerLinkTag()`. For example, in a content view, you can register a link tag like follows:

```php
// Creates a link tag from an array of attributes:
$view->registerLink([
    'title' => 'Live News for Yii',
    'rel' => 'alternate',
    'type' => 'application/rss+xml',
    'href' => 'https://www.yiiframework.com/rss.xml',
]);

// Creates a link tag from a `Yiisoft\Html\Tag\Link` instance:
$view->registerLinkTag(\Yiisoft\Html\Html::link('/myicon.png', [
    'rel' => 'icon',
    'type' => 'image/png',
]));
```

The code above will result in:

```html
<link title="Live News for Yii" rel="alternate" type="application/rss+xml" href="https://www.yiiframework.com/rss.xml">
<link rel="icon" type="image/png">
```

You can use the second parameter to specify the position at which the link tag should be inserted in a page.
Default is `Yiisoft\View\WebView::POSITION_HEAD`. Like registering meta tags, you can specify
a key to avoid creating duplicate link tags. In the `registerLink()` and `registerLinkTag()`
the key is specified as a third parameter.

## Registering CSS

To include a CSS file, you can use the registration of [link tags](#registering-link-tags)
or the `registerCssFile()` method:

```php
use Yiisoft\View\WebView;

// With a file path:
$view->registerCssFile('/path/to/style.css');
// Result: <link href="/path/to/file.css" rel="stylesheet">

// With a URL:
$view->registerCssFile('https//example.com/style.css');
// Result: <link href="https//example.com/style.css" rel="stylesheet">

// With the position change, default is `WebView::POSITION_HEAD`:
$view->registerCssFile('/path/to/style.css', WebView::POSITION_BEGIN);

// With the addition of attributes:
$view->registerCssFile('/path/to/style.css', WebView::POSITION_HEAD, [
    'media' => 'print',
]);

// With a specific of the identifying key.
$view->registerCssFile('/path/to/style.css', WebView::POSITION_HEAD, [], 'file-key');
// If the key isn't specified, the URL of the CSS file will be used instead.
```

The registration of the CSS code block is as follows:

```php
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

// Creates a style tag from code block:
$view->registerCss('.green{color:green;}', WebView::POSITION_HEAD, [
    'id' => 'green-class',
]);

// Creates a style tag from a file:
$view->registerCssFromFile('/path/to/file.css', WebView::POSITION_HEAD, [
    'id' => 'grey-class',
]);

// Creates a style tag from a `Yiisoft\Html\Tag\Base\NormalTag\Link` instance:
$view->registerStyleTag(
    Html::style('.red{color:red;}', ['id' => 'red-class']),
    WebView::POSITION_HEAD,
);
```

The code above will result in:

```html
<style>.green{color:green;}</style>
<style>.grey{color:grey;}</style>
<style>.red{color:red;}</style>
```

For all methods, the `POSITION_HEAD` position is used by default, and the last argument specifies the key that
identifies this block of CSS code. If the key isn't specified, the `md5()` hash of the CSS code block
will be used instead.

## Registering JavaScript

To include a JavaScript file, use the `registerJsFile()`method:

```php
use Yiisoft\View\WebView;

// With a file path:
$view->registerJsFile('/path/to/script.js');
// Result: <script src="/path/to/script.js"></script>

// With a URL:
$view->registerJsFile('https//example.com/script.js');
// Result: <script src="https//example.com/script.js"></script>

// With the position change, default is `WebView::POSITION_END`:
$view->registerJsFile('/path/to/script.js', WebView::POSITION_BEGIN);

// With the addition of attributes:
$view->registerJsFile('/path/to/script.js', WebView::POSITION_END, [
    'async' => true,
]);
// Result: <script src="/path/to/script.js" async></script>

// With a specific of the identifying key.
$view->registerJsFile('/path/to/script.js', WebView::POSITION_END, [], 'file-key');
// If the key isn't specified, the URL of the JavaScript file will be used instead.
```

The registration of the JavaScript code block is as follows:

```php
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

// Creates a style tag from code block:
$view->registerJs('alert(1);', WebView::POSITION_END);

// Creates a style tag from a `Yiisoft\Html\Tag\Base\NormalTag\Script` instance:
$view->registerScriptTag(
    Html::script('alert(2);', ['defer' => true]),
    WebView::POSITION_END,
);
```

The code above will result in:

```html
<script>alert(1);</script>
<script defer>alert(2);</script>
```

For both methods, the `POSITION_END` position is used by default, and the last argument specifies the key
that identifies this block of JavaScript code. If the key isn't specified, the `md5()` hash of the
JavaScript code block will be used instead.

Additionally, a separate method is provided for registering JavaScript variables:

```php
use Yiisoft\View\WebView;

$view->registerJs('username', 'John');

// With the position change, default is `WebView::POSITION_HEAD`:
$view->registerJsFile('age', 42, WebView::POSITION_BEGIN);
```

The code above will result in:

```html
<head>
    <script>var username = "John";</script>
</head>
<body>
    <script>var age = 42;</script>
</body>
```

The values of the variables will be encoded, using the `Yiisoft\Json\Json::htmlEncode()` method:

```php
$view->registerJs('data', ['username' => 'John', 'age' => 42]);
```

The code above will result in:

```html
<script>var data = {"username":"John","age":42};</script>
```

The name of variable will be used as a key, preventing duplicated variable names.

## Use with an asset manager

If you are using the [yiisoft/assets](https://github.com/yiisoft/assets) package, the `Yiisoft\View\WebView`
class provides methods for batch adding CSS and JavaScript data.

```php
/**
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Assets\AssetManager $assetManager
 */
 
$assetManager->register(MyAsset::class);

$this->addCssFiles($assetManager->getCssFiles());
$this->addCssStrings($assetManager->getCssStrings());
$this->addJsFiles($assetManager->getJsFiles());
$this->addJsStrings($assetManager->getJsStrings());
$this->addJsVars($assetManager->getJsVars());
```

These methods process the CSS and JavaScript configuration created by the asset manager and convert it into HTML code.

## WebView events

The `Yiisoft\View\WebView` class triggers several events during the view rendering process. Events are classes:

- `Yiisoft\View\Event\WebView\BeforeRender` - triggered at the beginning of rendering a view template.
  Listeners of this event may set `$event->stopPropagation()` to be false to cancel the rendering process.
- `Yiisoft\View\Event\WebView\AfterRender` - triggered at the ending of rendering a view template.
  Listeners of this event can get data about the rendering results using the methods of this class.
- `Yiisoft\View\Event\WebView\PageBegin` - triggered by the call of `Yiisoft\View\WebView::beginPage()` in the view template.
- `Yiisoft\View\Event\WebView\PageEnd` - triggered by the call of `Yiisoft\View\WebView::endPage()` in the view template.
- `Yiisoft\View\Event\WebView\Head` - triggered by the call of `Yiisoft\View\WebView::head()` in the view template.
- `Yiisoft\View\Event\WebView\BodyBegin` - triggered by the call of `Yiisoft\View\WebView::beginBody()` in the view template.
- `Yiisoft\View\Event\WebView\BodyEnd` - triggered by the call of `Yiisoft\View\WebView::endBody()` in the view template.

These events are passed to the `Psr\EventDispatcher\EventDispatcherInterface` implementation,
which is specified in the constructor when the `Yiisoft\View\WebView` instance is initialized.

## Security

When creating views that generate HTML pages, it's important that you properly encode and/or
filter all the data when outputted. Otherwise, your application may be subject to
[cross-site scripting](https://en.wikipedia.org/wiki/Cross-site_scripting) attacks.

To display a plain text, encode it first by calling `Yiisoft\Html\Html::encode()`.
For example, the following code encodes the username before displaying it:

```php
<?php

declare(strict_types=1);

/**
 * @var Yiisoft\View\WebView $this 
 * @var App\User\User $user 
 */

use Yiisoft\Html\Html;
?>

<div class="username">
    <?= Html::encode($user->name) ?>
</div>
```

If you need to securely display HTML content, you can use content filtering tools such as
[HTML Purifier](https://github.com/ezyang/htmlpurifier). A drawback is that it isn't very
performant, so consider caching the result.
