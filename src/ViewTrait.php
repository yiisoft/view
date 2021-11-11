<?php

declare(strict_types=1);

namespace Yiisoft\View;

use InvalidArgumentException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\StoppableEventInterface;
use RuntimeException;
use Throwable;
use Yiisoft\View\Event\AfterRenderEventInterface;
use Yiisoft\View\Exception\ViewNotFoundException;

use function array_key_exists;
use function array_merge;
use function array_pop;
use function basename;
use function crc32;
use function dechex;
use function dirname;
use function end;
use function func_get_args;
use function is_array;
use function is_file;
use function pathinfo;
use function substr;

/**
 * ViewTrait could be used as a base implementation of {@see ViewInterface}.
 *
 * @internal
 */
trait ViewTrait
{
    private EventDispatcherInterface $eventDispatcher;

    private string $basePath;
    private ?Theme $theme = null;
    private ?ViewContextInterface $context = null;
    private string $placeholderSignature;
    private string $language = 'en';
    private string $sourceLanguage = 'en';
    private string $defaultExtension = 'php';

    /**
     * @var array A list of available renderers indexed by their corresponding
     * supported file extensions.
     * @psalm-var array<string, TemplateRendererInterface>
     */
    private array $renderers = [];

    /**
     * @var array Parameters that are common for all view templates.
     * @psalm-var array<string, mixed>
     */
    private array $parameters = [];

    /**
     * @var array Named content blocks that are common for all view templates.
     * @psalm-var array<string, string>
     */
    private array $blocks = [];

    /**
     * @var array The view files currently being rendered. There may be multiple view files being
     * rendered at a moment because one view may be rendered within another.
     *
     * @psalm-var array<array-key, array<string, string>>
     */
    private array $viewFiles = [];

    /**
     * @param string $basePath The full path to the base directory of views.
     * @param EventDispatcherInterface $eventDispatcher The event dispatcher instance.
     */
    public function __construct(string $basePath, EventDispatcherInterface $eventDispatcher)
    {
        $this->basePath = $basePath;
        $this->eventDispatcher = $eventDispatcher;
        $this->setPlaceholderSalt(__DIR__);
    }

    /**
     * Returns a new instance with the specified theme instance.
     *
     * @param Theme $theme The theme instance.
     *
     * @return static
     */
    public function withTheme(Theme $theme): self
    {
        $new = clone $this;
        $new->theme = $theme;
        return $new;
    }

    /**
     * Returns a new instance with the specified renderers.
     *
     * @param array $renderers A list of available renderers indexed by their
     * corresponding supported file extensions.
     *
     * ```php
     * $view = $view->withRenderers(['twig' => new \Yiisoft\Yii\Twig\ViewRenderer($environment)]);
     * ```
     *
     * If no renderer is available for the given view file, the view file will be treated as a normal PHP
     * and rendered via {@see PhpTemplateRenderer}.
     *
     * @psalm-param array<string, TemplateRendererInterface> $renderers
     *
     * @return static
     */
    public function withRenderers(array $renderers): self
    {
        $new = clone $this;
        $new->renderers = $renderers;
        return $new;
    }

    /**
     * Returns a new instance with the specified language.
     *
     * @param string $language The language.
     *
     * @return static
     */
    public function withLanguage(string $language): self
    {
        $new = clone $this;
        $new->language = $language;
        return $new;
    }

    /**
     * Returns a new instance with the specified source language.
     *
     * @param string $language The source language.
     *
     * @return static
     */
    public function withSourceLanguage(string $language): self
    {
        $new = clone $this;
        $new->sourceLanguage = $language;
        return $new;
    }

    /**
     * Returns a new instance with the specified default view file extension.
     *
     * @param string $defaultExtension The default view file extension. Default is "php".
     * This will be appended to view file names if they don't have file extensions.
     *
     * @return static
     */
    public function withDefaultExtension(string $defaultExtension): self
    {
        $new = clone $this;
        $new->defaultExtension = $defaultExtension;
        return $new;
    }

    /**
     * Returns a new instance with the specified view context instance.
     *
     * @param ViewContextInterface $context The context under which the {@see renderFile()} method is being invoked.
     *
     * @return static
     */
    public function withContext(ViewContextInterface $context): self
    {
        $new = clone $this;
        $new->context = $context;
        $new->viewFiles = [];
        return $new;
    }

    /**
     * Returns a new instance with the specified view context path.
     *
     * @param string $path The context path under which the {@see renderFile()} method is being invoked.
     *
     * @return static
     */
    public function withContextPath(string $path): self
    {
        return $this->withContext(new ViewContext($path));
    }

    /**
     * Gets the base path to the view directory.
     *
     * @return string The base view path.
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Gets the default view file extension.
     *
     * @return string The default view file extension.
     */
    public function getDefaultExtension(): string
    {
        return $this->defaultExtension;
    }

