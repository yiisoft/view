<?php

declare(strict_types=1);

namespace Yiisoft\View;

use InvalidArgumentException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\StoppableEventInterface;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Link;
use Yiisoft\Html\Tag\Meta;
use Yiisoft\Html\Tag\Script;
use Yiisoft\Html\Tag\Style;
use Yiisoft\View\Event\AfterRenderEventInterface;
use Yiisoft\View\Event\WebView\AfterRender;
use Yiisoft\View\Event\WebView\BeforeRender;
use Yiisoft\View\Event\WebView\BodyBegin;
use Yiisoft\View\Event\WebView\BodyEnd;
use Yiisoft\View\Event\WebView\Head;
use Yiisoft\View\Event\WebView\PageBegin;
use Yiisoft\View\Event\WebView\PageEnd;
use Yiisoft\View\State\WebViewState;

use function array_merge;
use function implode;
use function ob_get_clean;
use function ob_implicit_flush;
use function ob_start;
use function sprintf;
use function strtr;

/**
 * `WebView` represents an instance of a view for use in a WEB environment.
 *
 * `WebView` provides a set of methods (e.g. {@see WebView::render()}) for rendering purpose.
 */
final class WebView implements ViewInterface
{
    use ViewTrait;

    private WebViewState $state;

    /**
     * This means the location is in the head section.
     */
    public const POSITION_HEAD = 1;

    /**
     * This means the location is at the beginning of the body section.
     */
    public const POSITION_BEGIN = 2;

    /**
     * This means the location is at the end of the body section.
     */
    public const POSITION_END = 3;

    /**
     * This means the JavaScript code block will be executed when HTML document composition is ready.
     */
    public const POSITION_READY = 4;

    /**
     * This means the JavaScript code block will be executed when HTML page is completely loaded.
     */
    public const POSITION_LOAD = 5;

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
     * @param string $basePath The full path to the base directory of views.
     * @param EventDispatcherInterface $eventDispatcher The event dispatcher instance.
     */
    public function __construct(string $basePath, EventDispatcherInterface $eventDispatcher)
    {
        $this->basePath = $basePath;
        $this->state = new WebViewState();
        $this->eventDispatcher = $eventDispatcher;
        $this->setPlaceholderSalt(__DIR__);
    }

    /**
     * Returns a new instance with cleared state (blocks, parameters, registered CSS/JS, etc.)
     *
     * @return static
     */
    public function withClearedState(): self
    {
        $new = clone $this;
        $new->state = new WebViewState();
        return $new;
    }

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
     * Marks the beginning of an HTML page.
     */
    public function beginPage(): void
    {
        ob_start();
        /** @psalm-suppress InvalidArgument */
        PHP_VERSION_ID >= 80000 ? ob_implicit_flush(false) : ob_implicit_flush(0);

        $this->eventDispatcher->dispatch(new PageBegin($this));
    }

    /**
     * Marks the ending of an HTML page.
     *
     * @param bool $ajaxMode Whether the view is rendering in AJAX mode. If true, the JS scripts registered at
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
     * @param string $view The view name. Please refer to {@see render()} on how to specify this parameter.
     * @param array $parameters The parameters (name-value pairs) that will be extracted and made available in the view
     * file.
     *
     * @return string The rendering result
     *
     * {@see render()}
     */
    public function renderAjax(string $view, array $parameters = []): string
    {
        $viewFile = $this->findTemplateFile($view);

        ob_start();
        /** @psalm-suppress InvalidArgument */
        PHP_VERSION_ID >= 80000 ? ob_implicit_flush(false) : ob_implicit_flush(0);

        $this->beginPage();
        $this->head();
        $this->beginBody();
        echo $this->renderFile($viewFile, $parameters);
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
        /** @psalm-suppress InvalidArgument */
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
        return $this->state->getTitle();
    }

