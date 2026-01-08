<?php

declare(strict_types=1);

namespace Yiisoft\View;

use InvalidArgumentException;
use LogicException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\StoppableEventInterface;
use Throwable;
use Yiisoft\View\Event\AfterRenderEventInterface;
use Yiisoft\View\Exception\ViewNotFoundException;
use Yiisoft\View\State\LocaleState;
use Yiisoft\View\State\ThemeState;

use function array_merge;
use function array_pop;
use function basename;
use function crc32;
use function dechex;
use function dirname;
use function end;
use function is_file;
use function pathinfo;
use function substr;

/**
 * `ViewTrait` could be used as a base implementation of {@see ViewInterface}.
 *
 * @internal
 */
trait ViewTrait
{
    private ?EventDispatcherInterface $eventDispatcher;

    private ?string $basePath = null;
    private ?ViewContextInterface $context = null;
    private string $placeholderSignature;
    private string $sourceLocale = 'en';
    /**
     * @var string[]
     */
    private array $fallbackExtensions = [self::PHP_EXTENSION];

    /**
     * @var array A list of available renderers indexed by their corresponding
     * supported file extensions.
     * @psalm-var array<string, TemplateRendererInterface>
     */
    private array $renderers = [];

    /**
     * @var array The view files currently being rendered. There may be multiple view files being
     * rendered at a moment because one view may be rendered within another.
     *
     * @psalm-var array<array-key, array<string, string>>
     */
    private array $viewFiles = [];

    /**
     * Returns a new instance with specified base path to the view directory.
     *
     * @param string|null $basePath The base path to the view directory.
     */
    public function withBasePath(string|null $basePath): static
    {
        $new = clone $this;
        $new->basePath = $basePath;
        return $new;
    }

    /**
     * Returns a new instance with the specified renderers.
     *
     * @param array $renderers A list of available renderers indexed by their
     * corresponding supported file extensions.
     *
     * ```php
     * $view = $view->withRenderers(['twig' => new \Yiisoft\View\Twig\ViewRenderer($environment)]);
     * ```
     *
     * If no renderer is available for the given view file, the view file will be treated as a normal PHP
     * and rendered via {@see PhpTemplateRenderer}.
     *
     * @psalm-param array<string, TemplateRendererInterface> $renderers
     */
    public function withRenderers(array $renderers): static
    {
        $new = clone $this;
        $new->renderers = $renderers;
        return $new;
    }

    /**
     * Returns a new instance with the specified source locale.
     *
     * @param string $locale The source locale.
     */
    public function withSourceLocale(string $locale): static
    {
        $new = clone $this;
        $new->sourceLocale = $locale;
        return $new;
    }

    /**
     * Returns a new instance with the specified fallback view file extension.
     *
     * @param string $fallbackExtension The fallback view file extension. Default is {@see ViewInterface::PHP_EXTENSION}.
     * This will be appended to view file names if they don't exist.
     */
    public function withFallbackExtension(string $fallbackExtension, string ...$otherFallbacks): static
    {
        $new = clone $this;
        $new->fallbackExtensions = [$fallbackExtension, ...array_values($otherFallbacks)];
        return $new;
    }

    /**
     * Returns a new instance with the specified view context instance.
     *
     * @param ViewContextInterface|null $context The context under which the {@see render()} method is being invoked.
     */
    public function withContext(ViewContextInterface|null $context): static
    {
        $new = clone $this;
        $new->context = $context;
        $new->viewFiles = [];
        return $new;
    }

    /**
     * Returns a new instance with the specified view context path.
     *
     * @param string $path The context path under which the {@see render()} method is being invoked.
     */
    public function withContextPath(string $path): static
    {
        return $this->withContext(new ViewContext($path));
    }

    /**
     * Returns a new instance with specified salt for the placeholder signature {@see getPlaceholderSignature()}.
     *
     * @param string $salt The placeholder salt.
     */
    public function withPlaceholderSalt(string $salt): static
    {
        $new = clone $this;
        $new->setPlaceholderSalt($salt);
        return $new;
    }

