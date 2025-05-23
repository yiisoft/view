<?php

declare(strict_types=1);

namespace Yiisoft\View;

use InvalidArgumentException;
use LogicException;
use Throwable;
use Yiisoft\View\Exception\ViewNotFoundException;

/**
 * View allows rendering templates and sub-templates using data provided.
 */
interface ViewInterface
{
    /**
     * @psalm-suppress MissingClassConstType Needs for PHP 8.1 only
     */
    public const PHP_EXTENSION = 'php';

    /**
     * Returns a new instance with specified base path to the view directory.
     *
     * @param string|null $basePath The base path to the view directory.
     */
    public function withBasePath(string|null $basePath): static;

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
    public function withRenderers(array $renderers): static;

    /**
     * Returns a new instance with the specified source locale.
     *
     * @param string $locale The source locale.
     */
    public function withSourceLocale(string $locale): static;

    /**
     * Returns a new instance with the specified view context instance.
     *
     * @param ViewContextInterface|null $context The context under which the {@see render()} method is being invoked.
     */
    public function withContext(ViewContextInterface|null $context): static;

    /**
     * Returns a new instance with the specified view context path.
     *
     * @param string $path The context path under which the {@see render()} method is being invoked.
     */
    public function withContextPath(string $path): static;

    /**
     * Returns a new instance with specified salt for the placeholder signature {@see getPlaceholderSignature()}.
     *
     * @param string $salt The placeholder salt.
     */
    public function withPlaceholderSalt(string $salt): static;

    /**
     * Returns a new instance with cleared state (blocks, parameters, etc.)
     */
    public function withClearedState(): static;

    /**
     * Returns a new instance with deep clone of the object, including state cloning.
     */
    public function deepClone(): static;

    /**
     * Set the specified locale code.
     *
     * @param string $locale The locale code.
     */
    public function setLocale(string $locale): static;

    /**
     * Set the specified locale code.
     *
     * @param string $locale The locale code.
     */
    public function withLocale(string $locale): static;

    /**
     * Get the specified locale code.
     *
     * @return string The locale code.
     */
    public function getLocale(): string;

    /**
     * Gets the base path to the view directory.
     *
     * @return string The base view path.
     */
    public function getBasePath(): string;

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
     */
    public function setTheme(?Theme $theme): static;

    /**
     * Set the specified theme instance immutable.
     *
     * @param Theme|null $theme The theme instance or `null` for reset theme.
     */
    public function withTheme(?Theme $theme): static;

    /**
     * Sets a common parameters that is accessible in all view templates.
     *
     * @param array $parameters Parameters that are common for all view templates.
     *
     * @psalm-param array<string, mixed> $parameters
     *
     * @see setParameter()
     */
    public function setParameters(array $parameters): static;

    /**
     * Sets a common parameter that is accessible in all view templates.
     *
     * @param string $id The unique identifier of the parameter.
     * @param mixed $value The value of the parameter.
     */
    public function setParameter(string $id, mixed $value): static;

    /**
     * Add values to end of common array parameter. If specified parameter does not exist or him is not array,
     * then parameter will be added as empty array.
     *
     * @param string $id The unique identifier of the parameter.
     * @param mixed ...$value Value(s) for add to end of array parameter.
     *
     * @throws InvalidArgumentException When specified parameter already exists and is not an array.
     */
    public function addToParameter(string $id, mixed ...$value): static;

    /**
     * Removes a common parameter.
     *
     * @param string $id The unique identifier of the parameter.
     */
    public function removeParameter(string $id): static;

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
    public function getParameter(string $id, mixed ...$default): mixed;

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
     */
    public function setBlock(string $id, string $content): static;

    /**
     * Removes a content block.
     *
     * @param string $id The unique identifier of the block.
     */
    public function removeBlock(string $id): static;

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
     * - the absolute path to the view file, e.g. "/path/to/view.php";
     * - the name of the view starting with `//` to join the base path {@see getBasePath()}, e.g. "//site/index";
     * - the name of the view starting with `./` to join the directory containing the view currently being rendered
     *   (i.e., this happens when rendering a view within another view), e.g. "./widget";
     * - the name of the view without the starting `//` or `./` (e.g. "site/index"). The corresponding view file will be
     *   looked for under the {@see ViewContextInterface::getViewPath()} of the context set via {@see withContext()}.
     *   If the context instance was not set {@see withContext()}, it will be looked for under the base path.
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
    public function render(string $view, array $parameters = []): string;

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
    public function localize(string $file, ?string $locale = null, ?string $sourceLocale = null): string;

    /**
     * Clears the data for working with the event loop.
     */
    public function clear(): void;
}
