<?php

namespace Yiisoft\View;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use yii\i18n\Locale;
use Yiisoft\View\Event\AfterRender;
use Yiisoft\View\Event\BeforeRender;
use Yiisoft\View\Event\PageBegin;
use Yiisoft\View\Event\PageEnd;
use Yiisoft\Widget\Block;
use Yiisoft\Widget\ContentDecorator;
use Yiisoft\Widget\FragmentCache;

/**
 * View represents a view object in the MVC pattern.
 *
 * View provides a set of methods (e.g. [[render()]]) for rendering purpose.
 *
 * For more details and usage information on View, see the [guide article on views](guide:structure-views).
 *
 * @property string|bool $viewFile The view file currently being rendered. False if no view file is being
 * rendered. This property is read-only.
 */
class View implements DynamicContentAwareInterface
{
    /**
     * @var ViewContextInterface the context under which the [[renderFile()]] method is being invoked.
     */
    public $context;
    /**
     * @var mixed custom parameters that are shared among view templates.
     */
    public $params = [];
    /**
     * @var array a list of available renderers indexed by their corresponding supported file extensions.
     *            Each renderer may be a view renderer object or the configuration for creating the renderer object.
     *            For example, the following configuration enables the Twig view renderer:
     *
     * ```php
     * [
     *     'twig' => ['__class' => \Yiisoft\Yii\Twig\ViewRenderer::class],
     * ]
     * ```
     *
     * If no renderer is available for the given view file, the view file will be treated as a normal PHP
     * and rendered via [[renderPhpFile()]].
     */
    protected $renderers = [];
    /**
     * @var string the default view file extension. This will be appended to view file names if they don't have file extensions.
     */
    public $defaultExtension = 'php';
    /**
     * @var Theme the theme object.
     */
    protected $theme;
    /**
     * @var array a list of named output blocks. The keys are the block names and the values
     *            are the corresponding block content. You can call [[beginBlock()]] and [[endBlock()]]
     *            to capture small fragments of a view. They can be later accessed somewhere else
     *            through this property.
     */
    public $blocks;
    /**
     * @var DynamicContentAwareInterface[] a list of currently active dynamic content class instances.
     */
    private $_cacheStack = [];
    /**
     * @var Locale source locale used to find localized view file.
     */
    private $_sourceLocale;
    /**
     * @var array a list of placeholders for embedding dynamic contents.
     */
    private $_dynamicPlaceholders = [];
    /**
     * @var array the view files currently being rendered. There may be multiple view files being
     *            rendered at a moment because one view may be rendered within another.
     */
    private $_viewFiles = [];

    protected $eventDispatcher;

    private $basePath;

    private $logger;

    private $sourceLanguage = 'en';
    private $language = 'en';

    public function __construct(string $basePath, Theme $theme, EventDispatcherInterface $eventDispatcher, LoggerInterface $logger)
    {
        $this->basePath = $basePath;
        $this->theme = $theme;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
    }

    public function setRenderers(array $renderers)
    {
        $this->renderers = $renderers;
    }

    public function setSourceLanguage(string $language)
    {
        $this->sourceLanguage = $language;
    }

    public function setLanguage(string $language)
    {
        $this->language = $language;
    }

