## Uso geral

O pacote fornece dois casos de uso para gerenciamento de modelos de visualização:

- [Funcionalidade básica](basic-funcionality.md) para uso em qualquer ambiente.
- [Funcionalidade avançada](use-in-web-environment.md) para uso em ambiente web.

### Estado dos serviços `View` e `WebView`

Embora sejam imutáveis e, por si só, sem estado, os serviços `View` e `WebView` possuem conjuntos de serviços com estado e dados mutáveis.

Serviço `View`:
- parâmetros,
- blocos,
- tema,
- local.

Serviço `WebView`:
- parâmetros,
- blocos,
- tema,
- localidade,
- título,
- meta tags e links,
- strings JS/CSS,
- Arquivos JS/CSS.

O estado de `View` e `WebView` não são clonados quando os serviços são clonados. Então quando
usar com `with*()`, instâncias novas e antigas compartilharão o mesmo conjunto de dados mutáveis com estado. Permitem, por exemplo,
obter `WebView` por meio de dicas de tipo em um controlador e alterar o caminho do contexto:

```php
final class BlogController {
    private WebView $view;
    public function __construct (WebView $view) {
        $this->view = $view->withContextPath(__DIR__.'/views');
    }
}
```

e então registrar CSS em um widget:

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

#### Estado Locale (internacionalização)

Você pode alterar a localidade usando `setLocale()`, que será aplicado a todas as outras instâncias que usaram o estado atual
incluindo os existentes. Se você precisar alterar a localidade apenas para uma única instância, poderá usar o método imutável
`withLocale()`. A localidade será aplicada a todas as visualizações renderizadas nas visualizações com chamadas `render()`.

Exemplo com método mutável:

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
```

Exemplo com método imutável:

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

#### Redefinir o estado

Para obter um `View` ou `WebView` clonado profundamente, use `withClearedState()`:

```php
$view = $view->withClearedState();
```

## Extensões
  
- [yiisoft/yii-view](https://github.com/yiisoft/yii-view) - um wrapper usado na [Estrutura Yii](https://www.yiiframework.com/).
   Adiciona funcionalidade extra para um ambiente web e compatibilidade
   com interfaces [PSR-7](https://www.php-fig.org/psr/psr-7).
- [yiisoft/view-twig](https://github.com/yiisoft/view-twig) - uma extensão que fornece um renderizador de visualização que
   permite que você use o mecanismo de modelo de visualização [Twig](https://twig.symfony.com), em vez do renderizador PHP padrão.