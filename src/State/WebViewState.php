<?php

declare(strict_types=1);

namespace Yiisoft\View\State;

use InvalidArgumentException;
use RuntimeException;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Link;
use Yiisoft\Html\Tag\Meta;
use Yiisoft\Html\Tag\Script;
use Yiisoft\Html\Tag\Style;
use Yiisoft\Json\Json;
use Yiisoft\View\WebView;

use function array_key_exists;
use function get_class;
use function gettype;
use function in_array;
use function is_array;
use function is_object;
use function is_string;

/**
 * @internal
 */
final class WebViewState
{
    use StateTrait;

    /**
     * @var string The page title
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
     * @psalm-var array<int, Link[]>
     *
     * @see registerLink()
     * @see registerLinkTag()
     */
    private array $linkTags = [];

    /**
     * @var array The registered CSS code blocks.
     * @psalm-var array<int, string[]|Style[]>
     *
     * {@see registerCss()}
     */
    private array $css = [];

    /**
     * @var array The registered CSS files.
     * @psalm-var array<int, string[]>
     *
     * {@see registerCssFile()}
     */
    private array $cssFiles = [];

    /**
     * @var array The registered JS code blocks
     * @psalm-var array<int, string[]|Script[]>
     *
     * {@see registerJs()}
     */
    private array $js = [];

    /**
     * @var array The registered JS files.
     * @psalm-var array<int, string[]>
     *
     * {@see registerJsFile()}
     */
    private array $jsFiles = [];

    /**
     * Get title in views.
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return Meta[] The registered meta tags.
     */
    public function getMetaTags(): array
    {
        return $this->metaTags;
    }

    /**
     * @return array The registered link tags.
     * @psalm-return array<int, Link[]>
     */
    public function getLinkTags(): array
    {
        return $this->linkTags;
    }

    /**
     * @return array The registered CSS code blocks.
     * @psalm-return array<int, string[]|Style[]>
     */
    public function getCss(): array
    {
        return $this->css;
    }

    /**
     * @return array The registered CSS files.
     * @psalm-return array<int, string[]>
     */
    public function getCssFiles(): array
    {
        return $this->cssFiles;
    }

    /**
     * @return array The registered JS code blocks
     * @psalm-return array<int, string[]|Script[]>
     */
    public function getJs(): array
    {
        return $this->js;
    }

    /**
     * @return array The registered JS files.
     * @psalm-return array<int, string[]>
     */
    public function getJsFiles(): array
    {
        return $this->jsFiles;
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
        $this->title = $value;
        return $this;
    }

    /**
     * Registers a meta tag.
     *
     * For example, a description meta tag can be added like the following:
     *
     * ```php
     * $state->registerMeta([
     *     'name' => 'description',
     *     'content' => 'This website is about funny raccoons.'
     * ]);
     * ```
     *
     * @param array $attributes The HTML attributes for the meta tag.
     * @param string|null $key The key that identifies the meta tag. If two meta tags are registered with the same key,
     * the latter will overwrite the former. If this is null, the new meta tag will be appended to the existing ones.
     */
    public function registerMeta(array $attributes, ?string $key = null): void
    {
        $this->registerMetaTag(Html::meta($attributes), $key);
    }

    /**
     * Registers a {@see Meta} tag.
     *
     * @see registerMeta()
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
     * **Note:** To register link tags for CSS stylesheets, use {@see registerCssFile()]} instead, which has more
     * options for this kind of link tag.
     *
     * @param array $attributes The HTML attributes for the link tag.
     * @param int $position The position at which the link tag should be inserted in a page.
     * @param string|null $key The key that identifies the link tag. If two link tags are registered with the same key,
     * the latter will overwrite the former. If this is null, the new link tag will be appended to the existing ones.
     */
    public function registerLink(array $attributes, int $position = WebView::POSITION_HEAD, ?string $key = null): void
    {
        $this->registerLinkTag(Html::link()->attributes($attributes), $position, $key);
    }

    /**
     * Registers a {@see Link} tag.
     *
     * @see registerLink()
     */
    public function registerLinkTag(Link $link, int $position = WebView::POSITION_HEAD, ?string $key = null): void
    {
        $key === null
            ? $this->linkTags[$position][] = $link
            : $this->linkTags[$position][$key] = $link;
    }

