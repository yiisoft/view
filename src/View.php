<?php

declare(strict_types=1);

namespace Yiisoft\View;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\I18n\Locale;
use Yiisoft\View\Event\AfterRender;
use Yiisoft\View\Event\BeforeRender;
use Yiisoft\View\Event\PageBegin;
use Yiisoft\View\Event\PageEnd;
use Yiisoft\View\Exception\ViewNotFoundException;

/**
 * View represents a view object in the MVC pattern.
 *
 * View provides a set of methods (e.g. {@see View::render()}) for rendering purpose.
 *
 * For more details and usage information on View, see the [guide article on views](guide:structure-views).
 */
class View implements DynamicContentAwareInterface
{
    /**
     * @var string view path
     */
    private string $basePath;

    /**
     * @var array a list of named output blocks. The keys are the block names and the values are the corresponding block
     * content. You can call {@see beginBlock()} and {@see endBlock()} to capture small fragments of a view.
     * They can be later accessed somewhere else through this property.
     */
    private array $blocks;

    /**
     * @var ViewContextInterface|null the context under which the {@see renderFile()} method is being invoked.
     */
    private ?ViewContextInterface $context = null;

    /**
     * @var string the default view file extension. This will be appended to view file names if they don't have file
     * extensions.
     */
    private string $defaultExtension = 'php';

    /**
     * @var array custom parameters that are shared among view templates.
     */
    private array $defaultParameters = [];

    /**
     * @var EventDispatcherInterface
     */
    protected EventDispatcherInterface $eventDispatcher;

    /**
     * @var array a list of available renderers indexed by their corresponding supported file extensions. Each renderer
     * may be a view renderer object or the configuration for creating the renderer object. For example, the
     * following configuration enables the Twig view renderer:
     *
     * ```php
     * [
     *     'twig' => ['__class' => \Yiisoft\Yii\Twig\ViewRenderer::class],
     * ]
     * ```
     *
     * If no renderer is available for the given view file, the view file will be treated as a normal PHP and rendered
     * via PhpTemplateRenderer.
     */
    protected array $renderers = [];

    /**
     * @var Theme the theme object.
     */
    protected Theme $theme;

    /**
     * @var DynamicContentAwareInterface[] a list of currently active dynamic content class instances.
     */
    private array $cacheStack = [];

    /**
     * @var array a list of placeholders for embedding dynamic contents.
     */
    private array $dynamicPlaceholders = [];

    /**
     * @var string
     */
    private string $language = 'en';

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var string
     */
    private string $sourceLanguage = 'en';

    /**
     * @var Locale|null source locale used to find localized view file.
     */
    private ?Locale $sourceLocale = null;

    private string $placeholderSignature;

    /**
     * @var array the view files currently being rendered. There may be multiple view files being
     * rendered at a moment because one view may be rendered within another.
     */
    private array $viewFiles = [];

    public function __construct(string $basePath, Theme $theme, EventDispatcherInterface $eventDispatcher, LoggerInterface $logger)
    {
        $this->basePath = $basePath;
        $this->theme = $theme;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
        $this->setPlaceholderSalt(__DIR__);
    }

    public function setPlaceholderSalt(string $salt): void
    {
        $this->placeholderSignature = dechex(crc32($salt));
    }

    public function getPlaceholderSignature(): string
    {
        return $this->placeholderSignature;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function setRenderers(array $renderers): void
    {
        $this->renderers = $renderers;
    }

    public function setSourceLanguage(string $language): void
    {
        $this->sourceLanguage = $language;
    }

    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }

    public function setContext(ViewContextInterface $context): void
    {
        $this->context = $context;
    }

    public function getDefaultExtension(): string
    {
        return $this->defaultExtension;
    }

    public function setDefaultExtension(string $defaultExtension): void
    {
        $this->defaultExtension = $defaultExtension;
    }

    public function getDefaultParameters(): array
    {
        return $this->defaultParameters;
    }

    public function setDefaultParameters(array $defaultParameters): void
    {
        $this->defaultParameters = $defaultParameters;
    }