    /**
     * Renders a view.
     *
     * The view to be rendered can be specified in one of the following formats:
     *
     * - [path alias](guide:concept-aliases) (e.g. "@app/views/site/index");
     * - absolute path within application (e.g. "//site/index"): the view name starts with double slashes.
     *   The actual view file will be looked for under the [[Application::viewPath|view path]] of the application.
     * - absolute path within current module (e.g. "/site/index"): the view name starts with a single slash.
     *   The actual view file will be looked for under the [[Module::viewPath|view path]] of the [[Controller::module|current module]].
     * - relative view (e.g. "index"): the view name does not start with `@` or `/`. The corresponding view file will be
     *   looked for under the [[ViewContextInterface::getViewPath()|view path]] of the view `$context`.
     *   If `$context` is not given, it will be looked for under the directory containing the view currently
     *   being rendered (i.e., this happens when rendering a view within another view).
     *
     * @param string $view the view name.
     * @param array $params the parameters (name-value pairs) that will be extracted and made available in the view file.
     * @param object $context the context to be assigned to the view and can later be accessed via [[context]]
     * in the view. If the context implements [[ViewContextInterface]], it may also be used to locate
     * the view file corresponding to a relative view name.
     *
     * @return string the rendering result
     *
     * @throws InvalidCallException  if the view cannot be resolved.
     *
     * @throws ViewNotFoundException if the view file does not exist.
     * @see renderFile()
     */
    public function render($view, $params = [], $context = null)
    {
        $viewFile = $this->findTemplateFile($view, $context);

        return $this->renderFile($viewFile, $params, $context);
    }

    /**
     * Finds the view file based on the given view name.
     *
     * @param string $view the view name or the [path alias](guide:concept-aliases) of the view file. Please refer to [[render()]]
     * on how to specify this parameter.
     * @param object $context the context to be assigned to the view and can later be accessed via [[context]]
     * in the view. If the context implements [[ViewContextInterface]], it may also be used to locate
     * the view file corresponding to a relative view name.
     *
     * @return string the view file path. Note that the file may not exist.
     * @throws InvalidCallException if a relative view name is given while there is no active context to
     * determine the corresponding view file.
     *
     */
    protected function findTemplateFile(string $view, ViewContextInterface $context = null): string
    {
        if (strncmp($view, '//', 2) === 0) {
            // e.g. "//layouts/main"
            $file = $this->basePath . '/' . ltrim($view, '/');
        } elseif ($context !== null) {
            $file = $context->getViewPath() . '/' . $view;
        } elseif (($currentViewFile = $this->getRequestedViewFile()) !== false) {
            $file = dirname($currentViewFile) . '/' . $view;
        } else {
            // TODO: this isn't right
            $file = $view;
        }

        if (pathinfo($file, PATHINFO_EXTENSION) !== '') {
            return $file;
        }
        $path = $file . '.' . $this->defaultExtension;
        if ($this->defaultExtension !== 'php' && !is_file($path)) {
            $path = $file . '.php';
        }

        return $path;
    }

    /**
     * Renders a view file.
     *
     * If [[theme]] is enabled (not null), it will try to render the themed version of the view file as long
     * as it is available.
     *
     * The method will call [[FileHelper::localize()]] to localize the view file.
     *
     * If [[renderers|renderer]] is enabled (not null), the method will use it to render the view file.
     * Otherwise, it will simply include the view file as a normal PHP file, capture its output and
     * return it as a string.
     *
     * @param string $viewFile the view file. This can be either an absolute file path or an alias of it.
     * @param array $params the parameters (name-value pairs) that will be extracted and made available in the view file.
     * @param object $context the context that the view should use for rendering the view. If null,
     *                         existing [[context]] will be used.
     *
     * @return string the rendering result
     * @throws ViewNotFoundException if the view file does not exist
     *
     */
    public function renderFile($viewFile, $params = [], $context = null)
    {
        // TODO: these two match now
        $requestedFile = $viewFile;

        if ($this->theme !== null) {
            $viewFile = $this->theme->applyTo($viewFile);
        }
        if (is_file($viewFile)) {
            $viewFile = $this->localize($viewFile);
        } else {
            throw new ViewNotFoundException("The view file does not exist: $viewFile");
        }

        $oldContext = $this->context;
        if ($context !== null) {
            $this->context = $context;
        }
        $output = '';
        $this->_viewFiles[] = [
            'resolved' => $viewFile,
            'requested' => $requestedFile,
        ];

        if ($this->beforeRender($viewFile, $params)) {
            $this->logger->debug("Rendering view file: $viewFile");
            $ext = pathinfo($viewFile, PATHINFO_EXTENSION);
            if (isset($this->renderers[$ext])) {
                /* @var $renderer TemplateRenderer */
                $renderer = $this->renderers[$ext];
                $output = $renderer->render($this, $viewFile, $params);
            } else {
                $output = $this->renderPhpFile($viewFile, $params);
            }
            $this->afterRender($viewFile, $params, $output);
        }

        array_pop($this->_viewFiles);
        $this->context = $oldContext;

        return $output;
    }