    /**
     * Registers a CSS code block.
     *
     * @param string $css The content of the CSS code block to be registered.
     * @param string|null $key The key that identifies the CSS code block. If null, it will use $css as the key.
     * If two CSS code blocks are registered with the same key, the latter will overwrite the former.
     * @param array $attributes The HTML attributes for the {@see Style} tag.
     */
    public function registerCss(
        string $css,
        int $position = WebView::POSITION_HEAD,
        array $attributes = [],
        ?string $key = null
    ): void {
        $key = $key ?: md5($css);
        $this->css[$position][$key] = $attributes === [] ? $css : Html::style($css, $attributes);
    }

    /**
     * Registers a CSS code block from file.
     *
     * @param string $path The path or URL to CSS file.
     *
     * @see registerCss()
     */
    public function registerCssFromFile(
        string $path,
        int $position = WebView::POSITION_HEAD,
        array $attributes = [],
        ?string $key = null
    ): void {
        $css = file_get_contents($path);
        if ($css === false) {
            throw new RuntimeException(sprintf('File %s could not be read.', $path));
        }

        $this->registerCss($css, $position, $attributes, $key);
    }

    /**
     * Register a {@see Style} tag.
     *
     * @see registerJs()
     */
    public function registerStyleTag(Style $style, int $position = WebView::POSITION_HEAD, ?string $key = null): void
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
     * @param string $url The CSS file to be registered.
     * @param array $options the HTML attributes for the link tag. Please refer to {@see \Yiisoft\Html\Html::cssFile()}
     * for the supported options.
     * @param string|null $key The key that identifies the CSS script file. If null, it will use $url as the key.
     * If two CSS files are registered with the same key, the latter will overwrite the former.
     */
    public function registerCssFile(
        string $url,
        int $position = WebView::POSITION_HEAD,
        array $options = [],
        string $key = null
    ): void {
        if (!$this->isValidCssPosition($position)) {
            throw new InvalidArgumentException('Invalid position of CSS file.');
        }

        $this->cssFiles[$position][$key ?: $url] = Html::cssFile($url, $options)->render();
    }

    /**
     * It processes the CSS configuration generated by the asset manager and converts it into HTML code.
     *
     * @param array $cssFiles
     */
    public function addCssFiles(array $cssFiles): void
    {
        /** @var mixed $value */
        foreach ($cssFiles as $key => $value) {
            $this->registerCssFileByConfig(
                is_string($key) ? $key : null,
                is_array($value) ? $value : [$value],
            );
        }
    }

    /**
     * It processes the CSS strings generated by the asset manager.
     *
     * @param array $cssStrings
     */
    public function addCssStrings(array $cssStrings): void
    {
        /** @var mixed $value */
        foreach ($cssStrings as $key => $value) {
            $this->registerCssStringByConfig(
                is_string($key) ? $key : null,
                is_array($value) ? $value : [$value, WebView::POSITION_HEAD],
            );
        }
    }

    /**
     * Registers a JS code block.
     *
     * @param string $js the JS code block to be registered
     * @param int $position the position at which the JS script tag should be inserted in a page.
     *
     * The possible values are:
     *
     * - {@see WebView::POSITION_HEAD}: in the head section
     * - {@see WebView::POSITION_BEGIN}: at the beginning of the body section
     * - {@see WebView::POSITION_END}: at the end of the body section. This is the default value.
     * - {@see WebView::POSITION_LOAD}: executed when HTML page is completely loaded.
     * - {@see WebView::POSITION_READY}: executed when HTML document composition is ready.
     * @param string|null $key The key that identifies the JS code block. If null, it will use $js as the key.
     * If two JS code blocks are registered with the same key, the latter will overwrite the former.
     */
    public function registerJs(string $js, int $position = WebView::POSITION_END, ?string $key = null): void
    {
        $key = $key ?: md5($js);
        $this->js[$position][$key] = $js;
    }

