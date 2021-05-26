<?php

declare(strict_types=1);

namespace Yiisoft\View;

use InvalidArgumentException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\StoppableEventInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;
use Yiisoft\View\Event\AfterRenderEventInterface;
use Yiisoft\View\Exception\ViewNotFoundException;

use function count;
use function dirname;

/**
 * @internal Base class for {@see View} and {@see WebView}.
 */
abstract class BaseView implements DynamicContentAwareInterface
{
    protected EventDispatcherInterface $eventDispatcher;

    private LoggerInterface $logger;

    /**
     * @var string Base view path.
     */
    private string $basePath;

    /**
     * @var Theme|null The theme object.
     */
    private ?Theme $theme = null;

    /**
     * @var ViewContextInterface|null The context under which the {@see renderFile()} method is being invoked.
     */
    private ?ViewContextInterface $context = null;

    private string $placeholderSignature;

    /**
     * @var array A list of available renderers indexed by their corresponding supported file extensions. Each renderer
     * may be a view renderer object or the configuration for creating the renderer object. For example, the
     * following configuration enables the Twig view renderer:
     *
     * ```php
     * [
     *     'twig' => ['class' => \Yiisoft\Yii\Twig\ViewRenderer::class],
     * ]
     * ```
     *
     * If no renderer is available for the given view file, the view file will be treated as a normal PHP and rendered
     * via PhpTemplateRenderer.
     */
    private array $renderers = [];

    private string $language = 'en';
    private string $sourceLanguage = 'en';

    /**
     * @var string The default view file extension. This will be appended to view file names if they don't have file
     * extensions.
     */
    private string $defaultExtension = 'php';

    /**
     * @var array<string, mixed> Parameters that are common for all view templates.
     */
    private array $commonParameters = [];

    /**
     * @var array<string, string> Named content blocks that are common for all view templates.
     */
    private array $blocks = [];

    /**
     * @var array The view files currently being rendered. There may be multiple view files being
     * rendered at a moment because one view may be rendered within another.
     */
    private array $viewFiles = [];

    /**
     * @var DynamicContentAwareInterface[] A list of currently active dynamic content class instances.
     */
    private array $cacheStack = [];

    /**
     * @var array A list of placeholders for embedding dynamic contents.
     */
    private array $dynamicPlaceholders = [];

    public function __construct(string $basePath, EventDispatcherInterface $eventDispatcher, LoggerInterface $logger)
    {
        $this->basePath = $basePath;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
        $this->setPlaceholderSalt(__DIR__);
    }

    /**
     * @return static
     */
    public function withTheme(Theme $theme): self
    {
        $new = clone $this;
        $new->theme = $theme;
        return $new;
    }

    public function withRenderers(array $renderers): self
    {
        $new = clone $this;
        $new->renderers = $renderers;
        return $new;
    }

    public function withLanguage(string $language): self
    {
        $new = clone $this;
        $new->language = $language;
        return $new;
    }

    public function withSourceLanguage(string $language): self
    {
        $new = clone $this;
        $new->sourceLanguage = $language;
        return $new;
    }