    /**
     * Returns the localized version of a specified file.
     *
     * The searching is based on the specified language code. In particular,
     * a file with the same name will be looked for under the subdirectory
     * whose name is the same as the language code. For example, given the file "path/to/view.php"
     * and language code "zh-CN", the localized file will be looked for as
     * "path/to/zh-CN/view.php". If the file is not found, it will try a fallback with just a language code that is
     * "zh" i.e. "path/to/zh/view.php". If it is not found as well the original file will be returned.
     *
     * If the target and the source language codes are the same,
     * the original file will be returned.
     *
     * @param string $file the original file
     * @param string $language the target language that the file should be localized to.
     *                               If not set, the value of [[\yii\base\Application::language]] will be used.
     * @param string $sourceLanguage the language that the original file is in.
     *                               If not set, the value of [[\yii\base\Application::sourceLanguage]] will be used.
     *
     * @return string the matching localized file, or the original file if the localized version is not found.
     *                If the target and the source language codes are the same, the original file will be returned.
     */
    public function localize($file, $language = null, $sourceLanguage = null)
    {
        $language = $language ?? $this->language;
        $sourceLanguage = $sourceLanguage ?? $this->sourceLanguage;

        if ($language === $sourceLanguage) {
            return $file;
        }
        $desiredFile = dirname($file) . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . basename($file);
        if (is_file($desiredFile)) {
            return $desiredFile;
        }

        $language = substr($language, 0, 2);
        if ($language === $sourceLanguage) {
            return $file;
        }
        $desiredFile = dirname($file) . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . basename($file);

        return is_file($desiredFile) ? $desiredFile : $file;
    }

    /**
     * @return string|bool the view file currently being rendered. False if no view file is being rendered.
     */
    public function getViewFile()
    {
        return empty($this->_viewFiles) ? false : end($this->_viewFiles)['resolved'];
    }

    /**
     * @return string|bool the requested view currently being rendered. False if no view file is being rendered.
     */
    protected function getRequestedViewFile()
    {
        return empty($this->_viewFiles) ? false : end($this->_viewFiles)['requested'];
    }

    /**
     * This method is invoked right before [[renderFile()]] renders a view file.
     * The default implementation will trigger the [[BeforeRender]] event.
     * If you override this method, make sure you call the parent implementation first.
     *
     * @param string $viewFile the view file to be rendered.
     * @param array $params the parameter array passed to the [[render()]] method.
     *
     * @return bool whether to continue rendering the view file.
     */
    public function beforeRender(string $viewFile, array $params): bool
    {
        $event = new BeforeRender($viewFile, $params);
        $this->eventDispatcher->dispatch($event);

        return $event->isPropagationStopped();
    }

    /**
     * This method is invoked right after [[renderFile()]] renders a view file.
     * The default implementation will trigger the [[AfterRender]] event.
     * If you override this method, make sure you call the parent implementation first.
     *
     * @param string $viewFile the view file being rendered.
     * @param array $params the parameter array passed to the [[render()]] method.
     * @param string $output the rendering result of the view file. Updates to this parameter
     *                         will be passed back and returned by [[renderFile()]].
     */
    public function afterRender(string $viewFile, array $params, &$output): string
    {
        $event = new AfterRender($viewFile, $params, $output);
        $this->eventDispatcher->dispatch($event);

        return $event->getResult();
    }

