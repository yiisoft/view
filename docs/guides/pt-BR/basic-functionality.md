# Funcionalidade básica

O pacote fornece uma classe `Yiisoft\View\View` com funcionalidade básica para gerenciar visualizações, e
uma classe `Yiisoft\View\WebView` com funcionalidade avançada para uso em um ambiente web. Este guia se aplica a ambas
classes, mas exemplos serão fornecidos usando `Yiisoft\View\View`. Para exemplos avançados na
Funcionalidade `Yiisoft\View\WebView`, consulte o guia "[Usar no ambiente web](use-in-web-environment.md)".

Para criar uma classe `Yiisoft\View\View`, você deve especificar dois parâmetros obrigatórios:

```php
/**
 * @var Psr\EventDispatcher\EventDispatcherInterface $eventDispatcher
 */

$view = new \Yiisoft\View\View(
    '/path/to/views', // Full path to the directory of view templates.
    $eventDispatcher,
);
```

## Criando modelos de visualização

No exemplo abaixo, o modelo de visualização é um script PHP simples que gera informações sobre postagens em um loop:

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

Dentro de uma view, você pode acessar `$this` que se refere ao `Yiisoft\View\View` que gerencia e renderiza o modelo da view atual.
Além de `$this`, pode haver outras variáveis em uma view, como `$posts` no exemplo acima.
Essas variáveis representam os dados passados como parâmetros ao renderizar a visualização. Observe que `<?=`
não codifica variáveis automaticamente para uso seguro com HTML e você deve cuidar disso.

> Dica: As variáveis predefinidas são listadas em um bloco de comentários no início de uma visualização para que possam
> ser reconhecidas por IDEs. Também é uma boa maneira de documentar suas opiniões.

## Renderização

Para renderizar o arquivo mostrado acima, existem dois métodos: `render()` e `renderFile()`.

O método `renderFile()` aceita um caminho absoluto completo do arquivo de visualização a ser renderizado,
e uma matriz de parâmetros (pares nome-valor) que estarão disponíveis no modelo de visualização:

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

Em vez de um caminho de arquivo absoluto, ele aceita o nome de uma visualização em um dos seguintes formatos:

- O nome de uma visualização começando com uma barra (por exemplo, `/blog/posts`). Será prefixado com
   o caminho base que foi passado para o construtor `Yiisoft\View\View`. Por exemplo, `/blog/posts`
   será resolvido em `/path/to/views/blog/posts.php`.
- O nome de uma visualização sem a barra inicial (como `blog/posts`). O arquivo de visualização correspondente será procurado
   no contexto da instância de `Yiisoft\View\ViewContextInterface` definido via `$view->withContext()`. Se o
   a instância de contexto não foi definida, ela  procurará no diretório que contém a visualização que está sendo atualmente
   renderizado.

O nome da visualização pode omitir uma extensão de arquivo. Nesse caso, extensões substitutas serão usadas como extensão.
A extensão de fallback padrão é `php`. Por exemplo, o nome da visualização `blog/posts` corresponde ao nome do arquivo `blog/posts.php`.
Você pode alterar as extensões substitutas da seguinte maneira:

```php
$view->getFallbackExtensions(); // ['php']

$view = $view->withFallbackExtension('tpl', 'twig');

$view->getFallbackExtensions(); // ['tpl', 'twig']
```

> Neste caso, a primeira extensão substituta correspondente será usada, portanto, preste atenção à sua ordem ao configurar.

Os métodos de renderização podem ser chamados dentro de visualizações para renderizar visualizações aninhadas:

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

Por padrão, a renderização simplesmente inclui um arquivo de visualização como um arquivo PHP normal, captura sua saída e retorna
isso como uma string. Você pode substituir esse comportamento implementando `\Yiisoft\View\TemplateRendererInterface`
e usar essa implementação através do método `$view->withRenderers()`.

```php
$view = $view->withRenderers([
    'tpl' => new MyCustomViewRenderer(),
    'twig' => new \Yiisoft\View\Twig\TemplateRenderer($environment),
]);
```

Durante a renderização, a extensão do arquivo será analisada e, se a chave do array corresponder à extensão do arquivo,
o renderizador correspondente será aplicado.

## Temas

Temas é uma forma de substituir um conjunto de visualizações por outro conjunto de visualizações sem a necessidade de alterar a visualização
original do código de renderização. Você pode usar temas para alterar sistematicamente a aparência de um aplicativo.

Para usar temas, você deve criar e configurar uma instância de `Yiisoft\View\Theme`
e configurá-lo usando o método `setTheme()`:

```php
$theme = new \Yiisoft\View\Theme([
    '/path/to/views' => '/path/to/views/themes/basic/views',
]);

$view->setTheme($theme);
```

O primeiro parâmetro da classe `Theme` aceita um array, que
é um mapa de caminhos para diretórios de visualização para as
versões temáticas correspondentes. Por exemplo, se você chamar `$view->render('blog/posts')`, você estará renderizando o
visualizar arquivo `/path/to/views/blog/posts.php`. No entanto, se você ativar o tema conforme mostrado acima, o arquivo de visualização
`/path/to/views/themes/basic/views/blog/posts.php` será renderizado em seu lugar.

Além de visualizar arquivos, os temas podem conter imagens, estilos CSS, scripts JS e outros recursos.
Para ter acesso a esses ativos em uma view, mais dois parâmetros opcionais devem ser
passados ao criar uma instância do `Yiisoft\View\Theme`:

```php
$theme = new \Yiisoft\View\Theme(
    ['/path/to/views' => '/path/to/views/themes/basic/views'],
    '/path/to/views/themes/basic/assets', // The base directory that contains the themed assets.
    '/path/to/public/themes/basic/assets', // The base URL of the themed assets.
);

$view->setTheme($theme);
```

Em uma view, você pode acessar o tema usando o método `getTheme()` e gerenciar ativos da seguinte forma:

```php
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

## Localização

Duas localidades são definidas para localização com o valor padrão `en`:

- `$locale` - A localidade de destino para a qual o arquivo deve ser localizado.
- `$sourceLocale` - A localidade em que o arquivo original está.

Você pode alterar os valores padrão:

```php
$view = $view->withSourceLocale('es');
$view->setLocale('fr');
```

Para usar vários locais, é necessário criar subdiretórios no nível do diretório que corresponda aos arquivos de modelo
da visualização. Por exemplo, se houver uma visualização `/path/to/views/blog/posts.php` e a traduzirmos para o russo, crie
um subdiretório `ru-RU` ou `ru`. Neste subdiretório, e crie um arquivo para a localidade russa:
`/path/to/views/blog/ru/posts.php`.

Para localizar o arquivo, use o método `localize($file, $locale, $sourceLocale)`:

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

A escolha do arquivo é baseada no código de localidade especificado. Um arquivo com o mesmo nome será procurado
no subdiretório cujo nome é igual ao código de localidade. Por exemplo, dado o arquivo
`/path/to/views/blog/posts.php` e o código de localidade `ru-RU`, o arquivo localizado será procurado
para como `/path/to/views/blog/ru-RU/posts.php`. Se o arquivo não for encontrado, ele tentará um substituto
com um código de idioma que é `ru`, ou seja, `/path/to/views/blog/ru/posts.php`.

> Se o arquivo de destino não for encontrado, o arquivo original será retornado.
> Se os códigos de localidade de destino e de origem forem iguais, o arquivo original será retornado.

## Compartilhando dados entre visualizações

Você pode usar blocos e parâmetros comuns para compartilhar dados entre visualizações. Os blocos permitem que você especifique o conteúdo da string
de uma visualização em um lugar enquanto a exibe em outro. Primeiro, em uma visualização de conteúdo, defina um ou vários blocos:

```php
declare(strict_types=1);

/** @var Yiisoft\View\View $this */

$this->setBlock('block-id-1', '...content of block1...');

$this->setBlock('block-id-2', '...content of block2...');
```

Em seguida, exiba os blocos, se houver, ou o conteúdo padrão, se o bloco não estiver definido:

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

Os parâmetros gerais são usados da mesma maneira, mas diferentemente dos blocos, o valor pode ser de qualquer tipo.
Isto é conveniente se você precisar definir alguns dados que estarão disponíveis em todas as visualizações:

```php
$view->setParameter('urlGenerator', new MyUrlGenerator());
$view->setParameter(SomeObject::class, new SomeObject());

$view->render('blog/posts');
```

Usando o `urlGenerator` em todas as visualizações:

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

Você não pode chamar o método `hasParameter()`, mas pode passar o valor padrão para o método `getParameter()`.
Ao mesmo tempo, se o valor padrão não for passado e o parâmetro solicitado não existir,
uma exceção `InvalidArgumentException` será lançada.

```php
// return "default-value"
$view->getParameter('parameter-name', 'default-value');