    /**
     * Gets the theme instance, or null if no theme has been set.
     *
     * @return Theme The theme instance, or null if no theme has been set.
     */
    public function getTheme(): ?Theme
    {
        return $this->theme;
    }

    /**
     * Sets a common parameters that is accessible in all view templates.
     *
     * @param array $parameters Parameters that are common for all view templates.
     *
     * @psalm-param array<string, mixed> $parameters
     *
     * @return static
     *
     * @see setParameter()
     */
    public function setParameters(array $parameters): self
    {
        /** @var mixed $value */
        foreach ($parameters as $id => $value) {
            $this->setParameter($id, $value);
        }
        return $this;
    }

    /**
     * Sets a common parameter that is accessible in all view templates.
     *
     * @param string $id The unique identifier of the parameter.
     * @param mixed $value The value of the parameter.
     *
     * @return static
     */
    public function setParameter(string $id, $value): self
    {
        $this->parameters[$id] = $value;
        return $this;
    }

    /**
     * Add values to end of common array parameter. If specified parameter does not exist or him is not array,
     * then parameter will be added as empty array.
     *
     * @param string $id The unique identifier of the parameter.
     * @param mixed ...$value Value(s) for add to end of array parameter.
     *
     * @return static
     */
    public function addToArrayParameter(string $id, ...$value): self
    {
        /** @var mixed $array */
        $array = $this->parameters[$id] ?? [];
        if (!is_array($array)) {
            $array = [];
        }

        $this->setParameter($id, array_merge($array, $value));

        return $this;
    }

    /**
     * Removes a common parameter.
     *
     * @param string $id The unique identifier of the parameter.
     */
    public function removeParameter(string $id): void
    {
        unset($this->parameters[$id]);
    }

    /**
     * Gets a common parameter value by ID.
     *
     * @param string $id The unique identifier of the parameter.
     * @param mixed $default The default value to be returned if the specified parameter does not exist.
     *
     * @throws InvalidArgumentException If specified parameter does not exist and not passed default value.
     *
     * @return mixed The value of the parameter.
     */
    public function getParameter(string $id)
    {
        if (isset($this->parameters[$id])) {
            return $this->parameters[$id];
        }

        $args = func_get_args();
        if (array_key_exists(1, $args)) {
            return $args[1];
        }

        throw new InvalidArgumentException('Parameter "' . $id . '" not found.');
    }

    /**
     * Checks the existence of a common parameter by ID.
     *
     * @param string $id The unique identifier of the parameter.
     *
     * @return bool Whether a custom parameter that is common for all view templates exists.
     */
    public function hasParameter(string $id): bool
    {
        return isset($this->parameters[$id]);
    }

