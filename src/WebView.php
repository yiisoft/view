<?php

declare(strict_types=1);

namespace Yiisoft\View;

use InvalidArgumentException;
use Psr\EventDispatcher\StoppableEventInterface;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Link;
use Yiisoft\Html\Tag\Meta;
use Yiisoft\Html\Tag\Script;
use Yiisoft\Html\Tag\Style;
use Yiisoft\Json\Json;
use Yiisoft\View\Event\AfterRenderEventInterface;
use Yiisoft\View\Event\WebView\AfterRender;
use Yiisoft\View\Event\WebView\BeforeRender;
use Yiisoft\View\Event\WebView\BodyBegin;
use Yiisoft\View\Event\WebView\BodyEnd;
use Yiisoft\View\Event\WebView\Head;
use Yiisoft\View\Event\WebView\PageBegin;
use Yiisoft\View\Event\WebView\PageEnd;

use function array_key_exists;
use function get_class;
use function gettype;
use function in_array;
use function is_array;
use function is_object;
use function is_string;

/**
 * View represents a view object in the MVC pattern.
 *
 * View provides a set of methods (e.g. {@see render()} for rendering purpose.
 *
 * You can modify its configuration by adding an array to your application config under `components` as it is shown in
 * the following example:
 *
 * ```php
 * 'view' => [
 *     'theme' => 'app\themes\MyTheme',
 *     'renderers' => [
 *         // you may add Smarty or Twig renderer here
 *     ]
 *     // ...
 * ]
 * ```
 *
 * For more details and usage information on View, see the [guide article on views](guide:structure-views).
 */
final class WebView extends BaseView
{
    /**
     * The location of registered JavaScript code block or files.
     * This means the location is in the head section.
     */
    public const POSITION_HEAD = 1;

    /**
     * The location of registered JavaScript code block or files.
     * This means the location is at the beginning of the body section.
     */
    public const POSITION_BEGIN = 2;

    /**
     * The location of registered JavaScript code block or files.
     * This means the location is at the end of the body section.
     */
    public const POSITION_END = 3;

    /**
     * The location of registered JavaScript code block.
     * This means the JavaScript code block will be executed when HTML document composition is ready.
     */
    public const POSITION_READY = 4;

    /**
     * The location of registered JavaScript code block.
     * This means the JavaScript code block will be executed when HTML page is completely loaded.
     */
    public const POSITION_LOAD = 5;

    private const DEFAULT_POSITION_CSS_FILE = self::POSITION_HEAD;
    private const DEFAULT_POSITION_CSS_STRING = self::POSITION_HEAD;
    private const DEFAULT_POSITION_JS_FILE = self::POSITION_END;
    private const DEFAULT_POSITION_JS_VARIABLE = self::POSITION_HEAD;
    private const DEFAULT_POSITION_JS_STRING = self::POSITION_END;
    private const DEFAULT_POSITION_LINK = self::POSITION_HEAD;

    /**
     * This is internally used as the placeholder for receiving the content registered for the head section.
     */
    private const PLACEHOLDER_HEAD = '<![CDATA[YII-BLOCK-HEAD-%s]]>';

    /**
     * This is internally used as the placeholder for receiving the content registered for the beginning of the body
     * section.
     */
    private const PLACEHOLDER_BODY_BEGIN = '<![CDATA[YII-BLOCK-BODY-BEGIN-%s]]>';

    /**
     * This is internally used as the placeholder for receiving the content registered for the end of the body section.
     */
    private const PLACEHOLDER_BODY_END = '<![CDATA[YII-BLOCK-BODY-END-%s]]>';

    /**
     * @var string the page title
     */
    private string $title = '';

    /**
     * @var Meta[] The registered meta tags.
     *
     * @see registerMeta()
     * @see registerMetaTag()
     */
    private array $metaTags = [];

    /**
     * @var array The registered link tags.
     *
     * @psalm-var array<int, Link[]>
     *
     * @see registerLink()
     * @see registerLinkTag()
     */
    private array $linkTags = [];

    /**
     * @var array the registered CSS code blocks.
     *
     * {@see registerCss()}
     */
    private array $css = [];

    /**
     * @var array the registered CSS files.
     *
     * {@see registerCssFile()}
     */
    private array $cssFiles = [];

    /**
     * @var array the registered JS code blocks
     * @psalm-var array<int, string[]|Script[]>
     *
     * {@see registerJs()}
     */
    private array $js = [];

