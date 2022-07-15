<?php

declare(strict_types=1);

namespace Yiisoft\View\Cache;

use DateInterval;
use InvalidArgumentException;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\CacheKeyNormalizer;
use Yiisoft\Cache\Dependency\Dependency;

use function array_merge;
use function get_class;
use function gettype;
use function is_string;
use function is_object;
use function sprintf;
use function strtr;

/**
 * `CacheContent` caches content, supports the use of dynamic content {@see DynamicContent} inside cached content.
 */
final class CachedContent
{
    private string $id;
    private CacheInterface $cache;
    private CacheKeyNormalizer $cacheKeyNormalizer;

    /**
     * @var array<string, DynamicContent>
     */
    private array $dynamicContents = [];

    /**
     * @var string[]
     */
    private array $variations = [];

    /**
     * @param string $id The unique identifier of the cached content.
     * @param CacheInterface $cache The cache instance.
     * @param DynamicContent[] $dynamicContents The dynamic content instances.
     * @param string[] $variations List of string factors that would cause the variation of the content being cached.
     */
    public function __construct(string $id, CacheInterface $cache, array $dynamicContents = [], array $variations = [])
    {
        $this->id = $id;
        $this->cache = $cache;
        $this->cacheKeyNormalizer = new CacheKeyNormalizer();
        $this->setDynamicContents($dynamicContents);
        $this->setVariations($variations);
    }

    /**
     * Caches, replaces placeholders with actual dynamic content, and returns the full actual content.
     *
     * @param string $content The content of the item to cache store.
     * @param DateInterval|int|null $ttl The TTL of the cached content.
     * @param Dependency|null $dependency The dependency of the cached content.
     * @param float $beta The value for calculating the range that is used for "Probably early expiration".
     *
     * @see CacheInterface::getOrSet()
     *
     * @return string The rendered cached content.
     */
    public function cache(string $content, $ttl = 60, Dependency $dependency = null, float $beta = 1.0): string
    {
        /** @psalm-suppress MixedArgument */
        return $this->replaceDynamicPlaceholders(
            $this->cache->getOrSet($this->cacheKey(), static fn (): string => $content, $ttl, $dependency, $beta),
        );
    }

    /**
     * Returns cached content with placeholders replaced with actual dynamic content.
     *
     * @return string|null The cached content. Null is returned if valid content is not found in the cache.
     */
    public function get(): ?string
    {
        /** @var string|null $content */
        $content = $this->cache
            ->psr()
            ->get($this->cacheKey());

        if ($content === null) {
            return null;
        }

        return $this->replaceDynamicPlaceholders($content);
    }

    /**
     * Generates a unique key used for storing the content in cache.
     *
     * @return string A valid cache key.
     */
    private function cacheKey(): string
    {
        return $this->cacheKeyNormalizer->normalize(array_merge([__CLASS__, $this->id], $this->variations));
    }

    /**
     * Replaces placeholders with actual dynamic content.
     *
     * @param string $content The content to be replaced.
     *
     * @return string The content with replaced placeholders.
     */
    private function replaceDynamicPlaceholders(string $content): string
    {
        $dynamicContents = [];

        foreach ($this->dynamicContents as $dynamicContent) {
            $dynamicContents[$dynamicContent->placeholder()] = $dynamicContent->content();
        }

        if (!empty($dynamicContents)) {
            $content = strtr($content, $dynamicContents);
        }

        return $content;
    }

    /**
     * Sets dynamic content instances.
     *
     * @param array $dynamicContents The dynamic content instances to set.
     */
    private function setDynamicContents(array $dynamicContents): void
    {
        foreach ($dynamicContents as $dynamicContent) {
            if (!($dynamicContent instanceof DynamicContent)) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid dynamic content "%s" specified. It must be a "%s" instance.',
                    is_object($dynamicContent) ? get_class($dynamicContent) : gettype($dynamicContent),
                    DynamicContent::class,
                ));
            }

            $this->dynamicContents[$dynamicContent->id()] = $dynamicContent;
        }
    }

    /**
     * Sets variations.
     *
     * @param array $variations The variations to set.
     */
    private function setVariations(array $variations): void
    {
        foreach ($variations as $variation) {
            if (!is_string($variation)) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid variation "%s" specified. It must be a string type.',
                    is_object($variation) ? get_class($variation) : gettype($variation),
                ));
            }

            $this->variations[] = $variation;
        }
    }
}