    /**
     * {@see blocks}
     *
     * @param string $id
     * @param string $value
     */
    public function setBlock(string $id, string $value): void
    {
        $this->blocks[$id] = $value;
    }

    /**
     * {@see blocks}
     *
     * @param string $id
     */
    public function removeBlock(string $id): void
    {
        unset($this->blocks[$id]);
    }

    /**
     * {@see blocks}
     *
     * @param string $id
     *
     * @return string
     */
    public function getBlock(string $id): string
    {
        if (isset($this->blocks[$id])) {
            return $this->blocks[$id];
        }

        throw new \InvalidArgumentException('Block: "' . $id . '" not found.');
    }

    /**
     * {@see blocks}
     *
     * @param string $id
     *
     * @return bool
     */
    public function hasBlock(string $id): bool
    {
        return isset($this->blocks[$id]);
    }

    /**
     * Renders a view.
     *
     * The view to be rendered can be specified in one of the following formats:
     *
     * - [path alias](guide:concept-aliases) (e.g. "@app/views/site/index");
     * - absolute path within application (e.g. "//site/index"): the view name starts with double slashes. The actual
     *   view file will be looked for under the [[Application::viewPath|view path]] of the application.
     * - absolute path within current module (e.g. "/site/index"): the view name starts with a single slash. The actual
     *   view file will be looked for under the [[Module::viewPath|view path]] of the [[Controller::module|current module]].
     * - relative view (e.g. "index"): the view name does not start with `@` or `/`. The corresponding view file will be
     *   looked for under the {@see ViewContextInterface::getViewPath()} of the view `$context`.
     *   If `$context` is not given, it will be looked for under the directory containing the view currently
     *   being rendered (i.e., this happens when rendering a view within another view).
     *
     * @param string $view the view name.
     * @param array $parameters the parameters (name-value pairs) that will be extracted and made available in the view
     * file.
     * @param ViewContextInterface|null $context the context to be assigned to the view and can later be accessed via
     * {@see context} in the view. If the context implements {@see ViewContextInterface}, it may also be used to locate
     * the view file corresponding to a relative view name.
     *
     * @throws \RuntimeException if the view cannot be resolved.
     * @throws ViewNotFoundException if the view file does not exist.
     * @throws \Throwable
     *
     * {@see renderFile()}
     *
     * @return string the rendering result
     */
    public function render(string $view, array $parameters = [], ?ViewContextInterface $context = null): string
    {
        $viewFile = $this->findTemplateFile($view, $context);

        return $this->renderFile($viewFile, $parameters, $context);
    }