    /**
     * @var array the registered JS files.
     *
     * {@see registerJsFile()}
     */
    private array $jsFiles = [];

    /**
     * Marks the position of an HTML head section.
     */
    public function head(): void
    {
        echo sprintf(self::PLACEHOLDER_HEAD, $this->getPlaceholderSignature());
        $this->eventDispatcher->dispatch(new Head($this));
    }

    /**
     * Marks the beginning of an HTML body section.
     */
    public function beginBody(): void
    {
        echo sprintf(self::PLACEHOLDER_BODY_BEGIN, $this->getPlaceholderSignature());
        $this->eventDispatcher->dispatch(new BodyBegin($this));
    }

    /**
     * Marks the ending of an HTML body section.
     */
    public function endBody(): void
    {
        $this->eventDispatcher->dispatch(new BodyEnd($this));
        echo sprintf(self::PLACEHOLDER_BODY_END, $this->getPlaceholderSignature());
    }

    /**
     * Marks the beginning of a HTML page.
     */
    public function beginPage(): void
    {
        ob_start();
        /** @psalm-suppress PossiblyFalseArgument */
        PHP_VERSION_ID >= 80000 ? ob_implicit_flush(false) : ob_implicit_flush(0);

        $this->eventDispatcher->dispatch(new PageBegin($this));
    }

    /**
     * Marks the ending of an HTML page.
     *
     * @param bool $ajaxMode whether the view is rendering in AJAX mode. If true, the JS scripts registered at
     * {@see POSITION_READY} and {@see POSITION_LOAD} positions will be rendered at the end of the view like
     * normal scripts.
     */
    public function endPage(bool $ajaxMode = false): void
    {
        $this->eventDispatcher->dispatch(new PageEnd($this));

        $content = ob_get_clean();

        echo strtr($content, [
            sprintf(self::PLACEHOLDER_HEAD, $this->getPlaceholderSignature()) => $this->renderHeadHtml(),
            sprintf(self::PLACEHOLDER_BODY_BEGIN, $this->getPlaceholderSignature()) => $this->renderBodyBeginHtml(),
            sprintf(self::PLACEHOLDER_BODY_END, $this->getPlaceholderSignature()) => $this->renderBodyEndHtml($ajaxMode),
        ]);

        $this->clear();
    }

    /**
     * Renders a view in response to an AJAX request.
     *
     * This method is similar to {@see render()} except that it will surround the view being rendered with the calls of
     * {@see beginPage()}, {@see head()}, {@see beginBody()}, {@see endBody()} and {@see endPage()}. By doing so, the
     * method is able to inject into the rendering result with JS/CSS scripts and files that are registered with the
     * view.
     *
     * @param string $view the view name. Please refer to {@see render()} on how to specify this parameter.
     * @param array $params the parameters (name-value pairs) that will be extracted and made available in the view
     * file.
     *
     * @return string the rendering result
     *
     * {@see render()}
     */
    public function renderAjax(string $view, array $params = []): string
    {
        $viewFile = $this->findTemplateFile($view);

        ob_start();
        /** @psalm-suppress PossiblyFalseArgument */
        PHP_VERSION_ID >= 80000 ? ob_implicit_flush(false) : ob_implicit_flush(0);

        $this->beginPage();
        $this->head();
        $this->beginBody();
        echo $this->renderFile($viewFile, $params);
        $this->endBody();
        $this->endPage(true);

        return ob_get_clean();
    }

    /**
     * Renders a string in response to an AJAX request.
     *
     * @param string $string The string.
     *
     * @return string The rendering result.
     */
    public function renderAjaxString(string $string): string
    {
        ob_start();
        /** @psalm-suppress PossiblyFalseArgument */
        PHP_VERSION_ID >= 80000 ? ob_implicit_flush(false) : ob_implicit_flush(0);

        $this->beginPage();
        $this->head();
        $this->beginBody();
        echo $string;
        $this->endBody();
        $this->endPage(true);

        return ob_get_clean();
    }

    /**
     * Clears up the registered meta tags, link tags, css/js scripts and files.
     */
    public function clear(): void
    {
        $this->metaTags = [];
        $this->linkTags = [];
        $this->css = [];
        $this->cssFiles = [];
        $this->js = [];
        $this->jsFiles = [];
        parent::clear();
    }

