## Documentação

O pacote fornece dois casos de uso para gerenciar modelos de visualização:

- [Funcionalidade básica](basic-functionality.md) para uso em qualquer ambiente.
- [Funcionalidade avançada](use-in-web-environment.md) para uso em ambiente web.

### State of `View` and `WebView` services

While being immutable and, by itself, stateless, both `View` and `WebView` services have sets of stateful and mutable
data.

`View` service:
- parameters,
- blocks,
- theme,
- locale.

`WebView` service:
- parameters,
- blocks,
- theme,
- locale,
- title,
- meta and link tags,
- JS/CSS strings,
- JS/CSS files.

The state of `View` and `WebView` isn't cloned when the services are cloned. So when
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

and then register CSS in a widget:

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

#### Locale state

You can change the locale by using `setLocale()`, which will be applied to all other instances that used current state
including existing ones. If you need to change the locale only for a single instance, you can use the immutable
`withLocale()` method. Locale will be applied to all views rendered within views with `render()` calls.

Example with mutable method:

```php
final class LocaleMiddleware implements MiddlewareInterface
{    
    ...
    private WebView $view;
    ...
    public function __construct (
        ...
        WebView $view
        ...
    ) {
        $this->view = $view;
    }
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        ...
        $this->view->setLocale($locale);
        ...
    }
}
````

Example with immutable method:

```php
final class BlogController {
    private WebView $view;
    public function __construct (WebView $view) {
        $this->view = $view;
    }
    public function index() {
        return $this->view->withLocale('es')->render('index');
    }
}
```

#### Reset state

To get a deep cloned `View` or `WebView` use `withClearedState()`: 

```php
$view = $view->withClearedState();
```

## Extensions
  
- [yiisoft/yii-view](https://github.com/yiisoft/yii-view) - a wrapper that's used in
  [Yii Framework]((https://www.yiiframework.com/)).
  Adds extra functionality for a web environment and compatibility 
  with [PSR-7](https://www.php-fig.org/psr/psr-7) interfaces.
- [yiisoft/view-twig](https://github.com/yiisoft/view-twig) - an extension that provides a view renderer that will
  allow you to use the [Twig](https://twig.symfony.com) view template engine, instead of the default PHP renderer.