    public function withContext(ViewContextInterface $context): self
    {
        $new = clone $this;
        $new->context = $context;
        return $new;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    final public function getPlaceholderSignature(): string
    {
        return $this->placeholderSignature;
    }

    final public function setPlaceholderSalt(string $salt): void
    {
        $this->placeholderSignature = dechex(crc32($salt));
    }

    public function getDefaultExtension(): string
    {
        return $this->defaultExtension;
    }

    public function withDefaultExtension(string $defaultExtension): self
    {
        $new = clone $this;
        $new->defaultExtension = $defaultExtension;
        return $new;
    }

    /**
     * Sets a common parameter that is accessible in all view templates.
     *
     * @param string $id The unique identifier of the parameter.
     * @param mixed $value The value of the parameter.
     */
    public function setCommonParameter(string $id, $value): void
    {
        $this->commonParameters[$id] = $value;
    }

    /**
     * Removes a common parameter.
     *
     * @param string $id The unique identifier of the parameter.
     */
    public function removeCommonParameter(string $id): void
    {
        unset($this->commonParameters[$id]);
    }

    /**
     * Gets a common parameter value by ID.
     *
     * @param string $id The unique identifier of the parameter.
     *
     * @return mixed The value of the parameter.
     */
    public function getCommonParameter(string $id)
    {
        if (isset($this->commonParameters[$id])) {
            return $this->commonParameters[$id];
        }

        throw new InvalidArgumentException('Common parameter: "' . $id . '" not found.');
    }

    /**
     * Checks the existence of a common parameter by ID.
     *
     * @param string $id The unique identifier of the parameter.
     *
     * @return bool Whether a custom parameter that is common for all view templates exists.
     */
    public function hasCommonParameter(string $id): bool
    {
        return isset($this->commonParameters[$id]);
    }

    /**
     * Sets a content block.
     *
     * @param string $id The unique identifier of the block.
     * @param mixed $content The content of the block.
     */
    public function setBlock(string $id, string $content): void
    {
        $this->blocks[$id] = $content;
    }

    /**
     * Removes a content block.
     *
     * @param string $id The unique identifier of the block.
     */
    public function removeBlock(string $id): void
    {
        unset($this->blocks[$id]);
    }

    /**
     * Gets content of the block by ID.
     *
     * @param string $id The unique identifier of the block.
     *
     * @return string The content of the block.
     */
    public function getBlock(string $id): string
    {
        if (isset($this->blocks[$id])) {
            return $this->blocks[$id];
        }

        throw new InvalidArgumentException('Block: "' . $id . '" not found.');
    }

    /**
     * Checks the existence of a content block by ID.
     *
     * @param string $id The unique identifier of the block.
     *
     * @return bool Whether a content block exists.
     */
    public function hasBlock(string $id): bool
    {
        return isset($this->blocks[$id]);
    }

    /**
     * @return string|null The view file currently being rendered. `null` if no view file is being rendered.
     */
    public function getViewFile(): ?string
    {
        return empty($this->viewFiles) ? null : end($this->viewFiles)['resolved'];
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
     *   view file will be looked for under the [[Module::viewPath|view path]]
     *   of the [[Controller::module|current module]].
     * - relative view (e.g. "index"): the view name does not start with `@` or `/`. The corresponding view file will be
     *   looked for under the {@see ViewContextInterface::getViewPath()} of the {@see View::$context}.
     *   If {@see View::$context} is not set, it will be looked for under the directory containing the view currently
     *   being rendered (i.e., this happens when rendering a view within another view).
     *
     * @param string $view the view name.
     * @param array $parameters the parameters (name-value pairs) that will be extracted and made available in the view
     * file.
     *
     * @throws RuntimeException If the view cannot be resolved.
     * @throws ViewNotFoundException If the view file does not exist.
     * @throws Throwable
     *
     * {@see renderFile()}
     *
     * @return string The rendering result.
     */
    public function render(string $view, array $parameters = []): string
    {
        $viewFile = $this->findTemplateFile($view);

        return $this->renderFile($viewFile, $parameters);
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
     * @param string $viewFile The view file. This can be either an absolute file path or an alias of it.
     * @param array $parameters The parameters (name-value pairs) that will be extracted and made available in the view
     * file.
     *
     * @throws Throwable
     * @throws ViewNotFoundException If the view file does not exist
     *
     * @return string The rendering result.
     */
    public function renderFile(string $viewFile, array $parameters = []): string
    {
        $parameters = array_merge($this->commonParameters, $parameters);

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

        return $output;
    }

    /**
     * Returns the localized version of a specified file.
     *
     * The searching is based on the specified language code. In particular, a file with the same name will be looked
     * for under the subdirectory whose name is the same as the language code. For example, given the file
     * "path/to/view.php" and language code "zh-CN", the localized file will be looked for as path/to/zh-CN/view.php".
     * If the file is not found, it will try a fallback with just a language code that is "zh"
     * i.e. "path/to/zh/view.php".
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
            $statements = 'extract(unserialize(\'' .
                str_replace(['\\', '\''], ['\\\\', '\\\''], serialize($parameters)) .
                '\'));' . $statements;
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
     * Evaluates the given PHP statements.
     *
     * This method is mainly used internally to implement dynamic content feature.
     *
     * @param string $statements The PHP statements to be evaluated.
     *
     * @return mixed The return value of the PHP statements.
     */
    public function evaluateDynamicContent(string $statements)
    {
        return eval($statements);
    }

    /**
     * Returns a list of currently active dynamic content class instances.
     *
     * @return DynamicContentAwareInterface[] Class instances supporting dynamic contents.
     */
    public function getDynamicContents(): array
    {
        return $this->cacheStack;
    }

    /**
     * Adds a class instance supporting dynamic contents to the end of a list of currently active dynamic content class
     * instances.
     *
     * @param DynamicContentAwareInterface $instance Class instance supporting dynamic contents.
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
        $event = $this->createBeforeRenderEvent($viewFile, $parameters);
        $event = $this->eventDispatcher->dispatch($event);

        return !$event->isPropagationStopped();
    }

    abstract protected function createBeforeRenderEvent(string $viewFile, array $parameters): StoppableEventInterface;

    /**
     * This method is invoked right after {@see renderFile()} renders a view file.
     *
     * The default implementation will trigger the {@see AfterRender} event. If you override this method, make sure you
     * call the parent implementation first.
     *
     * @param string $viewFile the view file being rendered.
     * @param array $parameters the parameter array passed to the {@see render()} method.
     * @param string $output the rendering result of the view file.
     *
     * @return string Updated output. It will be passed to {@see renderFile()} and returned.
     */
    public function afterRender(string $viewFile, array $parameters, string $output): string
    {
        $event = $this->createAfterRenderEvent($viewFile, $parameters, $output);

        /** @var AfterRenderEventInterface $event */
        $event = $this->eventDispatcher->dispatch($event);

        return $event->getResult();
    }

    abstract protected function createAfterRenderEvent(
        string $viewFile,
        array $parameters,
        string $result
    ): AfterRenderEventInterface;

    /**
     * Finds the view file based on the given view name.
     *
     * @param string $view The view name or the [path alias](guide:concept-aliases) of the view file. Please refer to
     * {@see render()} on how to specify this parameter.
     *
     * @throws RuntimeException If a relative view name is given while there is no active context to determine the
     * corresponding view file.
     *
     * @return string The view file path. Note that the file may not exist.
     */
    protected function findTemplateFile(string $view): string
    {
        if (strncmp($view, '//', 2) === 0) {
            // path relative to basePath e.g. "//layouts/main"
            $file = $this->basePath . '/' . ltrim($view, '/');
        } elseif (($currentViewFile = $this->getRequestedViewFile()) !== null) {
            // path relative to currently rendered view
            $file = dirname($currentViewFile) . '/' . $view;
        } elseif ($this->context instanceof ViewContextInterface) {
            // path provided by context
            $file = $this->context->getViewPath() . '/' . $view;
        } else {
            throw new RuntimeException("Unable to resolve view file for view '$view': no active view context.");
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
     * @return string|null The requested view currently being rendered. `null` if no view file is being rendered.
     */
    private function getRequestedViewFile(): ?string
    {
        return empty($this->viewFiles) ? null : end($this->viewFiles)['requested'];
    }
}