    /**
     * Registers a meta tag.
     *
     * For example, a description meta tag can be added like the following:
     *
     * ```php
     * $view->registerMeta([
     *     'name' => 'description',
     *     'content' => 'This website is about funny raccoons.'
     * ]);
     * ```
     *
     * will result in the meta tag `<meta name="description" content="This website is about funny raccoons.">`.
     *
     * @param array $attributes the HTML attributes for the meta tag.
     * @param string $key the key that identifies the meta tag. If two meta tags are registered with the same key, the
     * latter will overwrite the former. If this is null, the new meta tag will be appended to the
     * existing ones.
     */
    public function registerMeta(array $attributes, ?string $key = null): void
    {
        $this->registerMetaTag(Html::meta($attributes), $key);
    }

    /**
     * Registers a {@see Meta} tag.
     */
    public function registerMetaTag(Meta $meta, ?string $key = null): void
    {
        $key === null
            ? $this->metaTags[] = $meta
            : $this->metaTags[$key] = $meta;
    }

    /**
     * Registers a link tag.
     *
     * For example, a link tag for a custom [favicon](http://www.w3.org/2005/10/howto-favicon) can be added like the
     * following:
     *
     * ```php
     * $view->registerLink(['rel' => 'icon', 'type' => 'image/png', 'href' => '/myicon.png']);
     * ```
     *
     * which will result in the following HTML: `<link rel="icon" type="image/png" href="/myicon.png">`.
     *
     * **Note:** To register link tags for CSS stylesheets, use {@see registerCssFile()]} instead, which has more
     * options for this kind of link tag.
     *
     * @param array $attributes The HTML attributes for the link tag.
     * @param int $position The position at which the link tag should be inserted in a page.
     * @param string|null $key the key that identifies the link tag. If two link tags are registered with the same
     * key, the latter will overwrite the former. If this is null, the new link tag will be appended
     * to the existing ones.
     */
    public function registerLink(
        array $attributes,
        int $position = self::DEFAULT_POSITION_LINK,
        ?string $key = null
    ): void {
        $this->registerLinkTag(Html::link()->attributes($attributes), $position, $key);
    }

    /**
     * Registers a {@see Link} tag.
     */
    public function registerLinkTag(Link $link, int $position = self::DEFAULT_POSITION_LINK, ?string $key = null): void
    {
        $key === null
            ? $this->linkTags[$position][] = $link
            : $this->linkTags[$position][$key] = $link;
    }

    /**
     * Registers a CSS code block.
     *
     * @param string $css the content of the CSS code block to be registered
     * @param string|null $key the key that identifies the CSS code block. If null, it will use $css as the key. If two CSS
     * code blocks are registered with the same key, the latter will overwrite the former.
     */
    public function registerCss(string $css, int $position = self::DEFAULT_POSITION_CSS_STRING, ?string $key = null): void
    {
        $key = $key ?: md5($css);
        $this->css[$position][$key] = $css;
    }

    /**
     * Register a `style` tag.
     *
     * @see registerJs()
     */
    public function registerStyleTag(Style $style, int $position = self::DEFAULT_POSITION_CSS_STRING, ?string $key = null): void
    {
        $key = $key ?: md5($style->render());
        $this->css[$position][$key] = $style;
    }

    /**
     * Registers a CSS file.
     *
     * This method should be used for simple registration of CSS files. If you want to use features of
     * {@see \Yiisoft\Assets\AssetManager} like appending timestamps to the URL and file publishing options, use
     * {@see \Yiisoft\Assets\AssetBundle}.
     *
     * @param string $url the CSS file to be registered.
     * @param array $options the HTML attributes for the link tag. Please refer to {@see \Yiisoft\Html\Html::cssFile()}
     * for the supported options.
     * @param string $key the key that identifies the CSS script file. If null, it will use $url as the key. If two CSS
     * files are registered with the same key, the latter will overwrite the former.
     */
    public function registerCssFile(string $url, int $position = self::DEFAULT_POSITION_CSS_FILE, array $options = [], string $key = null): void
    {
        if (!$this->isValidCssPosition($position)) {
            throw new InvalidArgumentException('Invalid position of CSS file.');
        }

        $this->cssFiles[$position][$key ?: $url] = Html::cssFile($url, $options)->render();
    }

