<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px">
    </a>
    <h1 align="center">Yii View Rendering Library</h1>
    <br>
</p>

# Uso no ambiente web

Este guia descreve funcionalidades extras da classe `Yiisoft\View\WebView` destinada ao uso em um ambiente web.
Por favor, leia primeiro o guia [Funcionalidade básica](basic-functionality.md).

Para criar a classe `Yiisoft\View\WebView`, você deve especificar dois parâmetros obrigatórios:

```php
/**
 * @var Psr\EventDispatcher\EventDispatcherInterface $eventDispatcher
 */

$view = new \Yiisoft\View\WebView(
    '/path/to/views', // Full path to the directory of view templates.
    $eventDispatcher,
);
```

## Criando modelos de visualização

No exemplo abaixo, uma visualização é um script PHP simples que gera informações sobre postagens em um loop.
Observe que, por exemplo, simplificamos bastante o código do modelo. Na prática, você pode
adicionar mais conteúdo a ele, como tags `<head>`, menu principal, etc.

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

Para que scripts e tags sejam registrados e renderizados corretamente, métodos especiais são chamados no exemplo acima:

- `beginPage()` - Este método deve ser chamado bem no início do modelo de visualização.
- `endPage()` - Este método deve ser chamado bem no final do modelo de visualização.
- `head()` - Este método deve ser chamado dentro da seção `<head>` de uma página HTML. Ele gera um espaço reservado que
   será substituído pelo código HTML principal registrado (por exemplo, tags de link, meta tags) quando a renderização de uma página terminar.
- `beginBody()` - Este método deve ser chamado no início da seção `<body>`. Ele gera um espaço reservado
   que será substituído pelo código HTML registrado (por exemplo, СSS, JavaScript) na posição inicial do `<body>`.
- `endBody()` - Este método deve ser chamado no final da seção `<body>`. Ele gera um espaço reservado que
   será substituído pelo código HTML registrado (por exemplo, JavaScript) direcionado à posição final do `<body>`.