    /**
     * Finds the view file based on the given view name.
     *
     * @param string $view the view name or the [path alias](guide:concept-aliases) of the view file. Please refer to
     * {@see render()} on how to specify this parameter.
     * @param ViewContextInterface|null $context the context to be assigned to the view and can later be accessed via
     * {@see context} in the view. If the context implements {@see ViewContextInterface}, it may also be used to locate the
     * view file corresponding to a relative view name.
     *
     * @throws \RuntimeException if a relative view name is given while there is no active context to determine the
     * corresponding view file.
     *
     * @return string the view file path. Note that the file may not exist.
     */
    protected function findTemplateFile(string $view, ?ViewContextInterface $context = null): string
    {
        if (strncmp($view, '//', 2) === 0) {
            // path relative to basePath e.g. "//layouts/main"
            $file = $this->basePath . '/' . ltrim($view, '/');
        } elseif ($context instanceof ViewContextInterface) {
            // path provided by context
            $file = $context->getViewPath() . '/' . $view;
        } elseif (($currentViewFile = $this->getRequestedViewFile()) !== false) {
            // path relative to currently rendered view
            $file = dirname($currentViewFile) . '/' . $view;
        } else {
            throw new \RuntimeException("Unable to resolve view file for view '$view': no active view context.");
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
     * If {@see theme} is enabled (not null), it will try to render the themed version of the view file as long as it
     * is available.
     *
     * If {@see renderers} is enabled (not null), the method will use it to render the view file. Otherwise,
     * it will simply include the view file as a normal PHP file, capture its output and
     * return it as a string.
     *
     * @param string $viewFile the view file. This can be either an absolute file path or an alias of it.
     * @param array $parameters the parameters (name-value pairs) that will be extracted and made available in the view
     * file.
     * @param ViewContextInterface|null $context the context that the view should use for rendering the view. If null,
     * existing {@see context} will be used.
     *
     * @throws \Throwable
     * @throws ViewNotFoundException if the view file does not exist
     *
     * @return string the rendering result
     */
    public function renderFile(string $viewFile, array $parameters = [], ?ViewContextInterface $context = null): string
    {
        $parameters = array_merge($this->defaultParameters, $parameters);

        // TODO: these two match now
        $requestedFile = $viewFile;

        if (!empty($this->theme)) {
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
        $this->viewFiles[] = [
            'resolved' => $viewFile,
            'requested' => $requestedFile,
        ];

        if ($this->beforeRender($viewFile, $parameters)) {
            $this->logger->debug("Rendering view file: $viewFile");
            $ext = pathinfo($viewFile, PATHINFO_EXTENSION);
            $renderer = $this->renderers[$ext] ?? new PhpTemplateRenderer();
            $output = $renderer->render($this, $viewFile, $parameters);

            $output = $this->afterRender($viewFile, $parameters, $output);
        }

        array_pop($this->viewFiles);
        $this->context = $oldContext;

        return $output;
    }

    /**
     * Returns the localized version of a specified file.
     *
     * The searching is based on the specified language code. In particular, a file with the same name will be looked
     * for under the subdirectory whose name is the same as the language code. For example, given the file
     * "path/to/view.php" and language code "zh-CN", the localized file will be looked for as path/to/zh-CN/view.php".
     * If the file is not found, it will try a fallback with just a language code that is "zh" i.e. "path/to/zh/view.php".
     * If it is not found as well the original file will be returned.
     *
     * If the target and the source language codes are the same, the original file will be returned.
     *
     * @param string $file the original file
     * @param string|null $language the target language that the file should be localized to.
     * @param string|null $sourceLanguage the language that the original file is in.
     *
     * @return string the matching localized file, or the original file if the localized version is not found.
     * If the target and the source language codes are the same, the original file will be returned.
     */
    public function localize(string $file, ?string $language = null, ?string $sourceLanguage = null): string
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
     * @return bool|string the view file currently being rendered. False if no view file is being rendered.
     */
    public function getViewFile()
    {
        return empty($this->viewFiles) ? false : end($this->viewFiles)['resolved'];
    }

    /**
     * @return bool|string the requested view currently being rendered. False if no view file is being rendered.
     */
    protected function getRequestedViewFile()
    {
        return empty($this->viewFiles) ? false : end($this->viewFiles)['requested'];
    }

    /**
     * This method is invoked right before {@see renderFile()} renders a view file.
     *
     * The default implementation will trigger the {@see BeforeRender()} event. If you override this method, make sure
     * you call the parent implementation first.
     *
     * @param string $viewFile the view file to be rendered.
     * @param array $parameters the parameter array passed to the {@see render()} method.
     *
     * @return bool whether to continue rendering the view file.
     */
    public function beforeRender(string $viewFile, array $parameters): bool
    {
        $event = new BeforeRender($viewFile, $parameters);
        $event = $this->eventDispatcher->dispatch($event);

        return !$event->isPropagationStopped();
    }

    /**
     * This method is invoked right after {@see renderFile()} renders a view file.
     *
     * The default implementation will trigger the {@see AfterRender} event. If you override this method, make sure you
     * call the parent implementation first.
     *
     * @param string $viewFile the view file being rendered.
     * @param array $parameters the parameter array passed to the {@see render()} method.
     * @param string $output the rendering result of the view file.
    * @return string Updated output. It will be passed to {@see renderFile()} and returned.
     */
    public function afterRender(string $viewFile, array $parameters, string $output): string
    {
        $event = new AfterRender($viewFile, $parameters, $output);
        $event = $this->eventDispatcher->dispatch($event);

        return $event->getResult();
    }

    /**
     * Renders dynamic content returned by the given PHP statements.
     *
     * This method is mainly used together with content caching (fragment caching and page caching) when some portions
     * of the content (called *dynamic content*) should not be cached. The dynamic content must be returned by some PHP
     * statements. You can optionally pass additional parameters that will be available as variables in the PHP
     * statement:.
     *
     * ```php
     * <?= $this->renderDynamic('return foo($myVar);', [
     *     'myVar' => $model->getMyComplexVar(),
     * ]) ?>
     * ```
     *
     * @param string $statements the PHP statements for generating the dynamic content.
     * @param array $parameters the parameters (name-value pairs) that will be extracted and made
     * available in the $statement context. The parameters will be stored in the cache and be reused
     * each time $statement is executed. You should make sure, that these are safely serializable.
     *
     * @return string the placeholder of the dynamic content, or the dynamic content if there is no active content
     *                cache currently.
     */
    public function renderDynamic(string $statements, array $parameters = []): string
    {
        if (!empty($parameters)) {
            $statements = 'extract(unserialize(\'' . str_replace(['\\', '\''], ['\\\\', '\\\''], serialize($parameters)) . '\'));' . $statements;
        }

        if (!empty($this->cacheStack)) {
            $n = count($this->dynamicPlaceholders);
            $placeholder = "<![CDATA[YII-DYNAMIC-$n-{$this->getPlaceholderSignature()}]]>";
            $this->addDynamicPlaceholder($placeholder, $statements);

            return $placeholder;
        }

        return $this->evaluateDynamicContent($statements);
    }

    /**
     * Get source locale.
     *
     * @return Locale
     */
    public function getSourceLocale(): Locale
    {
        if ($this->sourceLocale === null) {
            $this->sourceLocale = new Locale('en-US');
        }

        return $this->sourceLocale;
    }

    /**
     * Set source locale.
     *
     * @param string $locale
     *
     * @return self
     */
    public function setSourceLocale(string $locale): self
    {
        $this->sourceLocale = new Locale($locale);

        return $this;
    }

    public function getDynamicPlaceholders(): array
    {
        return $this->dynamicPlaceholders;
    }

    public function setDynamicPlaceholders(array $placeholders): void
    {
        $this->dynamicPlaceholders = $placeholders;
    }

    public function addDynamicPlaceholder(string $name, string $statements): void
    {
        foreach ($this->cacheStack as $cache) {
            $cache->addDynamicPlaceholder($name, $statements);
        }

        $this->dynamicPlaceholders[$name] = $statements;
    }

    /**
     * Evaluates the given PHP statements.
     *
     * This method is mainly used internally to implement dynamic content feature.
     *
     * @param string $statements the PHP statements to be evaluated.
     *
     * @return mixed the return value of the PHP statements.
     */
    public function evaluateDynamicContent(string $statements)
    {
        return eval($statements);
    }

    /**
     * Returns a list of currently active dynamic content class instances.
     *
     * @return DynamicContentAwareInterface[] class instances supporting dynamic contents.
     */
    public function getDynamicContents(): array
    {
        return $this->cacheStack;
    }

    /**
     * Adds a class instance supporting dynamic contents to the end of a list of currently active dynamic content class
     * instances.
     *
     * @param DynamicContentAwareInterface $instance class instance supporting dynamic contents.
     */
    public function pushDynamicContent(DynamicContentAwareInterface $instance): void
    {
        $this->cacheStack[] = $instance;
    }

    /**
     * Removes a last class instance supporting dynamic contents from a list of currently active dynamic content class
     * instances.
     */
    public function popDynamicContent(): void
    {
        array_pop($this->cacheStack);
    }

    /**
     * Marks the beginning of a page.
     */
    public function beginPage(): void
    {
        ob_start();
        PHP_VERSION_ID >= 80000 ? ob_implicit_flush(false) : ob_implicit_flush(0);

        $this->eventDispatcher->dispatch(new PageBegin($this->getViewFile()));
    }

    /**
     * Marks the ending of a page.
     */
    public function endPage(): void
    {
        $this->eventDispatcher->dispatch(new PageEnd($this->getViewFile()));
        ob_end_flush();
    }
}