    /**
     * Register a `script` tag
     *
     * @see registerJs()
     */
    public function registerScriptTag(Script $script, int $position = WebView::POSITION_END, ?string $key = null): void
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
     * @param string $url The JS file to be registered.
     * @param array $options The HTML attributes for the script tag. The following options are specially handled and
     * are not treated as HTML attributes:
     *
     * - `position`: specifies where the JS script tag should be inserted in a page. The possible values are:
     *     * {@see WebView::POSITION_HEAD}: in the head section
     *     * {@see WebView::POSITION_BEGIN}: at the beginning of the body section
     *     * {@see WebView::POSITION_END}: at the end of the body section. This is the default value.
     *
     * Please refer to {@see \Yiisoft\Html\Html::javaScriptFile()} for other supported options.
     * @param string|null $key The key that identifies the JS script file. If null, it will use $url as the key.
     * If two JS files are registered with the same key at the same position, the latter will overwrite the former.
     * Note that position option takes precedence, thus files registered with the same key, but different
     * position option will not override each other.
     */
    public function registerJsFile(
        string $url,
        int $position = WebView::POSITION_END,
        array $options = [],
        string $key = null
    ): void {
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
     * @param mixed $value Value of the variable
     * @param int $position The position in a page at which the JavaScript variable should be inserted.
     *
     * The possible values are:
     *
     * - {@see WebView::POSITION_HEAD}: in the head section. This is the default value.
     * - {@see WebView::POSITION_BEGIN}: at the beginning of the body section.
     * - {@see WebView::POSITION_END}: at the end of the body section.
     * - {@see WebView::POSITION_LOAD}: enclosed within jQuery(window).load().
     *   Note that by using this position, the method will automatically register the jQuery js file.
     * - {@see POSITION_READY}: enclosed within jQuery(document).ready().
     *   Note that by using this position, the method will automatically register the jQuery js file.
     */
    public function registerJsVar(string $name, $value, int $position = WebView::POSITION_HEAD): void
    {
        $js = sprintf('var %s = %s;', $name, Json::htmlEncode($value));
        $this->registerJs($js, $position, $name);
    }

    /**
     * It processes the JS configuration generated by the asset manager and converts it into HTML code.
     *
     * @param array $jsFiles
     */
    public function addJsFiles(array $jsFiles): void
    {
        /** @var mixed $value */
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
                is_array($value) ? $value : [$value, WebView::POSITION_END]
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
        /** @var mixed $value */
        foreach ($jsVars as $key => $value) {
            if (is_string($key)) {
                $this->registerJsVar($key, $value, WebView::POSITION_HEAD);
            } else {
                $this->registerJsVarByConfig((array) $value);
            }
        }
    }

    /**
     * Clears the data for working with the event loop:
     *  - the added parameters and blocks;
     *  - the registered meta tags, link tags, css/js scripts, files and title.
     */
    public function clear(): void
    {
        $this->parameters = [];
        $this->blocks = [];
        $this->title = '';
        $this->metaTags = [];
        $this->linkTags = [];
        $this->css = [];
        $this->cssFiles = [];
        $this->js = [];
        $this->jsFiles = [];
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

        $position = (int) ($config[1] ?? WebView::POSITION_HEAD);

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

        $position = $config[1] ?? WebView::POSITION_HEAD;
        if (!$this->isValidCssPosition($position)) {
            throw new InvalidArgumentException('Invalid position of CSS strings.');
        }

        unset($config[0], $config[1]);
        if ($config !== []) {
            $css = ($css instanceof Style ? $css : Html::style($css))->attributes($config);
        }

        is_string($css)
            ? $this->registerCss($css, $position, [], $key)
            : $this->registerStyleTag($css, $position, $key);
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
                WebView::POSITION_HEAD,
                WebView::POSITION_BEGIN,
                WebView::POSITION_END,
            ],
            true,
        );
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

        $position = (int) ($config[1] ?? WebView::POSITION_END);

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

        $position = $config[1] ?? WebView::POSITION_END;
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

        $position = $config[2] ?? WebView::POSITION_HEAD;
        if (!$this->isValidJsPosition($position)) {
            throw new InvalidArgumentException('Invalid position of JS variable.');
        }

        $this->registerJsVar($key, $value, $position);
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
                WebView::POSITION_HEAD,
                WebView::POSITION_BEGIN,
                WebView::POSITION_END,
                WebView::POSITION_READY,
                WebView::POSITION_LOAD,
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