    /**
     * Registers a JS code block.
     *
     * @param string $js the JS code block to be registered
     * @param int $position the position at which the JS script tag should be inserted in a page.
     *
     * The possible values are:
     *
     * - {@see POSITION_HEAD}: in the head section
     * - {@see POSITION_BEGIN}: at the beginning of the body section
     * - {@see POSITION_END}: at the end of the body section. This is the default value.
     * - {@see POSITION_LOAD}: executed when HTML page is completely loaded.
     * - {@see POSITION_READY}: executed when HTML document composition is ready.
     * @param string $key the key that identifies the JS code block. If null, it will use $js as the key. If two JS code
     * blocks are registered with the same key, the latter will overwrite the former.
     */
    public function registerJs(string $js, int $position = self::DEFAULT_POSITION_JS_FILE, ?string $key = null): void
    {
        $key = $key ?: md5($js);
        $this->js[$position][$key] = $js;
    }

    /**
     * Register a `script` tag
     *
     * @see registerJs()
     */
    public function registerScriptTag(Script $script, int $position = self::DEFAULT_POSITION_JS_STRING, ?string $key = null): void
    {
        $key = $key ?: md5($script->render());
        $this->js[$position][$key] = $script;
    }

    /**
     * Registers a JS file.
     *
     * This method should be used for simple registration of JS files. If you want to use features of
     * {@see \Yiisoft\Assets\AssetManager} like appending timestamps to the URL and file publishing options, use
     * {@see \Yiisoft\Assets\AssetBundle}.
     *
     * @param string $url the JS file to be registered.
     * @param array $options the HTML attributes for the script tag. The following options are specially handled and
     * are not treated as HTML attributes:
     *
     * - `position`: specifies where the JS script tag should be inserted in a page. The possible values are:
     *     * {@see POSITION_HEAD}: in the head section
     *     * {@see POSITION_BEGIN}: at the beginning of the body section
     *     * {@see POSITION_END}: at the end of the body section. This is the default value.
     *
     * Please refer to {@see \Yiisoft\Html\Html::javaScriptFile()} for other supported options.
     * @param string $key the key that identifies the JS script file. If null, it will use $url as the key. If two JS
     * files are registered with the same key at the same position, the latter will overwrite the former.
     * Note that position option takes precedence, thus files registered with the same key, but different
     * position option will not override each other.
     */
    public function registerJsFile(string $url, int $position = self::DEFAULT_POSITION_JS_FILE, array $options = [], string $key = null): void
    {
        if (!$this->isValidJsPosition($position)) {
            throw new InvalidArgumentException('Invalid position of JS file.');
        }

        $this->jsFiles[$position][$key ?: $url] = Html::javaScriptFile($url, $options)->render();
    }

    /**
     * Registers a JS code block defining a variable. The name of variable will be used as key, preventing duplicated
     * variable names.
     *
     * @param string $name Name of the variable
     * @param array|string $value Value of the variable
     * @param int $position the position in a page at which the JavaScript variable should be inserted.
     *
     * The possible values are:
     *
     * - {@see POSITION_HEAD}: in the head section. This is the default value.
     * - {@see POSITION_BEGIN}: at the beginning of the body section.
     * - {@see POSITION_END}: at the end of the body section.
     * - {@see POSITION_LOAD}: enclosed within jQuery(window).load().
     *   Note that by using this position, the method will automatically register the jQuery js file.
     * - {@see POSITION_READY}: enclosed within jQuery(document).ready().
     *   Note that by using this position, the method will automatically register the jQuery js file.
     */
    public function registerJsVar(string $name, $value, int $position = self::DEFAULT_POSITION_JS_VARIABLE): void
    {
        $js = sprintf('var %s = %s;', $name, Json::htmlEncode($value));
        $this->registerJs($js, $position, $name);
    }

    /**
     * Renders the content to be inserted in the head section.
     *
     * The content is rendered using the registered meta tags, link tags, CSS/JS code blocks and files.
     *
     * @return string the rendered content
     */
    protected function renderHeadHtml(): string
    {
        $lines = [];
        if (!empty($this->metaTags)) {
            $lines[] = implode("\n", $this->metaTags);
        }

        if (!empty($this->linkTags[self::POSITION_HEAD])) {
            $lines[] = implode("\n", $this->linkTags[self::POSITION_HEAD]);
        }
        if (!empty($this->cssFiles[self::POSITION_HEAD])) {
            $lines[] = implode("\n", $this->cssFiles[self::POSITION_HEAD]);
        }
        if (!empty($this->css[self::POSITION_HEAD])) {
            $lines[] = $this->generateCss($this->css[self::POSITION_HEAD]);
        }
        if (!empty($this->jsFiles[self::POSITION_HEAD])) {
            $lines[] = implode("\n", $this->jsFiles[self::POSITION_HEAD]);
        }
        if (!empty($this->js[self::POSITION_HEAD])) {
            $lines[] = $this->generateJs($this->js[self::POSITION_HEAD]);
        }

        return empty($lines) ? '' : implode("\n", $lines);
    }

