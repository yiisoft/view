<?php
declare(strict_types = 1);

namespace Yiisoft\View;

use Yiisoft\Asset\AssetBundle;
use Yiisoft\Asset\AssetManager;
use Yiisoft\Html\Html;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\View\Event\BodyBegin;
use Yiisoft\View\Event\BodyEnd;
use Yiisoft\View\Event\PageEnd;

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
    public const POS_HEAD = 1;

    /**
     * The location of registered JavaScript code block or files.
     * This means the location is at the beginning of the body section.
     */
    public const POS_BEGIN = 2;

    /**
     * The location of registered JavaScript code block or files.
     * This means the location is at the end of the body section.
     */
    public const POS_END = 3;

    /**
     * The location of registered JavaScript code block.
     * This means the JavaScript code block will be executed when HTML document composition is ready.
     */
    public const POS_READY = 4;

    /**
     * The location of registered JavaScript code block.
     * This means the JavaScript code block will be executed when HTML page is completely loaded.
     */
    public const POS_LOAD = 5;

    /**
     * This is internally used as the placeholder for receiving the content registered for the head section.
     */
    private const PH_HEAD = '<![CDATA[YII-BLOCK-HEAD]]>';

    /**
     * This is internally used as the placeholder for receiving the content registered for the beginning of the body
     * section.
     */
    private const PH_BODY_BEGIN = '<![CDATA[YII-BLOCK-BODY-BEGIN]]>';

    /**
     * This is internally used as the placeholder for receiving the content registered for the end of the body section.
     */
    private const PH_BODY_END = '<![CDATA[YII-BLOCK-BODY-END]]>';

    /**
     * @var AssetBundle[] list of the registered asset bundles. The keys are the bundle names, and the values
     * are the registered {@see AssetBundle} objects.
     *
     * {@see registerAssetBundle()}
     */
    private $assetBundles = [];

    /**
     * Undocumented variable
     *
     * @var AssetManager $assetManager
     */
    private $assetManager;

    /**
     * @var string the page title
     */
    private $title;

    /**
     * @var array the registered meta tags.
     *
     * {@see registerMetaTag()}
     */
    private $metaTags = [];

    /**
     * @var array the registered link tags.
     *
     * {@see registerLinkTag()}
     */
    private $linkTags = [];

    /**
     * @var array the registered CSS code blocks.
     *
     * {@see registerCss()}
     */
    private $css = [];

    /**
     * @var array the registered CSS files.
     *
     * {@see registerCssFile()}
     */
    private $cssFiles = [];

    /**
     * @var array the registered JS code blocks
     *
     * {@see registerJs()}
     */
    private $js = [];

    /**
     * @var array the registered JS files.
     *
     * {@see registerJsFile()}
     */
    private $jsFiles = [];

    /**
     * Marks the position of an HTML head section.
     */
    public function head(): void
    {
        echo self::PH_HEAD;
    }

    /**
     * Marks the beginning of an HTML body section.
     */
    public function beginBody(): void
    {
        echo self::PH_BODY_BEGIN;
        $this->eventDispatcher->dispatch(new BodyBegin($this->getViewFile()));
    }

    /**
     * Marks the ending of an HTML body section.
     */
    public function endBody(): void
    {
        $this->eventDispatcher->dispatch(new BodyEnd($this->getViewFile()));
        echo self::PH_BODY_END;

        foreach (array_keys($this->assetBundles) as $bundle) {
            $this->registerAssetFiles($bundle);
        }
    }

    /**
     * Marks the ending of an HTML page.
     *
     * @param bool $ajaxMode whether the view is rendering in AJAX mode. If true, the JS scripts registered at
     *             {@see POS_READY} and {@see POS_LOAD} positions will be rendered at the end of the view like
     *             normal scripts.
     */
    public function endPage($ajaxMode = false): void
    {
        $this->eventDispatcher->dispatch(new PageEnd($this->getViewFile()));

        $content = ob_get_clean();

        echo strtr($content, [
            self::PH_HEAD => $this->renderHeadHtml(),
            self::PH_BODY_BEGIN => $this->renderBodyBeginHtml(),
            self::PH_BODY_END => $this->renderBodyEndHtml($ajaxMode),
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
     * @param string $view the view name. Please refer to [[render()]] on how to specify this parameter.
     * @param array  $params the parameters (name-value pairs) that will be extracted and made available in the view
     *               file.
     * @param object $context the context that the view should use for rendering the view. If null, existing [[context]]
     *               will be used.
     *
     * @return string the rendering result
     *
     * {@see render()}
     */
    public function renderAjax(string $view, array $params = [], $context = null): string
    {
        $viewFile = $this->findViewFile($view, $context);

        ob_start();
        ob_implicit_flush(0);

        $this->beginPage();
        $this->head();
        $this->beginBody();
        echo $this->renderFile($viewFile, $params, $context);
        $this->endBody();
        $this->endPage(true);

        return ob_get_clean();
    }

    /**
     * Registers the asset manager being used by this view object.
     *
     * @return array the asset manager. Defaults to the "assetManager" application component.
     */
    public function getAssetBundles(): array
    {
        return $this->assetBundles;
    }

    /**
     * Registers the asset manager being used by this view object.
     *
     * @return AssetManager the asset manager. Defaults to the "assetManager" application component.
     */
    public function getAssetManager(): AssetManager
    {
        return $this->assetManager;
    }

    /**
     * Sets the asset manager.
     *
     * @param AssetManager $value the asset manager
     *
     * @return void
     */
    public function setAssetManager(AssetManager $value): void
    {
        $this->assetManager = $value;
    }

    /**
     * Clears up the registered meta tags, link tags, css/js scripts and files.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->metaTags = [];
        $this->linkTags = [];
        $this->css = [];
        $this->cssFiles = [];
        $this->js = [];
        $this->jsFiles = [];
        $this->assetBundles = [];
    }

    /**
     * Registers all files provided by an asset bundle including depending bundles files.
     *
     * Removes a bundle from {@see assetBundles} once files are registered.
     *
     * @param string $name name of the bundle to register
     *
     * @return void
     */
    protected function registerAssetFiles(string $name): void
    {
        if (!isset($this->assetBundles[$name])) {
            return;
        }

        $bundle = $this->assetBundles[$name];

        if ($bundle) {
            foreach ($bundle->depends as $dep) {
                $this->registerAssetFiles($dep);
            }
            $bundle->registerAssetFiles($this);
        }

        unset($this->assetBundles[$name]);
    }

    /**
     * Registers the named asset bundle.
     *
     * All dependent asset bundles will be registered.
     *
     * @param string   $name the class name of the asset bundle (without the leading backslash)
     * @param int|null $position if set, this forces a minimum position for javascript files. This will adjust depending
     *                 assets javascript file position or fail if requirement can not be met. If this is null, asset
     *                 bundles position settings will not be changed.
     *
     * {@see registerJsFile()} for more details on javascript position.
     *
     * @throws \RuntimeException if the asset bundle does not exist or a circular dependency is detected
     *
     * @return AssetBundle the registered asset bundle instance
     */
    public function registerAssetBundle(string $name, ?int $position = null): AssetBundle
    {
        if (!isset($this->assetBundles[$name])) {
            $am = $this->getAssetManager();
            $bundle = $am->getBundle($name);

            $this->assetBundles[$name] = false;

            // register dependencies

            $pos = isset($bundle->jsOptions['position']) ? $bundle->jsOptions['position'] : null;

            foreach ($bundle->depends as $dep) {
                $this->registerAssetBundle($dep, $pos);
            }

            $this->assetBundles[$name] = $bundle;
        } elseif ($this->assetBundles[$name] === false) {
            throw new \RuntimeException("A circular dependency is detected for bundle '$name'.");
        } else {
            $bundle = $this->assetBundles[$name];
        }

        if ($position !== null) {
            $pos = isset($bundle->jsOptions['position']) ? $bundle->jsOptions['position'] : null;

            if ($pos === null) {
                $bundle->jsOptions['position'] = $pos = $position;
            } elseif ($pos > $position) {
                throw new \RuntimeException("An asset bundle that depends on '$name' has a higher javascript file position configured than '$name'.");
            }

            // update position for all dependencies
            foreach ($bundle->depends as $dep) {
                $this->registerAssetBundle($dep, $pos);
            }
        }

        return $bundle;
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
     * @param array  $options the HTML attributes for the meta tag.
     * @param string $key the key that identifies the meta tag. If two meta tags are registered with the same key, the
     *               latter will overwrite the former. If this is null, the new meta tag will be appended to the
     *               existing ones.
     *
     * @return void
     */
    public function registerMetaTag(array $options, string $key = null): void
    {
        if ($key === null) {
            $this->metaTags[] = Html::tag('meta', '', $options);
        } else {
            $this->metaTags[$key] = Html::tag('meta', '', $options);
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
     * **Note:** To register link tags for CSS stylesheets, use [[registerCssFile()]] instead, which has more options
     * for this kind of link tag.
     *
     * @param array       $options the HTML attributes for the link tag.
     * @param string|null $key the key that identifies the link tag. If two link tags are registered with the same
     *                    key, the latter will overwrite the former. If this is null, the new link tag will be appended
     *                    to the existing ones.
     */
    public function registerLinkTag(array $options, ?string $key = null): void
    {
        if ($key === null) {
            $this->linkTags[] = Html::tag('link', '', $options);
        } else {
            $this->linkTags[$key] = Html::tag('link', '', $options);
        }
    }

    /**
     * Registers CSRF meta tags.
     *
     * They are rendered dynamically to retrieve a new CSRF token for each request.
     *
     * ```php
     * $view->registerCsrfMetaTags();
     * ```
     *
     * The above code will result in `<meta name="csrf-param" content="[Yiisoft\Web\Request::$csrfParam]">` and
     * `<meta name="csrf-token" content="tTNpWKpdy-bx8ZmIq9R72...K1y8IP3XGkzZA==">` added to the page.
     *
     * Note: Hidden CSRF input of ActiveForm will be automatically refreshed by calling `window.yii.refreshCsrfToken()`
     * from `yii.js`.
     */
    public function registerCsrfMetaTags(): void
    {
        $this->metaTags['csrf_meta_tags'] = $this->renderDynamic('return Yiisoft\Html\Html::csrfMetaTags();');
    }

    /**
     * Registers a CSS code block.
     *
     * @param string $css the content of the CSS code block to be registered
     * @param array  $options the HTML attributes for the `<style>`-tag.
     * @param string $key the key that identifies the CSS code block. If null, it will use $css as the key. If two CSS
     *               code blocks are registered with the same key, the latter will overwrite the former.
     */
    public function registerCss(string $css, array $options = [], string $key = null): void
    {
        $key = $key ?: md5($css);
        $this->css[$key] = Html::style($css, $options);
    }

    /**
     * Registers a CSS file.
     *
     * This method should be used for simple registration of CSS files. If you want to use features of
     * {@see AssetManager} like appending timestamps to the URL and file publishing options, use {@see AssetBundle}
     * and {@see registerAssetBundle()} instead.
     *
     * @param string $url the CSS file to be registered.
     * @param array  $options the HTML attributes for the link tag. Please refer to {@see \Yiisoft\Html\Html::cssFile()}
     *               for the supported options. The following options are specially handled and are not treated as HTML
     *               attributes:
     *
     *               - `depends`: array, specifies the names of the asset bundles that this CSS file depends on.
     * @param string $key the key that identifies the CSS script file. If null, it will use $url as the key. If two CSS
     *               files are registered with the same key, the latter will overwrite the former.
     *
     * @return void
     */
    public function registerCssFile(string $url, array $options = [], string $key = null): void
    {
        $key = $key ?: $url;

        $depends = ArrayHelper::remove($options, 'depends', []);

        if (empty($depends)) {
            $this->cssFiles[$key] = Html::cssFile($url, $options);
        } else {
            $bundle = $this->createBundle([
                'baseUrl' => '',
                'css' => [strncmp($url, '//', 2) === 0 ? $url : ltrim($url, '/')],
                'cssOptions' => $options,
                'depends' => (array) $depends,
            ]);
            $bundles[$key] = $bundle;

            $this->registerAssetBundle($key);
        }
    }

    /**
     * Registers a JS code block.
     *
     * @param string $js the JS code block to be registered
     * @param int    $position the position at which the JS script tag should be inserted in a page.
     *
     * The possible values are:
     *
     * - [[POS_HEAD]]: in the head section
     * - [[POS_BEGIN]]: at the beginning of the body section
     * - [[POS_END]]: at the end of the body section. This is the default value.
     * - [[POS_LOAD]]: executed when HTML page is completely loaded.
     * - [[POS_READY]]: executed when HTML document composition is ready.
     *
     * @param string $key the key that identifies the JS code block. If null, it will use $js as the key. If two JS code
     *               blocks are registered with the same key, the latter will overwrite the former.
     *
     * @return void
     */
    public function registerJs(string $js, int $position = self::POS_END, string $key = null): void
    {
        $key = $key ?: md5($js);
        $this->js[$position][$key] = $js;
    }

    /**
     * Registers a JS file.
     *
     * This method should be used for simple registration of JS files. If you want to use features of
     * {@see AssetManager} like appending timestamps to the URL and file publishing options, use {@see AssetBundle}
     * and {@see registerAssetBundle()} instead.
     *
     * @param string $url the JS file to be registered.
     * @param array  $options the HTML attributes for the script tag. The following options are specially handled and
     *               are not treated as HTML attributes:
     *
     * - `depends`: array, specifies the names of the asset bundles that this JS file depends on.
     * - `position`: specifies where the JS script tag should be inserted in a page. The possible values are:
     *     * [[POS_HEAD]]: in the head section
     *     * [[POS_BEGIN]]: at the beginning of the body section
     *     * [[POS_END]]: at the end of the body section. This is the default value.
     *
     * Please refer to {@see \Yiisoft\Html\Html::jsFile()} for other supported options.
     *
     * @param string $key the key that identifies the JS script file. If null, it will use $url as the key. If two JS
     *               files are registered with the same key at the same position, the latter will overwrite the former.
     *               Note that position option takes precedence, thus files registered with the same key, but different
     *               position option will not override each other.
     *
     * @return void
     */
    public function registerJsFile(string $url, array $options = [], string $key = null): void
    {
        $key = $key ?: $url;

        $depends = ArrayHelper::remove($options, 'depends', []);

        if (empty($depends)) {
            $position = ArrayHelper::remove($options, 'position', self::POS_END);
            $this->jsFiles[$position][$key] = Html::jsFile($url, $options);
        } else {
            $bundle = $this->createBundle([
                'baseUrl' => '',
                'js' => [strncmp($url, '//', 2) === 0 ? $url : ltrim($url, '/')],
                'jsOptions' => $options,
                'depends' => (array) $depends,
            ]);
            $bundles[$key] = $bundle;
            $this->registerAssetBundle($key);
        }
    }

    /**
     * Registers a JS code block defining a variable. The name of variable will be used as key, preventing duplicated
     * variable names.
     *
     * @param string       $name Name of the variable
     * @param array|string $value Value of the variable
     * @param int          $position the position in a page at which the JavaScript variable should be inserted.
     *
     * The possible values are:
     *
     * - [[POS_HEAD]]: in the head section. This is the default value.
     * - [[POS_BEGIN]]: at the beginning of the body section.
     * - [[POS_END]]: at the end of the body section.
     * - [[POS_LOAD]]: enclosed within jQuery(window).load().
     *   Note that by using this position, the method will automatically register the jQuery js file.
     * - [[POS_READY]]: enclosed within jQuery(document).ready().
     *   Note that by using this position, the method will automatically register the jQuery js file.
     */
    public function registerJsVar(string $name, $value, int $position = self::POS_HEAD): void
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
        if (!empty($this->jsFiles[self::POS_HEAD])) {
            $lines[] = implode("\n", $this->jsFiles[self::POS_HEAD]);
        }
        if (!empty($this->js[self::POS_HEAD])) {
            $lines[] = Html::script(implode("\n", $this->js[self::POS_HEAD]));
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
        if (!empty($this->jsFiles[self::POS_BEGIN])) {
            $lines[] = implode("\n", $this->jsFiles[self::POS_BEGIN]);
        }
        if (!empty($this->js[self::POS_BEGIN])) {
            $lines[] = Html::script(implode("\n", $this->js[self::POS_BEGIN]));
        }

        return empty($lines) ? '' : implode("\n", $lines);
    }

    /**
     * Renders the content to be inserted at the end of the body section.
     *
     * The content is rendered using the registered JS code blocks and files.
     *
     * @param bool $ajaxMode whether the view is rendering in AJAX mode. If true, the JS scripts registered at
     *             [[POS_READY]] and [[POS_LOAD]] positions will be rendered at the end of the view like normal scripts.
     *
     * @return string the rendered content
     */
    protected function renderBodyEndHtml(bool $ajaxMode): string
    {
        $lines = [];

        if (!empty($this->jsFiles[self::POS_END])) {
            $lines[] = implode("\n", $this->jsFiles[self::POS_END]);
        }

        if ($ajaxMode) {
            $scripts = [];
            if (!empty($this->js[self::POS_END])) {
                $scripts[] = implode("\n", $this->js[self::POS_END]);
            }
            if (!empty($this->js[self::POS_READY])) {
                $scripts[] = implode("\n", $this->js[self::POS_READY]);
            }
            if (!empty($this->js[self::POS_LOAD])) {
                $scripts[] = implode("\n", $this->js[self::POS_LOAD]);
            }
            if (!empty($scripts)) {
                $lines[] = Html::script(implode("\n", $scripts));
            }
        } else {
            if (!empty($this->js[self::POS_END])) {
                $lines[] = Html::script(implode("\n", $this->js[self::POS_END]));
            }
            if (!empty($this->js[self::POS_READY])) {
                $js = "document.addEventListener('DOMContentLoaded', function(event) {\n" . implode("\n", $this->js[self::POS_READY]) . "\n});";
                $lines[] = Html::script($js, ['type' => 'text/javascript']);
            }
            if (!empty($this->js[self::POS_LOAD])) {
                $js = "window.addEventListener('load', function (event) {\n" . implode("\n", $this->js[self::POS_LOAD]) . "\n});";
                $lines[] = Html::script($js, ['type' => 'text/javascript']);
            }
        }

        return empty($lines) ? '' : implode("\n", $lines);
    }

    /**
     * @param array $options
     *
     * @return AssetBundle
     */
    private function createBundle(array $options): AssetBundle
    {
        $bundle = new AssetBundle();
        $bundle->baseUrl = $options['basUrl'];
        $bundle->js = $options['js'];
        $bundle->jsOptions = $options['jsOptions'];
        $bundle->depends = $options['depends'];

        return $bundle;
    }
}
