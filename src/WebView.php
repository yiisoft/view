<?php

declare(strict_types=1);

namespace Yiisoft\View;

use InvalidArgumentException;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Script;
use Yiisoft\View\Event\BodyBegin;
use Yiisoft\View\Event\BodyEnd;
use Yiisoft\View\Event\PageEnd;

use function get_class;
use function gettype;
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
class WebView extends View
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
     * @var array the registered meta tags.
     *
     * {@see registerMetaTag()}
     */
    private array $metaTags = [];

    /**
     * @var array the registered link tags.
     *
     * {@see registerLinkTag()}
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
    }

    /**
     * Marks the beginning of an HTML body section.
     */
    public function beginBody(): void
    {
        echo sprintf(self::PLACEHOLDER_BODY_BEGIN, $this->getPlaceholderSignature());
        $this->eventDispatcher->dispatch(new BodyBegin($this->getViewFile()));
    }

    /**
     * Marks the ending of an HTML body section.
     */
    public function endBody(): void
    {
        $this->eventDispatcher->dispatch(new BodyEnd($this->getViewFile()));
        echo sprintf(self::PLACEHOLDER_BODY_END, $this->getPlaceholderSignature());
    }

    /**
     * Marks the ending of an HTML page.
     *
     * @param bool $ajaxMode whether the view is rendering in AJAX mode. If true, the JS scripts registered at
     * {@see POSITION_READY} and {@see POSITION_LOAD} positions will be rendered at the end of the view like
     * normal scripts.
     */
    public function endPage($ajaxMode = false): void
    {
        $this->eventDispatcher->dispatch(new PageEnd($this->getViewFile()));

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
    }

    /**
     * Registers a meta tag.
     *
     * For example, a description meta tag can be added like the following:
     *
     * ```php
     * $view->registerMetaTag([
     *     'name' => 'description',
     *     'content' => 'This website is about funny raccoons.'
     * ]);
     * ```
     *
     * will result in the meta tag `<meta name="description" content="This website is about funny raccoons.">`.
     *
     * @param array $options the HTML attributes for the meta tag.
     * @param string $key the key that identifies the meta tag. If two meta tags are registered with the same key, the
     * latter will overwrite the former. If this is null, the new meta tag will be appended to the
     * existing ones.
     */
    public function registerMetaTag(array $options, string $key = null): void
    {
        if ($key === null) {
            $this->metaTags[] = Html::meta()->attributes($options)->render();
        } else {
            $this->metaTags[$key] = Html::meta()->attributes($options)->render();
        }
    }

    /**
     * Registers a link tag.
     *
     * For example, a link tag for a custom [favicon](http://www.w3.org/2005/10/howto-favicon) can be added like the
     * following:
     *
     * ```php
     * $view->registerLinkTag(['rel' => 'icon', 'type' => 'image/png', 'href' => '/myicon.png']);
     * ```
     *
     * which will result in the following HTML: `<link rel="icon" type="image/png" href="/myicon.png">`.
     *
     * **Note:** To register link tags for CSS stylesheets, use {@see registerCssFile()]} instead, which has more
     * options for this kind of link tag.
     *
     * @param array $options the HTML attributes for the link tag.
     * @param string|null $key the key that identifies the link tag. If two link tags are registered with the same
     * key, the latter will overwrite the former. If this is null, the new link tag will be appended
     * to the existing ones.
     */
    public function registerLinkTag(array $options, ?string $key = null): void
    {
        if ($key === null) {
            $this->linkTags[] = Html::link()->attributes($options)->render();
        } else {
            $this->linkTags[$key] = Html::link()->attributes($options)->render();
        }
    }

    /**
     * Registers a CSS code block.
     *
     * @param string $css the content of the CSS code block to be registered
     * @param array $options the HTML attributes for the `<style>`-tag.
     * @param string $key the key that identifies the CSS code block. If null, it will use $css as the key. If two CSS
     * code blocks are registered with the same key, the latter will overwrite the former.
     */
    public function registerCss(string $css, array $options = [], string $key = null): void
    {
        $key = $key ?: md5($css);
        $this->css[$key] = Html::style($css, $options)->render();
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
    public function registerCssFile(string $url, array $options = [], string $key = null): void
    {
        $key = $key ?: $url;

        $this->cssFiles[$key] = Html::cssFile($url, $options)->render();
    }

    /**
     * Registers a JS code block.
     *
     * @param Script|string $js the JS code block to be registered
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
    public function registerJs($js, int $position = self::POSITION_END, string $key = null): void
    {
        if (!is_string($js) && !($js instanceof Script)) {
            throw new InvalidArgumentException(
                sprintf(
                    'JavaScript should be string or instance of \Yiisoft\Html\Tag\Script. Got %s.',
                    is_object($js) ? get_class($js) : gettype($js),
                )
            );
        }

        $key = $key ?: md5($js);
        $this->js[$position][$key] = $js;
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
    public function registerJsFile(string $url, array $options = [], string $key = null): void
    {
        $key = $key ?: $url;

        $position = ArrayHelper::remove($options, 'position', self::POSITION_END);
        $this->jsFiles[$position][$key] = Html::javaScriptFile($url, $options)->render();
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
    public function registerJsVar(string $name, $value, int $position = self::POSITION_HEAD): void
    {
        $js = sprintf('var %s = %s;', $name, \Yiisoft\Json\Json::htmlEncode($value));
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

        if (!empty($this->linkTags)) {
            $lines[] = implode("\n", $this->linkTags);
        }
        if (!empty($this->cssFiles)) {
            $lines[] = implode("\n", $this->cssFiles);
        }
        if (!empty($this->css)) {
            $lines[] = implode("\n", $this->css);
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

        if (!empty($this->jsFiles[self::POSITION_END])) {
            $lines[] = implode("\n", $this->jsFiles[self::POSITION_END]);
        }

        if ($ajaxMode) {
            $scripts = array_merge(
                $this->js[self::POSITION_END],
                $this->js[self::POSITION_READY],
                $this->js[self::POSITION_LOAD],
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
    public function setCssFiles(array $cssFiles): void
    {
        foreach ($cssFiles as $key => $value) {
            $this->registerCssFile(
                $cssFiles[$key]['url'],
                $cssFiles[$key]['attributes']
            );
        }
    }

    /**
     * It processes the JS configuration generated by the asset manager and converts it into HTML code.
     *
     * @param array $jsFiles
     */
    public function setJsFiles(array $jsFiles): void
    {
        foreach ($jsFiles as $key => $value) {
            $this->registerJsFile(
                $jsFiles[$key]['url'],
                $jsFiles[$key]['attributes']
            );
        }
    }

    /**
     * It processes the JS strings generated by the asset manager.
     *
     * @param array $jsStrings
     */
    public function setJsStrings(array $jsStrings): void
    {
        foreach ($jsStrings as $value) {
            $this->registerJs(
                $value['string'],
                $value['attributes']['position']
            );
        }
    }

    /**
     * It processes the JS variables generated by the asset manager and converts it into JS code.
     *
     * @param array $jsVar
     */
    public function setJsVar(array $jsVar): void
    {
        foreach ($jsVar as $key => $value) {
            $this->registerJsVar(
                (string)$key,
                $value['variables'],
                $value['attributes']['position']
            );
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