    /**
     * Renders a view file as a PHP script.
     *
     * This method treats the view file as a PHP script and includes the file.
     * It extracts the given parameters and makes them available in the view file.
     * The method captures the output of the included view file and returns it as a string.
     *
     * This method should mainly be called by view renderer or [[renderFile()]].
     *
     * @param string $_file_ the view file.
     * @param array $_params_ the parameters (name-value pairs) that will be extracted and made available in the view file.
     *
     * @return string the rendering result
     * @throws \Throwable
     *
     * @throws \Exception
     */
    public function renderPhpFile($_file_, $_params_ = [])
    {
        $_obInitialLevel_ = ob_get_level();
        ob_start();
        ob_implicit_flush(false);
        extract($_params_, EXTR_OVERWRITE);

        try {
            require $_file_;

            return ob_get_clean();
        } catch (\Throwable $e) {
            while (ob_get_level() > $_obInitialLevel_) {
                if (!@ob_end_clean()) {
                    ob_clean();
                }
            }

            throw $e;
        }
    }

    /**
     * Renders dynamic content returned by the given PHP statements.
     * This method is mainly used together with content caching (fragment caching and page caching)
     * when some portions of the content (called *dynamic content*) should not be cached.
     * The dynamic content must be returned by some PHP statements. You can optionally pass
     * additional parameters that will be available as variables in the PHP statement:.
     *
     * ```php
     * <?= $this->renderDynamic('return foo($myVar);', [
     *     'myVar' => $model->getMyComplexVar(),
     * ]) ?>
     * ```
     *
     * @param string $statements the PHP statements for generating the dynamic content.
     * @param array $params the parameters (name-value pairs) that will be extracted and made
     *                           available in the $statement context. The parameters will be stored in the cache and be reused
     *                           each time $statement is executed. You should make sure, that these are safely serializable.
     *
     * @return string the placeholder of the dynamic content, or the dynamic content if there is no
     *                active content cache currently.
     */
    public function renderDynamic($statements, array $params = [])
    {
        if (!empty($params)) {
            $statements = 'extract(unserialize(\'' . str_replace(['\\', '\''], ['\\\\', '\\\''], serialize($params)) . '\'));' . $statements;
        }

        if (!empty($this->_cacheStack)) {
            $n = count($this->_dynamicPlaceholders);
            $placeholder = "<![CDATA[YII-DYNAMIC-$n]]>";
            $this->addDynamicPlaceholder($placeholder, $statements);

            return $placeholder;
        }

        return $this->evaluateDynamicContent($statements);
    }

    public function getSourceLocale(): Locale
    {
        if ($this->_sourceLocale === null) {
            $this->_sourceLocale = Locale::create('en-US');
        }

        return $this->_sourceLocale;
    }