Ao registrar [CSS](#registrando-css), [JavaScript](#registrando-javascript) e [link tags](#registrando-tags-de-link),
você deve especificar a posição em que esta tag será renderizada. As posições são implementadas por constantes públicas:

- `POSITION_HEAD` - Corresponde à seção `<body>`. Corresponde ao método `head()`.
- `POSITION_BEGIN` - No começo da seção `<body>`. Corresponde ao método `beginBody()`.
- `POSITION_END` - No fim da seção `<body>`. Corresponde ao método `endBody()`.
- `POSITION_READY` - Executado quando a renderização do documento HTML está pronta.
- `POSITION_LOAD` - Executado quando a página HTML foi completamente carregada.

Cada página da Web deve ter um título. Você pode definir o título desta forma:

```php
$view->setTitle('My page title');
```

Então, na view, certifique-se de ter o seguinte código na seção `<head>`:

```php
<title><?= \Yiisoft\Html\Html::encode($this->getTitle()) ?></title>
```

## Renderização

Além dos métodos `render()` e `renderFile()`, dois métodos, `renderAjax()` e `renderAjaxString()`,
foram adicionados ao ambiente web para renderizar solicitações AJAX.

O método `renderAjax()` é semelhante ao `render()` exceto que envolverá a visualização que está sendo renderizada com
as chamadas de `beginPage()`, `head()`, `beginBody()`, `endBody()` e `endPage()`. Ao fazer isso, o método pode
injetar JavaScript, CSS e arquivos registrados no modelo de visualização no resultado da renderização.

```php
$view->renderAjax('blog/posts', [
    'posts' => $posts,
]);
```

O método `renderAjaxString()` é semelhante a `renderAjax()`, mas aceita apenas uma string pronta para renderização.

```php
$view->renderAjaxString('content');
```

## Registrando metatags

As páginas da Web geralmente precisam gerar várias metatags. Assim como o título da página, as meta tags aparecem
na seção `<head>`. Para especificar quais metatags adicionar, dois métodos são fornecidos:

```php
// Creates a meta tag from an array of attributes:
$view->registerMeta(['name' => 'keywords', 'content' => 'yii, framework, php']);

// Creates a meta tag from a `Yiisoft\Html\Tag\Meta` instance:
$view->registerMetaTag(\Yiisoft\Html\Html::meta()
    ->name('robots')
    ->content('noindex'));
```

O código acima registrará meta tags em uma instância `Yiisoft\View\WebView`. As tags são adicionadas após o
template de visualização terminar a renderização. O seguinte código HTML será gerado e inserido no local
`Yiisoft\View\WebView::head()` no modelo de visualização:

```html
<meta name="keywords" content="yii, framework, php">
<meta name="robots" content="noindex">
```

Observe que se você chamar `registerMeta()` ou `registerMetaTag()` muitas vezes, ele registrará muitas meta tags,
independentemente de as meta tags serem iguais ou não. Para garantir que haja apenas uma única instância de uma meta tag de
do mesmo tipo, você pode especificar uma chave como segundo parâmetro ao chamar os métodos. Por exemplo, o seguinte código
registra duas meta tags `description`. No entanto, apenas o segundo será renderizado.

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

## Registrando tags de links

Assim como as [meta tags](#registrando-metatags), as tags de link são frequentemente úteis, como personalizar favicon, apontar para
feeds RSS ou delegação de OpenID para outro servidor. Você pode trabalhar com tags de link de maneira semelhante às meta tags usando
`registerLink()` ou `registerLinkTag()`. Por exemplo, em uma visualização de conteúdo, você pode registrar uma tag de link como segue:

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

O código acima resultará em:

```html
<link title="Live News for Yii" rel="alternate" type="application/rss+xml" href="https://www.yiiframework.com/rss.xml">
<link rel="icon" type="image/png">
```

Você pode usar o segundo parâmetro para especificar a posição em que a tag do link deve ser inserida em uma página.
O padrão é `Yiisoft\View\WebView::POSITION_HEAD`. Assim como registrar meta tags, você pode especificar
uma chave para evitar a criação de tags de link duplicadas. Em `registerLink()` e `registerLinkTag()`
a chave é especificada como um terceiro parâmetro.

## Registrando CSS

Para incluir um arquivo CSS, você pode usar o registro de [links tags](#registrando-tags-de-links)
ou o método `registerCssFile()`:

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

O registro do bloco de código CSS é o seguinte:

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

O código acima resultará em:

```html
<style>.green{color:green;}</style>
<style>.grey{color:grey;}</style>
<style>.red{color:red;}</style>
```

Para todos os métodos, a posição `POSITION_HEAD` é usada por padrão, e o último argumento especifica a chave que
identifica este bloco de código CSS. Se a chave não for especificada, o hash `md5()` do bloco de código CSS
será usado em seu lugar.

## Registrando JavaScript

Para incluir um arquivo JavaScript, use o método `registerJsFile()`:

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

O registro do bloco de código JavaScript é o seguinte:

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

O código acima resultará em:

```html
<script>alert(1);</script>
<script defer>alert(2);</script>
```

Para ambos os métodos, a posição `POSITION_END` é usada por padrão e o último argumento especifica a chave
que identifica este bloco de código JavaScript. Se a chave não for especificada, o hash `md5()` do
bloco de código JavaScript será usado em seu lugar.

Além disso, é fornecido um método separado para registrar variáveis JavaScript:

```php
use Yiisoft\View\WebView;

$view->registerJs('username', 'John');

// With the position change, default is `WebView::POSITION_HEAD`:
$view->registerJsFile('age', 42, WebView::POSITION_BEGIN);
```

O código acima resultará em:

```html
<head>
    <script>var username = "John";</script>
</head>
<body>
    <script>var age = 42;</script>
</body>
```

Os valores das variáveis serão codificados, utilizando o método `Yiisoft\Json\Json::htmlEncode()`:

```php
$view->registerJs('data', ['username' => 'John', 'age' => 42]);
```

O código acima resultará em:

```html
<script>var data = {"username":"John","age":42};</script>
```

O nome da variável será usado como chave, evitando nomes de variáveis duplicados.

## Uso com um gerenciador de ativos

Se você estiver usando o pacote [yiisoft/assets](https://github.com/yiisoft/assets), a classe `Yiisoft\View\WebView`
fornece métodos para adição em lote de dados CSS e JavaScript.

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

Esses métodos processam a configuração CSS e JavaScript criada pelo gerenciador de ativos e a convertem em código HTML.

## Eventos WebView

A classe `Yiisoft\View\WebView` dispara vários eventos durante o processo de renderização da visualização. Eventos são classes:

- `Yiisoft\View\Event\WebView\BeforeRender` - acionado no início da renderização de um modelo de visualização.
   Os ouvintes deste evento podem definir `$event->stopPropagation()` como falso para cancelar o processo de renderização.
- `Yiisoft\View\Event\WebView\AfterRender` - acionado no final da renderização de um modelo de visualização.
   Os ouvintes deste evento podem obter dados sobre os resultados da renderização usando os métodos desta classe.
- `Yiisoft\View\Event\WebView\PageBegin` - acionado pela chamada de `Yiisoft\View\WebView::beginPage()` no modelo de visualização.
- `Yiisoft\View\Event\WebView\PageEnd` - acionado pela chamada de `Yiisoft\View\WebView::endPage()` no modelo de visualização.
- `Yiisoft\View\Event\WebView\Head` - acionado pela chamada de `Yiisoft\View\WebView::head()` no modelo de visualização.
- `Yiisoft\View\Event\WebView\BodyBegin` - acionado pela chamada de `Yiisoft\View\WebView::beginBody()` no modelo de visualização.
- `Yiisoft\View\Event\WebView\BodyEnd` - acionado pela chamada de `Yiisoft\View\WebView::endBody()` no modelo de visualização.

Esses eventos são passados para a implementação `Psr\EventDispatcher\EventDispatcherInterface`,
que é especificado no construtor quando a instância `Yiisoft\View\WebView` é inicializada.

## Segurança

Ao criar visualizações que geram páginas HTML, é importante que você codifique e/ou
filtre todos os dados quando gerados. Caso contrário, a sua aplicação poderá estar sujeita a
[vulnerabilidade cross-site](https://pt.wikipedia.org/wiki/Cross-site_scripting).

Para exibir um texto simples, codifique-o primeiro chamando `Yiisoft\Html\Html::encode()`.
Por exemplo, o código a seguir codifica o nome de usuário antes de exibi-lo:

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

Se precisar exibir conteúdo HTML com segurança, você pode usar ferramentas de filtragem de conteúdo, como
[HTML Purifier](https://github.com/ezyang/htmlpurifier). Uma desvantagem é que não tem bom
desempenho, então considere armazenar o resultado em cache.
