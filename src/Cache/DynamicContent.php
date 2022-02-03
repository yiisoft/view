<?php

declare(strict_types=1);

namespace Yiisoft\View\Cache;

/**
 * `DynamicContent` generates data for dynamic content that is used for cached content {@see CachedContent}.
 */
final class DynamicContent
{
    private string $id;
    private array $parameters;

    /**
     * @var callable
     */
    private $contentGenerator;

    /**
     * @param string $id The unique identifier of the dynamic content.
     * @param callable $contentGenerator PHP callable with the signature: `function (array $parameters = []): string;`.
     * @param array $parameters The parameters (name-value pairs) that will be passed in the $contentGenerator context.
     */
    public function __construct(string $id, callable $contentGenerator, array $parameters = [])
    {
        $this->id = $id;
        $this->contentGenerator = $contentGenerator;
        $this->parameters = $parameters;
    }

    /**
     * Returns a unique identifier of the dynamic content.
     *
     * @return string The unique identifier of the dynamic content.
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * Generates the dynamic content.
     *
     * @return string The generated dynamic content.
     *
     * @psalm-suppress MixedInferredReturnType, MixedReturnStatement
     */
    public function content(): string
    {
        return ($this->contentGenerator)($this->parameters);
    }

    /**
     * Returns the placeholder of the dynamic content.
     *
     * @return string The placeholder of the dynamic content.
     */
    public function placeholder(): string
    {
        return "<![CDATA[YII-DYNAMIC-$this->id]]>";
    }
}
