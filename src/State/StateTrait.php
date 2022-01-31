<?php

declare(strict_types=1);

namespace Yiisoft\View\State;

use InvalidArgumentException;
use Yiisoft\View\Theme;

use function array_key_exists;
use function func_get_args;
use function is_array;

/**
 * @internal
 */
trait StateTrait
{
    private ?Theme $theme = null;
    private string $language = 'en';

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
     * Set the specified theme instance.
     *
     * @param Theme|null $theme The theme instance or `null` for reset theme.
     *
     * @return static
     */
    public function setTheme(?Theme $theme): self
    {
        $this->theme = $theme;
        return $this;
    }

    /**
     * Gets the theme instance, or `null` if no theme has been set.
     *
     * @return Theme|null The theme instance, or `null` if no theme has been set.
     */
    public function getTheme(): ?Theme
    {
        return $this->theme;
    }

    /**
     * Set the specified language code.
     *
     * @param string $language The language code.
     *
     * @return static
     */
    public function setLanguage(string $language): self
    {
        $this->language = $language;
        return $this;
    }

    /**
     * Gets the language code.
     *
     * @return string The language code.
     */
    public function getLanguage(): string
    {
        return $this->language;
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
     * @throws InvalidArgumentException When specified parameter already exists and is not an array.
     *
     * @return static
     */
    public function addToParameter(string $id, ...$value): self
    {
        /** @var mixed $array */
        $array = $this->parameters[$id] ?? [];
        if (!is_array($array)) {
            throw new InvalidArgumentException(
                sprintf('The "%s" parameter already exists and is not an array.', $id)
            );
        }

        $this->setParameter($id, array_merge($array, $value));

        return $this;
    }

    /**
     * Removes a common parameter.
     *
     * @param string $id The unique identifier of the parameter.
     *
     * @return static
     */
    public function removeParameter(string $id): self
    {
        unset($this->parameters[$id]);
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

    public function getParameters(): array
    {
        return $this->parameters;
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
     *
     * @return static
     */
    public function removeBlock(string $id): self
    {
        unset($this->blocks[$id]);
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
}