    /**
     * Set the specified locale code.
     *
     * @param string $locale The locale code.
     */
    public function setLocale(string $locale): static
    {
        $this->localeState->setLocale($locale);
        return $this;
    }

    /**
     * Set the specified locale code.
     *
     * @param string $locale The locale code.
     */
    public function withLocale(string $locale): static
    {
        $new = clone $this;
        $new->localeState = new LocaleState($locale);

        return $new;
    }

    /**
     * Get the specified locale code.
     *
     * @return string The locale code.
     */
    public function getLocale(): string
    {
        return $this->localeState->getLocale();
    }

    /**
     * Gets the base path to the view directory.
     *
     * @return string The base view path.
     */
    public function getBasePath(): string
    {
        if ($this->basePath === null) {
            throw new LogicException('The base path is not set.');
        }

        return $this->basePath;
    }

    /**
     * Gets the fallback view file extension.
     *
     * @return string[] The fallback view file extension.
     */
    public function getFallbackExtensions(): array
    {
        return $this->fallbackExtensions;
    }

    /**
     * Gets the theme instance, or `null` if no theme has been set.
     *
     * @return Theme|null The theme instance, or `null` if no theme has been set.
     */
    public function getTheme(): ?Theme
    {
        return $this->themeState->getTheme();
    }

    /**
     * Set the specified theme instance.
     *
     * @param Theme|null $theme The theme instance or `null` for reset theme.
     */
    public function setTheme(?Theme $theme): static
    {
        $this->themeState->setTheme($theme);
        return $this;
    }

    public function withTheme(?Theme $theme): static
    {
        $new = clone $this;
        $new->themeState = new ThemeState($theme);

        return $new;
    }

    /**
     * Sets a common parameters that is accessible in all view templates.
     *
     * @param array $parameters Parameters that are common for all view templates.
     *
     * @psalm-param array<string, mixed> $parameters
     *
     * @see setParameter()
     */
    public function setParameters(array $parameters): static
    {
        $this->state->setParameters($parameters);
        return $this;
    }

    /**
     * Sets a common parameter that is accessible in all view templates.
     *
     * @param string $id The unique identifier of the parameter.
     * @param mixed $value The value of the parameter.
     */
    public function setParameter(string $id, mixed $value): static
    {
        $this->state->setParameter($id, $value);
        return $this;
    }

    /**
     * Add values to end of common array parameter. If specified parameter does not exist or him is not array,
     * then parameter will be added as empty array.
     *
     * @param string $id The unique identifier of the parameter.
     * @param mixed ...$value Value(s) for add to end of array parameter.
     *
     * @throws InvalidArgumentException When specified parameter already exists and is not an array.
     */
    public function addToParameter(string $id, mixed ...$value): static
    {
        $this->state->addToParameter($id, ...$value);
        return $this;
    }