    public function setSourceLocale($locale): self
    {
        $this->_sourceLocale = Locale::create($locale);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDynamicPlaceholders(): array
    {
        return $this->_dynamicPlaceholders;
    }

    /**
     * {@inheritdoc}
     */
    public function setDynamicPlaceholders(array $placeholders): void
    {
        $this->_dynamicPlaceholders = $placeholders;
    }

    /**
     * {@inheritdoc}
     */
    public function addDynamicPlaceholder(string $placeholder, string $statements): void
    {
        foreach ($this->_cacheStack as $cache) {
            $cache->addDynamicPlaceholder($placeholder, $statements);
        }

        $this->_dynamicPlaceholders[$placeholder] = $statements;
    }

    /**
     * Evaluates the given PHP statements.
     * This method is mainly used internally to implement dynamic content feature.
     *
     * @param string $statements the PHP statements to be evaluated.
     *
     * @return mixed the return value of the PHP statements.
     */
    public function evaluateDynamicContent($statements)
    {
        return eval($statements);
    }

    /**
     * Returns a list of currently active dynamic content class instances.
     *
     * @return DynamicContentAwareInterface[] class instances supporting dynamic contents.
     */
    public function getDynamicContents()
    {
        return $this->_cacheStack;
    }

    /**
     * Adds a class instance supporting dynamic contents to the end of a list of currently active
     * dynamic content class instances.
     *
     * @param DynamicContentAwareInterface $instance class instance supporting dynamic contents.
     */
    public function pushDynamicContent(DynamicContentAwareInterface $instance)
    {
        $this->_cacheStack[] = $instance;
    }

    /**
     * Removes a last class instance supporting dynamic contents from a list of currently active
     * dynamic content class instances.
     */
    public function popDynamicContent()
    {
        array_pop($this->_cacheStack);
    }

    /**
     * Begins recording a block.
     *
     * This method is a shortcut to beginning [[Block]].
     *
     * @param string $id the block ID.
     * @param bool $renderInPlace whether to render the block content in place.
     *                              Defaults to false, meaning the captured block will not be displayed.
     *
     * @return Block the Block widget instance
     */
    public function beginBlock($id, $renderInPlace = false)
    {
        return Block::begin([
            'id' => $id,
            'renderInPlace' => $renderInPlace,
            'view' => $this,
        ]);
    }

    /**
     * Ends recording a block.
     */
    public function endBlock()
    {
        Block::end();
    }

    /**
     * Begins the rendering of content that is to be decorated by the specified view.
     *
     * This method can be used to implement nested layout. For example, a layout can be embedded
     * in another layout file specified as '@app/views/layouts/base.php' like the following:
     *
     * ```php
     * <?php $this->beginContent('@app/views/layouts/base.php'); ?>
     * //...layout content here...
     * <?php $this->endContent(); ?>
     * ```
     *
     * @param string $viewFile the view file that will be used to decorate the content enclosed by this widget.
     *                         This can be specified as either the view file path or [path alias](guide:concept-aliases).
     * @param array $params the variables (name => value) to be extracted and made available in the decorative view.
     *
     * @return ContentDecorator the ContentDecorator widget instance
     *
     * @see ContentDecorator
     */
    public function beginContent($viewFile, $params = [])
    {
        return ContentDecorator::begin([
            'viewFile' => $viewFile,
            'params' => $params,
            'view' => $this,
        ]);
    }

    /**
     * Ends the rendering of content.
     */
    public function endContent()
    {
        ContentDecorator::end();
    }

    /**
     * Begins fragment caching.
     *
     * This method will display cached content if it is available.
     * If not, it will start caching and would expect an [[endCache()]]
     * call to end the cache and save the content into cache.
     * A typical usage of fragment caching is as follows,
     *
     * ```php
     * if ($this->beginCache($id)) {
     *     // ...generate content here
     *     $this->endCache();
     * }
     * ```
     *
     * @param string $id a unique ID identifying the fragment to be cached.
     * @param array $properties initial property values for [[FragmentCache]]
     *
     * @return bool whether you should generate the content for caching.
     *              False if the cached version is available.
     */
    public function beginCache($id, $properties = [])
    {
        $properties['id'] = $id;
        $properties['view'] = $this;
        /* @var $cache FragmentCache */
        $cache = FragmentCache::begin($properties);
        if ($cache->getCachedContent() !== false) {
            $this->endCache();

            return false;
        }

        return true;
    }

    /**
     * Ends fragment caching.
     */
    public function endCache()
    {
        FragmentCache::end();
    }

    /**
     * Marks the beginning of a page.
     */
    public function beginPage()
    {
        ob_start();
        ob_implicit_flush(false);

        $this->eventDispatcher->dispatch(new PageBegin($this->viewFile));
    }

    /**
     * Marks the ending of a page.
     */
    public function endPage()
    {
        $this->eventDispatcher->dispatch(new PageEnd($this->viewFile));
        ob_end_flush();
    }
}