    /**
     * Sets a content block.
     *
     * @param string $id The unique identifier of the block.
     * @param string $content The content of the block.
     *
     * @return static
     */
    public function setBlock(string $id, string $content): self
    {
        $this->blocks[$id] = $content;
        return $this;
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

        throw new InvalidArgumentException('Block "' . $id . '" not found.');
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
     * Gets the view file currently being rendered.
     *
     * @return string|null The view file currently being rendered. `null` if no view file is being rendered.
     */
    public function getViewFile(): ?string
    {
        /** @psalm-suppress InvalidArrayOffset */
        return empty($this->viewFiles) ? null : end($this->viewFiles)['resolved'];
    }

    /**
     * Gets the placeholder signature.
     *
     * @return string The placeholder signature.
     */
    public function getPlaceholderSignature(): string
    {
        return $this->placeholderSignature;
    }

    /**
     * Sets a salt for the placeholder signature {@see getPlaceholderSignature()}.
     *
     * @param string $salt The placeholder salt.
     *
     * @return static
     */
    public function setPlaceholderSalt(string $salt): self
    {
        $this->placeholderSignature = dechex(crc32($salt));
        return $this;
    }

    /**
     * Renders a view.
     *
     * The view to be rendered can be specified in one of the following formats:
     *
     * - The name of the view starting with a slash to join the base path {@see getBasePath()} (e.g. "/site/index").
     * - The name of the view without the starting slash (e.g. "site/index"). The corresponding view file will be
     *   looked for under the {@see ViewContextInterface::getViewPath()} of the context set via {@see withContext()}.
     *   If the context instance was not set {@see withContext()}, it will be looked for under the directory containing
     *   the view currently being rendered (i.e., this happens when rendering a view within another view).
     *
     * @param string $view The view name.
     * @param array $parameters The parameters (name-value pairs) that will be extracted and made available in the view
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
     * If the theme was set {@see withTheme()}, it will try to render the themed version of the view file
     * as long as it is available.
     *
     * If the renderer was set {@see withRenderers()}, the method will use it to render the view file. Otherwise,
     * it will simply include the view file as a normal PHP file, capture its output and return it as a string.
     *
     * @param string $viewFile The full absolute path of the view file.
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
        $parameters = array_merge($this->parameters, $parameters);

        // TODO: these two match now
        $requestedFile = $viewFile;

        if ($this->theme !== null) {
            $viewFile = $this->theme->applyTo($viewFile);
        }

        if (is_file($viewFile)) {
            $viewFile = $this->localize($viewFile);
        } else {
            throw new ViewNotFoundException("The view file \"$viewFile\" does not exist.");
        }

        $output = '';
        $this->viewFiles[] = [
            'resolved' => $viewFile,
            'requested' => $requestedFile,
        ];

        if ($this->beforeRender($viewFile, $parameters)) {
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
     * "path/to/view.php" and language code "zh-CN", the localized file will be looked for as "path/to/zh-CN/view.php".
     * If the file is not found, it will try a fallback with just a language code that is "zh"
     * i.e. "path/to/zh/view.php".
     * If it is not found as well the original file will be returned.
     *
     * If the target and the source language codes are the same, the original file will be returned.
     *
     * @param string $file The original file
     * @param string|null $language The target language that the file should be localized to.
     * @param string|null $sourceLanguage The language that the original file is in.
     *
     * @return string The matching localized file, or the original file if the localized version is not found.
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
     * Creates an event that occurs before rendering.
     *
     * @param string $viewFile The view file to be rendered.
     * @param array $parameters The parameter array passed to the {@see renderFile()} method.
     *
     * @return StoppableEventInterface The stoppable event instance.
     */
    abstract protected function createBeforeRenderEvent(string $viewFile, array $parameters): StoppableEventInterface;

    /**
     * Creates an event that occurs after rendering.
     *
     * @param string $viewFile The view file being rendered.
     * @param array $parameters The parameter array passed to the {@see renderFile()} method.
     * @param string $result The rendering result of the view file.
     *
     * @return AfterRenderEventInterface The event instance.
     */
    abstract protected function createAfterRenderEvent(
        string $viewFile,
        array $parameters,
        string $result
    ): AfterRenderEventInterface;

    /**
     * This method is invoked right before {@see renderFile()} renders a view file.
     *
     * The default implementations will trigger the {@see \Yiisoft\View\Event\View\BeforeRender}
     * or {@see \Yiisoft\View\Event\WebView\BeforeRender} event. If you override this method,
     * make sure you call the parent implementation first.
     *
     * @param string $viewFile The view file to be rendered.
     * @param array $parameters The parameter array passed to the {@see renderFile()} method.
     *
     * @return bool Whether to continue rendering the view file.
     */
    private function beforeRender(string $viewFile, array $parameters): bool
    {
        $event = $this->createBeforeRenderEvent($viewFile, $parameters);
        $event = $this->eventDispatcher->dispatch($event);
        /** @var StoppableEventInterface $event */
        return !$event->isPropagationStopped();
    }

    /**
     * This method is invoked right after {@see renderFile()} renders a view file.
     *
     * The default implementations will trigger the {@see \Yiisoft\View\Event\View\AfterRender}
     * or {@see \Yiisoft\View\Event\WebView\AfterRender} event. If you override this method,
     * make sure you call the parent implementation first.
     *
     * @param string $viewFile The view file being rendered.
     * @param array $parameters The parameter array passed to the {@see renderFile()} method.
     * @param string $result The rendering result of the view file.
     *
     * @return string Updated output. It will be passed to {@see renderFile()} and returned.
     */
    private function afterRender(string $viewFile, array $parameters, string $result): string
    {
        $event = $this->createAfterRenderEvent($viewFile, $parameters, $result);

        /** @var AfterRenderEventInterface $event */
        $event = $this->eventDispatcher->dispatch($event);

        return $event->getResult();
    }

    /**
     * Finds the view file based on the given view name.
     *
     * @param string $view The view name of the view file. Please refer to
     * {@see render()} on how to specify this parameter.
     *
     * @throws RuntimeException If a relative view name is given while there is no active context to determine the
     * corresponding view file.
     *
     * @return string The view file path. Note that the file may not exist.
     */
    private function findTemplateFile(string $view): string
    {
        if ($view !== '' && $view[0] === '/') {
            // path relative to basePath e.g. "/layouts/main"
            $file = $this->basePath . '/' . ltrim($view, '/');
        } elseif (($currentViewFile = $this->getRequestedViewFile()) !== null) {
            // path relative to currently rendered view
            $file = dirname($currentViewFile) . '/' . $view;
        } elseif ($this->context instanceof ViewContextInterface) {
            // path provided by context
            $file = $this->context->getViewPath() . '/' . $view;
        } else {
            throw new RuntimeException("Unable to resolve view file for view \"$view\": no active view context.");
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
        /** @psalm-suppress InvalidArrayOffset */
        return empty($this->viewFiles) ? null : end($this->viewFiles)['requested'];
    }
}