// throw InvalidArgumentException
$view->getParameter('parameter-name');
```

Para excluir dados, use os métodos `removeBlock('id')` e `removeParameter('id')`.

## Cache de conteúdo

Às vezes, armazenar conteúdo em cache pode melhorar significativamente o desempenho do seu aplicativo. Por exemplo,
se uma página exibir um resumo das vendas anuais em uma tabela, você poderá armazenar essa tabela em um cache para eliminar
o tempo necessário para gerar esta tabela para cada solicitação.

Para armazenar conteúdo em cache, é fornecida a classe `Yiisoft\View\Cache\CachedContent`, que é usada da seguinte maneira:

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

// If the content isn't in the cache, then we will generate it and add it to the cache
if ($content === null) {
  // Generating content
  $content = $view->render('view/name');
  // Adding content to the cache
  $cachedContent->cache($content); 
}

// Content output
echo $content;
```

Além do conteúdo, o método `Yiisoft\View\Cache\CachedContent::cache()`
aceita três argumentos opcionais extras:

- `$ttl (int)` - O TTL do conteúdo em cache. O padrão é `60`.
- `$dependency (Yiisoft\Cache\Dependency\Dependency|null)` - A dependência do conteúdo em cache. O padrão é `null`.
- `$beta (float)` - O valor para calcular o intervalo usado para "Expiração provavelmente antecipada". O padrão é `1.0`.

Para obter mais informações sobre cache e opções de cache, consulte a documentação do
[pacote yiisoft/cache](https://github.com/yiisoft/cache).

### Conteúdo Dinâmico

Ao armazenar conteúdo em cache, você pode se deparar com uma situação em que um grande fragmento de conteúdo é relativamente
estático, exceto um ou alguns lugares. Por exemplo, um cabeçalho de página pode exibir uma barra de menu principal junto com
o nome do usuário atual. Outro problema é que o conteúdo armazenado em cache pode conter código PHP que deve
ser executado para cada solicitação. Você pode resolver ambos os problemas usando a classe `Yiisoft\View\Cache\DynamicContent`.

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

// If the content isn't in the cache, then we will generate it and add it to the cache
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

Um conteúdo dinâmico significa um fragmento de saída que não deve ser armazenado em cache, mesmo que esteja incluído em um cache de fragmentos.
Você pode chamar `$dynamicContent->placeholder()` dentro de um fragmento em cache para inserir conteúdo dinâmico no local desejado
da visualização, como o seguinte:

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

Para armazenar fragmentos de conteúdo em cache, é muito mais conveniente usar conteúdo dinâmico usando o
Widget `Yiisoft\Yii\Widgets\FragmentCache` do
pacote [yiisoft/yii-widgets](https://github.com/yiisoft/yii-widgets):

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

### Variações

O conteúdo armazenado em cache pode variar de acordo com alguns parâmetros. Por exemplo, para uma aplicação web que suporta
vários locais, o mesmo código de visualização pode gerar o conteúdo em locais diferentes. Portanto, você
talvez queira variar o conteúdo em cache de acordo com a localidade do aplicativo atual.

Para especificar variações de cache, você precisa passar o terceiro parâmetro para o construtor,
que deve ser um array de valores de string, cada um representando um fator de variação específico.
Por exemplo, para variar o conteúdo em cache de acordo com a localidade, você pode usar o seguinte código:

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

## Eventos da visualização

A classe `Yiisoft\View\View` dispara vários eventos durante o processo de renderização da visualização. Eventos são classes:

- `Yiisoft\View\Event\View\BeforeRender` - acionado no início da renderização de um modelo de visualização.
   Os ouvintes deste evento podem definir `$event->stopPropagation()` como falso para cancelar o processo de renderização.
- `Yiisoft\View\Event\View\AfterRender` - acionado no final da renderização de um modelo de visualização.
   Os ouvintes deste evento podem obter dados sobre os resultados da renderização usando os métodos desta classe.
- `Yiisoft\View\Event\View\PageBegin` - acionado pela chamada de `Yiisoft\View\View::beginPage()` no modelo de visualização.
- `Yiisoft\View\Event\View\PageEnd` - acionado pela chamada de `Yiisoft\View\View::endPage()` no modelo de visualização.

Esses eventos são passados para a implementação `Psr\EventDispatcher\EventDispatcherInterface`,
que é especificado no construtor quando a instância `Yiisoft\View\View` é inicializada.

Para que os eventos `Yiisoft\View\Event\View\PageBegin` e `Yiisoft\View\Event\View\PageEnd` sejam acionados,
você deve chamar os métodos correspondentes no modelo de visualização:

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

## Usando com loop de eventos

As instâncias `Yiisoft\View\View` e `Yiisoft\View\WebView` têm estado, portanto, quando você cria aplicações de longa execução
com ferramentas como [Swoole](https://www.swoole.co.uk/) ou [RoadRunner](https://roadrunner.dev/) você deve redefinir
o estado a cada solicitação. Para este propósito, você pode usar o método `clear()`.