    /**
     * Renders the content to be inserted at the beginning of the body section.
     *
     * The content is rendered using the registered JS code blocks and files.
     *
     * @return string the rendered content
     */
    protected function renderBodyBeginHtml(): string
    {
        $lines = [];
        if (!empty($this->linkTags[self::POSITION_BEGIN])) {
            $lines[] = implode("\n", $this->linkTags[self::POSITION_BEGIN]);
        }
        if (!empty($this->cssFiles[self::POSITION_BEGIN])) {
            $lines[] = implode("\n", $this->cssFiles[self::POSITION_BEGIN]);
        }
        if (!empty($this->css[self::POSITION_BEGIN])) {
            $lines[] = $this->generateCss($this->css[self::POSITION_BEGIN]);
        }
        if (!empty($this->jsFiles[self::POSITION_BEGIN])) {
            $lines[] = implode("\n", $this->jsFiles[self::POSITION_BEGIN]);
        }
        if (!empty($this->js[self::POSITION_BEGIN])) {
            $lines[] = $this->generateJs($this->js[self::POSITION_BEGIN]);
        }

        return empty($lines) ? '' : implode("\n", $lines);
    }

    /**
     * Renders the content to be inserted at the end of the body section.
     *
     * The content is rendered using the registered JS code blocks and files.
     *
     * @param bool $ajaxMode whether the view is rendering in AJAX mode. If true, the JS scripts registered at
     * {@see POSITION_READY} and {@see POSITION_LOAD} positions will be rendered at the end of the view like normal
     * scripts.
     *
     * @return string the rendered content
     */
    protected function renderBodyEndHtml(bool $ajaxMode): string
    {
        $lines = [];

        if (!empty($this->linkTags[self::POSITION_END])) {
            $lines[] = implode("\n", $this->linkTags[self::POSITION_END]);
        }
        if (!empty($this->cssFiles[self::POSITION_END])) {
            $lines[] = implode("\n", $this->cssFiles[self::POSITION_END]);
        }
        if (!empty($this->css[self::POSITION_END])) {
            $lines[] = $this->generateCss($this->css[self::POSITION_END]);
        }
        if (!empty($this->jsFiles[self::POSITION_END])) {
            $lines[] = implode("\n", $this->jsFiles[self::POSITION_END]);
        }

        if ($ajaxMode) {
            $scripts = array_merge(
                $this->js[self::POSITION_END] ?? [],
                $this->js[self::POSITION_READY] ?? [],
                $this->js[self::POSITION_LOAD] ?? [],
            );
            if (!empty($scripts)) {
                $lines[] = $this->generateJs($scripts);
            }
        } else {
            if (!empty($this->js[self::POSITION_END])) {
                $lines[] = $this->generateJs($this->js[self::POSITION_END]);
            }
            if (!empty($this->js[self::POSITION_READY])) {
                $js = "document.addEventListener('DOMContentLoaded', function(event) {\n" .
                    $this->generateJsWithoutTag($this->js[self::POSITION_READY]) .
                    "\n});";
                $lines[] = Html::script($js)->render();
            }
            if (!empty($this->js[self::POSITION_LOAD])) {
                $js = "window.addEventListener('load', function (event) {\n" .
                    $this->generateJsWithoutTag($this->js[self::POSITION_LOAD]) .
                    "\n});";
                $lines[] = Html::script($js)->render();
            }
        }

        return empty($lines) ? '' : implode("\n", $lines);
    }

