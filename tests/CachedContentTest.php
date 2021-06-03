<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\Dependency\Dependency;
use Yiisoft\Cache\Dependency\ValueDependency;
use Yiisoft\View\Cache\CachedContent;
use Yiisoft\View\Cache\DynamicContent;

final class CachedContentTest extends TestCase
{
    private Cache $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = new Cache(new ArrayCache());
    }

    public function cacheParametersProvider(): array
    {
        return [
            'ttl-null-dependency-null' => [null, null],
            'ttl-30-dependency-true' => [30, new ValueDependency(true)],
            'ttl-60-dependency-false' => [60, new ValueDependency(false)],
        ];
    }

    /**
     * @dataProvider cacheParametersProvider
     *
     * @param int|null $ttl
     * @param Dependency|null $dependency
     */
    public function testCache(?int $ttl, ?Dependency $dependency): void
    {
        $cacheContent = new CachedContent('test', $this->cache);
        $this->assertNull($cacheContent->get());

        for ($counter = 0; $counter < 42; $counter++) {
            $dynamicContent = new DynamicContent(
                'dynamic-test',
                static fn ($params) => (string) $params['counter'],
                ['counter' => $counter],
            );

            $cacheContent = new CachedContent('test', $this->cache, [$dynamicContent]);
            $content = "Cached: $counter. Dynamic: {$dynamicContent->placeholder()}";
            $expectedContent = "Cached: 0. Dynamic: $counter";

            $this->assertSame($expectedContent, $cacheContent->cache($content, $ttl, $dependency));
            $this->assertSame($expectedContent, $cacheContent->get());
        }
    }

    public function testCacheWithExpiredTtl(): void
    {
        $cacheContent = new CachedContent('test', $this->cache);
        $this->assertNull($cacheContent->get());

        for ($counter = 0; $counter < 42; $counter++) {
            $dynamicContent = new DynamicContent(
                'dynamic-test',
                static fn ($params) => (string) $params['counter'],
                ['counter' => $counter],
            );

            $cacheContent = new CachedContent('test', $this->cache, [$dynamicContent]);
            $content = "Cached: $counter. Dynamic: {$dynamicContent->placeholder()}";
            $expectedContent = "Cached: $counter. Dynamic: $counter";

            $this->assertSame($expectedContent, $cacheContent->cache($content, -1));
            $this->assertNull($cacheContent->get());
        }
    }

    public function testCacheWithVariants(): void
    {
        for ($counter = 0; $counter < 42; $counter++) {
            $dynamicContent = new DynamicContent(
                'dynamic-test',
                static fn ($params) => (string) $params['counter'],
                ['counter' => $counter],
            );

            $cacheContent = new CachedContent('test', $this->cache, [$dynamicContent], ['en']);
            $content = "Cached: $counter. Dynamic: {$dynamicContent->placeholder()}";
            $expectedContent = "Cached: 0. Dynamic: $counter";

            $this->assertSame($expectedContent, $cacheContent->cache($content));
            $this->assertSame($expectedContent, $cacheContent->get());

            $cacheContent = new CachedContent('test', $this->cache, [$dynamicContent], ['ru']);
            $this->assertNull($cacheContent->get());
        }
    }
}