    /**
     * Removes a common parameter.
     *
     * @param string $id The unique identifier of the parameter.
     */
    public function removeParameter(string $id): static
    {
        $this->state->removeParameter($id);
        return $this;
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
    public function getParameter(string $id, mixed ...$default): mixed
    {
        return $this->state->getParameter($id, ...$default);
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
        return $this->state->hasParameter($id);
    }

    /**
     * Sets a content block.
     *
     * @param string $id The unique identifier of the block.
     * @param string $content The content of the block.
     */
    public function setBlock(string $id, string $content): static
    {
        $this->state->setBlock($id, $content);
        return $this;
    }

    /**
     * Removes a content block.
     *
     * @param string $id The unique identifier of the block.
     */
    public function removeBlock(string $id): static
    {
        $this->state->removeBlock($id);
        return $this;
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
        return $this->state->getBlock($id);
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
        return $this->state->hasBlock($id);
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
     * Renders a view.
     *
     * The view to be rendered can be specified in one of the following formats:
     *
     * - the absolute path to the view file, e.g. "/path/to/view.php";
     * - the name of the view starting with `//` to join the base path {@see getBasePath()}, e.g. "//site/index";
     * - the name of the view starting with `./` to join the directory containing the view currently being rendered
     *   (i.e., this happens when rendering a view within another view), e.g. "./widget";
     * - the name of the view starting with `../` to join the parent directory containing the view currently being
     *   rendered, e.g. "../_header";
     * - the name of the view without the starting `//`, `./` or `../` (e.g. "site/index"). The corresponding view file
     *   will be looked for under the {@see ViewContextInterface::getViewPath()} of the context set via {@see withContext()}.
     *   If the context instance was not set {@see withContext()}, it will be looked for under the base path.
     *
     * Warning: Using `..` in view path can lead to accessing unsafe data, e.g., `//../../etc/passwd`. Ensure that such
     * cases are handled properly.
     *
     * @param string $view The view name.
     * @param array $parameters The parameters (name-value pairs) that will be extracted and made available in the view
     * file.
     *
     * @throws LogicException If the view cannot be resolved.
     * @throws ViewNotFoundException If the view file does not exist.
     * @throws Throwable
     *
     * @return string The rendering result.
     */
    public function render(string $view, array $parameters = []): string
    {
        $viewFile = $this->findTemplateFile($view);

        $parameters = array_merge($this->state->getParameters(), $parameters);

        // TODO: these two match now
        $requestedFile = $viewFile;

        $theme = $this->getTheme();
        if ($theme !== null) {
            $viewFile = $theme->applyTo($viewFile);
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
     * The searching is based on the specified locale code. In particular, a file with the same name will be looked
     * for under the subdirectory whose name is the same as the locale code. For example, given the file
     * "path/to/view.php" and locale code "zh-CN", the localized file will be looked for as "path/to/zh-CN/view.php".
     * If the file is not found, it will try a fallback with just a locale code that is "zh"
     * i.e. "path/to/zh/view.php".
     * If it is not found as well the original file will be returned.
     *
     * If the target and the source locale codes are the same, the original file will be returned.
     *
     * @param string $file The original file
     * @param string|null $locale The target locale that the file should be localized to.
     * @param string|null $sourceLocale The locale that the original file is in.
     *
     * @return string The matching localized file, or the original file if the localized version is not found.
     * If the target and the source locale codes are the same, the original file will be returned.
     */
    public function localize(string $file, ?string $locale = null, ?string $sourceLocale = null): string
    {
        $locale ??= $this->localeState->getLocale();
        $sourceLocale ??= $this->sourceLocale;

        if ($locale === $sourceLocale) {
            return $file;
        }

        $desiredFile = dirname($file) . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . basename($file);

        if (is_file($desiredFile)) {
            return $desiredFile;
        }

        $locale = substr($locale, 0, 2);

        if ($locale === $sourceLocale) {
            return $file;
        }

        $desiredFile = dirname($file) . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . basename($file);
        return is_file($desiredFile) ? $desiredFile : $file;
    }

    /**
     * Clears the data for working with the event loop.
     */
    public function clear(): void
    {
        $this->viewFiles = [];
        $this->state->clear();
        $this->localeState = new LocaleState();
        $this->themeState = new ThemeState();
    }

    /**
     * Creates an event that occurs before rendering.
     *
     * @param string $viewFile The view file to be rendered.
     * @param array $parameters The parameter array passed to the {@see render()} method.
     *
     * @return StoppableEventInterface The stoppable event instance.
     */
    abstract protected function createBeforeRenderEvent(string $viewFile, array $parameters): StoppableEventInterface;

    /**
     * Creates an event that occurs after rendering.
     *
     * @param string $viewFile The view file being rendered.
     * @param array $parameters The parameter array passed to the {@see render()} method.
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
     * This method is invoked right before {@see render()} renders a view file.
     *
     * The default implementations will trigger the {@see \Yiisoft\View\Event\View\BeforeRender}
     * or {@see \Yiisoft\View\Event\WebView\BeforeRender} event. If you override this method,
     * make sure you call the parent implementation first.
     *
     * @param string $viewFile The view file to be rendered.
     * @param array $parameters The parameter array passed to the {@see render()} method.
     *
     * @return bool Whether to continue rendering the view file.
     */
    private function beforeRender(string $viewFile, array $parameters): bool
    {
        if ($this->eventDispatcher === null) {
            return true;
        }

        $event = $this->createBeforeRenderEvent($viewFile, $parameters);
        $event = $this->eventDispatcher->dispatch($event);
        /** @var StoppableEventInterface $event */
        return !$event->isPropagationStopped();
    }

    /**
     * This method is invoked right after {@see render()} renders a view file.
     *
     * The default implementations will trigger the {@see \Yiisoft\View\Event\View\AfterRender}
     * or {@see \Yiisoft\View\Event\WebView\AfterRender} event. If you override this method,
     * make sure you call the parent implementation first.
     *
     * @param string $viewFile The view file being rendered.
     * @param array $parameters The parameter array passed to the {@see render()} method.
     * @param string $result The rendering result of the view file.
     *
     * @return string Updated output. It will be passed to {@see render()} and returned.
     */
    private function afterRender(string $viewFile, array $parameters, string $result): string
    {
        if ($this->eventDispatcher === null) {
            return $result;
        }

        $event = $this->createAfterRenderEvent($viewFile, $parameters, $result);

        /** @var AfterRenderEventInterface $event */
        $event = $this->eventDispatcher->dispatch($event);

        return $event->getResult();
    }

    private function setPlaceholderSalt(string $salt): void
    {
        $this->placeholderSignature = dechex(crc32($salt));
    }

    /**
     * Finds the view file based on the given view name.
     *
     * @param string $view The view name of the view file. Please refer to
     * {@see render()} on how to specify this parameter.
     *
     * @throws LogicException If a relative view name is given while there is no currently rendered view.
     *
     * @return string The view file path. Note that the file may not exist.
     */
    private function findTemplateFile(string $view): string
    {
        $file = $this->resolveViewFilePath($view);
        $hasExtension = pathinfo($file, PATHINFO_EXTENSION) !== '';

        if ($hasExtension && is_file($file)) {
            return $file;
        }

        foreach ($this->fallbackExtensions as $fallbackExtension) {
            $fileWithFallbackExtension = $file . '.' . $fallbackExtension;
            if (is_file($fileWithFallbackExtension)) {
                return $fileWithFallbackExtension;
            }
        }

        if ($hasExtension) {
            return $file;
        }

        return $file . '.' . $this->fallbackExtensions[0];
    }

    private function resolveViewFilePath(string $view): string
    {
        if (str_starts_with($view, './')) {
            return dirname($this->getRequestedViewFile($view)) . substr($view, 1);
        }

        if (str_starts_with($view, '../')) {
            return dirname($this->getRequestedViewFile($view), 2) . substr($view, 2);
        }

        if (str_starts_with($view, '//')) {
            return $this->getBasePath() . substr($view, 1);
        }

        if ($this->isWindows()) {
            if (str_contains($view, ':')) {
                return $view;
            }
        } elseif (str_starts_with($view, '/')) {
            return $view;
        }

        return ($this->context?->getViewPath() ?? $this->getBasePath()) . '/' . $view;
    }

    /**
     * @return string The requested view currently being rendered.
     */
    private function getRequestedViewFile(string $view): string
    {
        /** @psalm-suppress InvalidArrayOffset */
        return empty($this->viewFiles)
            ? throw new LogicException("Unable to resolve file for view \"$view\": no currently rendered view.")
            : end($this->viewFiles)['requested'];
    }

    /**
     * Returns whether the current environment is Windows.
     */
    private function isWindows(): bool
    {
        return DIRECTORY_SEPARATOR === '\\';
    }
}