    /**
     * Get title in views.
     *
     * in Layout:
     *
     * ```php
     * <title><?= Html::encode($this->getTitle()) ?></title>
     * ```
     *
     * in Views:
     *
     * ```php
     * $this->setTitle('Web Application - Yii 3.0.');
     * ```
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * It processes the CSS configuration generated by the asset manager and converts it into HTML code.
     *
     * @param array $cssFiles
     */
    public function addCssFiles(array $cssFiles): void
    {
        foreach ($cssFiles as $key => $value) {
            $this->registerCssFileByConfig(
                is_string($key) ? $key : null,
                is_array($value) ? $value : [$value],
            );
        }
    }

    /**
     * @param array $cssStrings
     */
    public function addCssStrings(array $cssStrings): void
    {
        /** @var mixed $value */
        foreach ($cssStrings as $key => $value) {
            $this->registerCssStringByConfig(
                is_string($key) ? $key : null,
                is_array($value) ? $value : [$value, self::DEFAULT_POSITION_CSS_STRING]
            );
        }
    }

    /**
     * It processes the JS configuration generated by the asset manager and converts it into HTML code.
     *
     * @param array $jsFiles
     */
    public function addJsFiles(array $jsFiles): void
    {
        foreach ($jsFiles as $key => $value) {
            $this->registerJsFileByConfig(
                is_string($key) ? $key : null,
                is_array($value) ? $value : [$value],
            );
        }
    }

    /**
     * It processes the JS strings generated by the asset manager.
     *
     * @param array $jsStrings
     *
     * @throws InvalidArgumentException
     */
    public function addJsStrings(array $jsStrings): void
    {
        /** @var mixed $value */
        foreach ($jsStrings as $key => $value) {
            $this->registerJsStringByConfig(
                is_string($key) ? $key : null,
                is_array($value) ? $value : [$value, self::DEFAULT_POSITION_JS_STRING]
            );
        }
    }

    /**
     * It processes the JS variables generated by the asset manager and converts it into JS code.
     *
     * @param array $jsVars
     *
     * @throws InvalidArgumentException
     */
    public function addJsVars(array $jsVars): void
    {
        foreach ($jsVars as $key => $value) {
            if (is_string($key)) {
                $this->registerJsVar($key, $value, self::DEFAULT_POSITION_JS_VARIABLE);
            } else {
                $this->registerJsVarByConfig($value);
            }
        }
    }

    /**
     * Set title in views.
     *
     * {@see getTitle()}
     *
     * @param string $value
     */
    public function setTitle(string $value): void
    {
        $this->title = $value;
    }

    protected function createBeforeRenderEvent(string $viewFile, array $parameters): StoppableEventInterface
    {
        return new BeforeRender($this, $viewFile, $parameters);
    }

