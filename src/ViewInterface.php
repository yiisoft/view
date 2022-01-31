<?php

declare(strict_types=1);

namespace Yiisoft\View;

use InvalidArgumentException;
use RuntimeException;
use Throwable;
use Yiisoft\View\Exception\ViewNotFoundException;

/**
 * View allows rendering templates and sub-templates using data provided.
 */
interface ViewInterface
{
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
    public function withRenderers(array $renderers): self;

    /**
     * Returns a new instance with the specified source language.
     *
     * @param string $language The source language.
     *
     * @return static
     */
    public function withSourceLanguage(string $language): self;

    /**
     * Returns a new instance with the specified default view file extension.
     *
     * @param string $defaultExtension The default view file extension. Default is "php".
     * This will be appended to view file names if they don't have file extensions.
     *
     * @return static
     */
    public function withDefaultExtension(string $defaultExtension): self;

    /**
     * Returns a new instance with the specified view context instance.
     *
     * @param ViewContextInterface $context The context under which the {@see renderFile()} method is being invoked.
     *
     * @return static
     */
    public function withContext(ViewContextInterface $context): self;

    /**
     * Returns a new instance with the specified view context path.
     *
     * @param string $path The context path under which the {@see renderFile()} method is being invoked.
     *
     * @return static
     */
    public function withContextPath(string $path): self;

    /**
     * Returns a new instance with specified salt for the placeholder signature {@see getPlaceholderSignature()}.
     *
     * @param string $salt The placeholder salt.
     *
     * @return static
     */
    public function withPlaceholderSalt(string $salt): self;

    /**
     * Returns a new instance with cleared state (blocks, parameters, etc.)
     *
     * @return static
     */
    public function withClearedState(): self;

    /**
     * Set the specified language code.
     *
     * @param string $language The language code.
     *
     * @return static
     */
    public function setLanguage(string $language): self;

    /**
     * Gets the base path to the view directory.
     *
     * @return string The base view path.
     */
    public function getBasePath(): string;

    /**
     * Gets the default view file extension.
     *
     * @return string The default view file extension.
     */
    public function getDefaultExtension(): string;

    /**
     * Gets the theme instance, or `null` if no theme has been set.
     *
     * @return Theme|null The theme instance, or `null` if no theme has been set.
     */
    public function getTheme(): ?Theme;

    /**
     * Set the specified theme instance.
     *
     * @param Theme|null $theme The theme instance or `null` for reset theme.
     *
     * @return static
     */
    public function setTheme(?Theme $theme): self;

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
    public function setParameters(array $parameters): self;

    /**
     * Sets a common parameter that is accessible in all view templates.
     *
     * @param string $id The unique identifier of the parameter.
     * @param mixed $value The value of the parameter.
     *
     * @return static
     */
    public function setParameter(string $id, $value): self;

    /**
     * Add values to end of common array parameter. If specified parameter does not exist or him is not array,
     * then parameter will be added as empty array.
     *
     * @param string $id The unique identifier of the parameter.
     * @param mixed ...$value Value(s) for add to end of array parameter.
     *
     * @throws InvalidArgumentException When specified parameter already exists and is not an array.
     *
     * @return static
     */
    public function addToParameter(string $id, ...$value): self;

    /**
     * Removes a common parameter.
     *
     * @param string $id The unique identifier of the parameter.
     *
     * @return static
     */
    public function removeParameter(string $id): self;

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
    public function getParameter(string $id);

    /**
     * Checks the existence of a common parameter by ID.
     *
     * @param string $id The unique identifier of the parameter.
     *
     * @return bool Whether a custom parameter that is common for all view templates exists.
     */
    public function hasParameter(string $id): bool;

    /**
     * Sets a content block.
     *
     * @param string $id The unique identifier of the block.
     * @param string $content The content of the block.
     *
     * @return static
     */
    public function setBlock(string $id, string $content): self;

    /**
     * Removes a content block.
     *
     * @param string $id The unique identifier of the block.
     *
     * @return static
     */
    public function removeBlock(string $id): self;

    /**
     * Gets content of the block by ID.
     *
     * @param string $id The unique identifier of the block.
     *
     * @return string The content of the block.
     */
    public function getBlock(string $id): string;

    /**
     * Checks the existence of a content block by ID.
     *
     * @param string $id The unique identifier of the block.
     *
     * @return bool Whether a content block exists.
     */
    public function hasBlock(string $id): bool;

    /**
     * Gets the view file currently being rendered.
     *
     * @return string|null The view file currently being rendered. `null` if no view file is being rendered.
     */
    public function getViewFile(): ?string;

    /**
     * Gets the placeholder signature.
     *
     * @return string The placeholder signature.
     */
    public function getPlaceholderSignature(): string;

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
    public function render(string $view, array $parameters = []): string;

    /**
     * Renders a view file.
     *
     * If the theme was set {@see setTheme()}, it will try to render the themed version of the view file
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
    public function renderFile(string $viewFile, array $parameters = []): string;

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
    public function localize(string $file, ?string $language = null, ?string $sourceLanguage = null): string;

    /**
     * Clears the data for working with the event loop.
     */
    public function clear(): void;
}