    /**
     * Set title in views.
     *
     * {@see getTitle()}
     *
     * @param string $value
     *
     * @return static
     */
    public function setTitle(string $value): self
    {
        $this->state->setTitle($value);
        return $this;
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
     * @param array $attributes The HTML attributes for the meta tag.
     * @param string|null $key The key that identifies the meta tag. If two meta tags are registered with the same key,
     * the latter will overwrite the former. If this is null, the new meta tag will be appended to the existing ones.
     *
     * @return static
     */
    public function registerMeta(array $attributes, ?string $key = null): self
    {
        $this->state->registerMeta($attributes, $key);
        return $this;
    }

    /**
     * Registers a {@see Meta} tag.
     *
     * @return static
     *
     * @see registerMeta()
     */
    public function registerMetaTag(Meta $meta, ?string $key = null): self
    {
        $this->state->registerMetaTag($meta, $key);
        return $this;
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
     * @param string|null $key The key that identifies the link tag. If two link tags are registered with the same
     * key, the latter will overwrite the former. If this is null, the new link tag will be appended
     * to the existing ones.
     *
     * @return static
     */
    public function registerLink(array $attributes, int $position = self::POSITION_HEAD, ?string $key = null): self
    {
        $this->state->registerLink($attributes, $position, $key);
        return $this;
    }

    /**
     * Registers a {@see Link} tag.
     *
     * @return static
     *
     * @see registerLink()
     */
    public function registerLinkTag(Link $link, int $position = self::POSITION_HEAD, ?string $key = null): self
    {
        $this->state->registerLinkTag($link, $position, $key);
        return $this;
    }

    /**
     * Registers a CSS code block.
     *
     * @param string $css The content of the CSS code block to be registered.
     * @param string|null $key The key that identifies the CSS code block. If null, it will use $css as the key.
     * If two CSS code blocks are registered with the same key, the latter will overwrite the former.
     * @param array $attributes The HTML attributes for the {@see Style} tag.
     *
     * @return static
     */
    public function registerCss(
        string $css,
        int $position = self::POSITION_HEAD,
        array $attributes = [],
        ?string $key = null
    ): self {
        $this->state->registerCss($css, $position, $attributes, $key);
        return $this;
    }

    /**
     * Registers a CSS code block from file.
     *
     * @param string $path The path or URL to CSS file.
     *
     * @return static
     *
     * @see registerCss()
     */
    public function registerCssFromFile(
        string $path,
        int $position = self::POSITION_HEAD,
        array $attributes = [],
        ?string $key = null
    ): self {
        $this->state->registerCssFromFile($path, $position, $attributes, $key);
        return $this;
    }

    /**
     * Register a {@see Style} tag.
     *
     * @return static
     *
     * @see registerJs()
     */
    public function registerStyleTag(Style $style, int $position = self::POSITION_HEAD, ?string $key = null): self
    {
        $this->state->registerStyleTag($style, $position, $key);
        return $this;
    }

    /**
     * Registers a CSS file.
     *
     * This method should be used for simple registration of CSS files. If you want to use features of
     * {@see \Yiisoft\Assets\AssetManager} like appending timestamps to the URL and file publishing options, use
     * {@see \Yiisoft\Assets\AssetBundle}.
     *
     * @param string $url The CSS file to be registered.
     * @param array $options the HTML attributes for the link tag. Please refer to {@see \Yiisoft\Html\Html::cssFile()}
     * for the supported options.
     * @param string|null $key The key that identifies the CSS script file. If null, it will use $url as the key.
     * If two CSS files are registered with the same key, the latter will overwrite the former.
     *
     * @return static
     */
    public function registerCssFile(
        string $url,
        int $position = self::POSITION_HEAD,
        array $options = [],
        string $key = null
    ): self {
        $this->state->registerCssFile($url, $position, $options, $key);
        return $this;
    }

    /**
     * It processes the CSS configuration generated by the asset manager and converts it into HTML code.
     *
     * @param array $cssFiles
     *
     * @return static
     */
    public function addCssFiles(array $cssFiles): self
    {
        $this->state->addCssFiles($cssFiles);
        return $this;
    }

    /**
     * It processes the CSS strings generated by the asset manager.
     *
     * @param array $cssStrings
     *
     * @return static
     */
    public function addCssStrings(array $cssStrings): self
    {
        $this->state->addCssStrings($cssStrings);
        return $this;
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
     * @param string|null $key The key that identifies the JS code block. If null, it will use $js as the key.
     * If two JS code blocks are registered with the same key, the latter will overwrite the former.
     *
     * @return static
     */
    public function registerJs(string $js, int $position = self::POSITION_END, ?string $key = null): self
    {
        $this->state->registerJs($js, $position, $key);
        return $this;
    }

    /**
     * Register a `script` tag
     *
     * @return static
     *
     * @see registerJs()
     */
    public function registerScriptTag(Script $script, int $position = self::POSITION_END, ?string $key = null): self
    {
        $this->state->registerScriptTag($script, $position, $key);
        return $this;
    }

    /**
     * Registers a JS file.
     *
     * This method should be used for simple registration of JS files. If you want to use features of
     * {@see \Yiisoft\Assets\AssetManager} like appending timestamps to the URL and file publishing options, use
     * {@see \Yiisoft\Assets\AssetBundle}.
     *
     * @param string $url The JS file to be registered.
     * @param array $options The HTML attributes for the script tag. The following options are specially handled and
     * are not treated as HTML attributes:
     *
     * - `position`: specifies where the JS script tag should be inserted in a page. The possible values are:
     *     * {@see POSITION_HEAD}: in the head section
     *     * {@see POSITION_BEGIN}: at the beginning of the body section
     *     * {@see POSITION_END}: at the end of the body section. This is the default value.
     *
     * Please refer to {@see \Yiisoft\Html\Html::javaScriptFile()} for other supported options.
     * @param string|null $key The key that identifies the JS script file. If null, it will use $url as the key.
     * If two JS files are registered with the same key at the same position, the latter will overwrite the former.
     * Note that position option takes precedence, thus files registered with the same key, but different
     * position option will not override each other.
     *
     * @return static
     */
    public function registerJsFile(
        string $url,
        int $position = self::POSITION_END,
        array $options = [],
        string $key = null
    ): self {
        $this->state->registerJsFile($url, $position, $options, $key);
        return $this;
    }

    /**
     * Registers a JS code block defining a variable. The name of variable will be used as key, preventing duplicated
     * variable names.
     *
     * @param string $name Name of the variable
     * @param mixed $value Value of the variable
     * @param int $position The position in a page at which the JavaScript variable should be inserted.
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
     *
     * @return static
     */
    public function registerJsVar(string $name, $value, int $position = self::POSITION_HEAD): self
    {
        $this->state->registerJsVar($name, $value, $position);
        return $this;
    }

    /**
     * It processes the JS configuration generated by the asset manager and converts it into HTML code.
     *
     * @param array $jsFiles
     *
     * @return static
     */
    public function addJsFiles(array $jsFiles): self
    {
        $this->state->addJsFiles($jsFiles);
        return $this;
    }

    /**
     * It processes the JS strings generated by the asset manager.
     *
     * @param array $jsStrings
     *
     * @throws InvalidArgumentException
     *
     * @return static
     */
    public function addJsStrings(array $jsStrings): self
    {
        $this->state->addJsStrings($jsStrings);
        return $this;
    }

    /**
     * It processes the JS variables generated by the asset manager and converts it into JS code.
     *
     * @param array $jsVars
     *
     * @throws InvalidArgumentException
     *
     * @return static
     */
    public function addJsVars(array $jsVars): self
    {
        $this->state->addJsVars($jsVars);
        return $this;
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
     * Renders the content to be inserted in the head section.
     *
     * The content is rendered using the registered meta tags, link tags, CSS/JS code blocks and files.
     *
     * @return string The rendered content
     */
    private function renderHeadHtml(): string
    {
        $lines = [];

        if (!empty($this->state->getMetaTags())) {
            $lines[] = implode("\n", $this->state->getMetaTags());
        }
        if (!empty($this->state->getLinkTags()[self::POSITION_HEAD])) {
            $lines[] = implode("\n", $this->state->getLinkTags()[self::POSITION_HEAD]);
        }
        if (!empty($this->state->getCssFiles()[self::POSITION_HEAD])) {
            $lines[] = implode("\n", $this->state->getCssFiles()[self::POSITION_HEAD]);
        }
        if (!empty($this->state->getCss()[self::POSITION_HEAD])) {
            $lines[] = $this->generateCss($this->state->getCss()[self::POSITION_HEAD]);
        }
        if (!empty($this->state->getJsFiles()[self::POSITION_HEAD])) {
            $lines[] = implode("\n", $this->state->getJsFiles()[self::POSITION_HEAD]);
        }
        if (!empty($this->state->getJs()[self::POSITION_HEAD])) {
            $lines[] = $this->generateJs($this->state->getJs()[self::POSITION_HEAD]);
        }

        return empty($lines) ? '' : implode("\n", $lines);
    }

    /**
     * Renders the content to be inserted at the beginning of the body section.
     *
     * The content is rendered using the registered JS code blocks and files.
     *
     * @return string The rendered content.
     */
    private function renderBodyBeginHtml(): string
    {
        $lines = [];

        if (!empty($this->state->getLinkTags()[self::POSITION_BEGIN])) {
            $lines[] = implode("\n", $this->state->getLinkTags()[self::POSITION_BEGIN]);
        }
        if (!empty($this->state->getCssFiles()[self::POSITION_BEGIN])) {
            $lines[] = implode("\n", $this->state->getCssFiles()[self::POSITION_BEGIN]);
        }
        if (!empty($this->state->getCss()[self::POSITION_BEGIN])) {
            $lines[] = $this->generateCss($this->state->getCss()[self::POSITION_BEGIN]);
        }
        if (!empty($this->state->getJsFiles()[self::POSITION_BEGIN])) {
            $lines[] = implode("\n", $this->state->getJsFiles()[self::POSITION_BEGIN]);
        }
        if (!empty($this->state->getJs()[self::POSITION_BEGIN])) {
            $lines[] = $this->generateJs($this->state->getJs()[self::POSITION_BEGIN]);
        }

        return empty($lines) ? '' : implode("\n", $lines);
    }

    /**
     * Renders the content to be inserted at the end of the body section.
     *
     * The content is rendered using the registered JS code blocks and files.
     *
     * @param bool $ajaxMode Whether the view is rendering in AJAX mode. If true, the JS scripts registered at
     * {@see POSITION_READY} and {@see POSITION_LOAD} positions will be rendered at the end of the view like normal
     * scripts.
     *
     * @return string The rendered content.
     */
    private function renderBodyEndHtml(bool $ajaxMode): string
    {
        $lines = [];

        if (!empty($this->state->getLinkTags()[self::POSITION_END])) {
            $lines[] = implode("\n", $this->state->getLinkTags()[self::POSITION_END]);
        }
        if (!empty($this->state->getCssFiles()[self::POSITION_END])) {
            $lines[] = implode("\n", $this->state->getCssFiles()[self::POSITION_END]);
        }
        if (!empty($this->state->getCss()[self::POSITION_END])) {
            $lines[] = $this->generateCss($this->state->getCss()[self::POSITION_END]);
        }
        if (!empty($this->state->getJsFiles()[self::POSITION_END])) {
            $lines[] = implode("\n", $this->state->getJsFiles()[self::POSITION_END]);
        }

        if ($ajaxMode) {
            $scripts = array_merge(
                $this->state->getJs()[self::POSITION_END] ?? [],
                $this->state->getJs()[self::POSITION_READY] ?? [],
                $this->state->getJs()[self::POSITION_LOAD] ?? [],
            );
            if (!empty($scripts)) {
                $lines[] = $this->generateJs($scripts);
            }
        } else {
            if (!empty($this->state->getJs()[self::POSITION_END])) {
                $lines[] = $this->generateJs($this->state->getJs()[self::POSITION_END]);
            }
            if (!empty($this->state->getJs()[self::POSITION_READY])) {
                $js = "document.addEventListener('DOMContentLoaded', function(event) {\n" .
                    $this->generateJsWithoutTag($this->state->getJs()[self::POSITION_READY]) .
                    "\n});";
                $lines[] = Html::script($js)->render();
            }
            if (!empty($this->state->getJs()[self::POSITION_LOAD])) {
                $js = "window.addEventListener('load', function(event) {\n" .
                    $this->generateJsWithoutTag($this->state->getJs()[self::POSITION_LOAD]) .
                    "\n});";
                $lines[] = Html::script($js)->render();
            }
        }

        return empty($lines) ? '' : implode("\n", $lines);
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
}