    protected function createAfterRenderEvent(
        string $viewFile,
        array $parameters,
        string $result
    ): AfterRenderEventInterface {
        return new AfterRender($this, $viewFile, $parameters, $result);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function registerCssFileByConfig(?string $key, array $config): void
    {
        if (!array_key_exists(0, $config)) {
            throw new InvalidArgumentException('Do not set CSS file.');
        }
        $file = $config[0];

        if (!is_string($file)) {
            throw new InvalidArgumentException(
                sprintf(
                    'CSS file should be string. Got %s.',
                    $this->getType($file),
                )
            );
        }

        $position = $config[1] ?? self::DEFAULT_POSITION_CSS_FILE;

        unset($config[0], $config[1]);
        $this->registerCssFile($file, $position, $config, $key);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function registerCssStringByConfig(?string $key, array $config): void
    {
        if (!array_key_exists(0, $config)) {
            throw new InvalidArgumentException('Do not set CSS string.');
        }
        $css = $config[0];

        if (!is_string($css) && !($css instanceof Style)) {
            throw new InvalidArgumentException(
                sprintf(
                    'CSS string should be string or instance of \\' . Style::class . '. Got %s.',
                    $this->getType($css),
                )
            );
        }

        $position = $config[1] ?? self::DEFAULT_POSITION_CSS_STRING;
        if (!$this->isValidCssPosition($position)) {
            throw new InvalidArgumentException('Invalid position of CSS strings.');
        }

        unset($config[0], $config[1]);
        if ($config !== []) {
            $css = ($css instanceof Style ? $css : Html::style($css))->attributes($config);
        }

        is_string($css)
            ? $this->registerCss($css, $position, $key)
            : $this->registerStyleTag($css, $position, $key);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function registerJsFileByConfig(?string $key, array $config): void
    {
        if (!array_key_exists(0, $config)) {
            throw new InvalidArgumentException('Do not set JS file.');
        }
        $file = $config[0];

        if (!is_string($file)) {
            throw new InvalidArgumentException(
                sprintf(
                    'JS file should be string. Got %s.',
                    $this->getType($file),
                )
            );
        }

        $position = $config[1] ?? self::DEFAULT_POSITION_JS_FILE;

        unset($config[0], $config[1]);
        $this->registerJsFile($file, $position, $config, $key);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function registerJsStringByConfig(?string $key, array $config): void
    {
        if (!array_key_exists(0, $config)) {
            throw new InvalidArgumentException('Do not set JS string.');
        }
        $js = $config[0];

        if (!is_string($js) && !($js instanceof Script)) {
            throw new InvalidArgumentException(
                sprintf(
                    'JS string should be string or instance of \\' . Script::class . '. Got %s.',
                    $this->getType($js),
                )
            );
        }

        $position = $config[1] ?? self::DEFAULT_POSITION_JS_STRING;
        if (!$this->isValidJsPosition($position)) {
            throw new InvalidArgumentException('Invalid position of JS strings.');
        }

        unset($config[0], $config[1]);
        if ($config !== []) {
            $js = ($js instanceof Script ? $js : Html::script($js))->attributes($config);
        }

        is_string($js)
            ? $this->registerJs($js, $position, $key)
            : $this->registerScriptTag($js, $position, $key);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function registerJsVarByConfig(array $config): void
    {
        if (!array_key_exists(0, $config)) {
            throw new InvalidArgumentException('Do not set JS variable name.');
        }
        $key = $config[0];

        if (!is_string($key)) {
            throw new InvalidArgumentException(
                sprintf(
                    'JS variable name should be string. Got %s.',
                    $this->getType($key),
                )
            );
        }

        if (!array_key_exists(1, $config)) {
            throw new InvalidArgumentException('Do not set JS variable value.');
        }
        /** @var mixed */
        $value = $config[1];

        $position = $config[2] ?? self::DEFAULT_POSITION_JS_VARIABLE;
        if (!$this->isValidJsPosition($position)) {
            throw new InvalidArgumentException('Invalid position of JS variable.');
        }

        $this->registerJsVar($key, $value, $position);
    }

    /**
     * @param string[]|Style[] $items
     */
    private function generateCss(array $items): string
    {
        $lines = [];

        $css = [];
        foreach ($items as $item) {
            if ($item instanceof Style) {
                if ($css !== []) {
                    $lines[] = Html::style(implode("\n", $css))->render();
                    $css = [];
                }
                $lines[] = $item->render();
            } else {
                $css[] = $item;
            }
        }
        if ($css !== []) {
            $lines[] = Html::style(implode("\n", $css))->render();
        }

        return implode("\n", $lines);
    }

    /**
     * @param Script[]|string[] $items
     */
    private function generateJs(array $items): string
    {
        $lines = [];

        $js = [];
        foreach ($items as $item) {
            if ($item instanceof Script) {
                if ($js !== []) {
                    $lines[] = Html::script(implode("\n", $js))->render();
                    $js = [];
                }
                $lines[] = $item->render();
            } else {
                $js[] = $item;
            }
        }
        if ($js !== []) {
            $lines[] = Html::script(implode("\n", $js))->render();
        }

        return implode("\n", $lines);
    }

    /**
     * @param Script[]|string[] $items
     */
    private function generateJsWithoutTag(array $items): string
    {
        $js = [];
        foreach ($items as $item) {
            $js[] = $item instanceof Script ? $item->getContent() : $item;
        }
        return implode("\n", $js);
    }

    /**
     * @param mixed $position
     *
     * @psalm-assert =int $position
     */
    private function isValidCssPosition($position): bool
    {
        return in_array(
            $position,
            [
                self::POSITION_HEAD,
                self::POSITION_BEGIN,
                self::POSITION_END,
            ],
            true,
        );
    }

    /**
     * @param mixed $position
     *
     * @psalm-assert =int $position
     */
    private function isValidJsPosition($position): bool
    {
        return in_array(
            $position,
            [
                self::POSITION_HEAD,
                self::POSITION_BEGIN,
                self::POSITION_END,
                self::POSITION_READY,
                self::POSITION_LOAD,
            ],
            true,
        );
    }

    /**
     * @param mixed $value
     */
    private function getType($value): string
    {
        return is_object($value) ? get_class($value) : gettype($value);
    }
}
