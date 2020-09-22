<?php

declare(strict_types=1);

namespace Yiisoft\View;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;
use Yiisoft\Cache\CacheInterface as YiiCacheInterface;
use Yiisoft\Cache\Dependency\Dependency;

class FragmentCache implements FragmentCacheInterface, DynamicContentAwareInterface
{
    use DynamicContentAwareTrait;

    private const NOCACHE = -1;

    /**
     * @var int number of seconds that the data can remain valid in cache.
     * Use 0 to indicate that the cached data will never expire.
     */
    public int $duration = 60;

    /**
     * @var bool whether to enable the fragment cache. You may use this property to turn on and off
     * the fragment cache according to specific setting (e.g. enable fragment cache only for GET requests).
     */
    public bool $enabled = true;

    /**
     * @var CacheInterface|null
     */
    private ?CacheInterface $cache;

    /**
     * @var string
     */
    private string $content = '';

    /**
     * @var Dependency|null
     */
    private ?Dependency $dependency = null;

    /**
     * @var string
     */
    private string $id;

    /**
     * @var string
     */
    private string $key;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var bool[]
     */
    private array $renderVars;

    /**
     * @var int
     */
    private int $savedObLevel;

    /**
     * @var int
     */
    private int $status = self::STATUS_NO;

    /**
     * @var string[]
     */
    private array $vars;

    /**
     * @var View|null
     */
    private ?View $view = null;

    public function __construct(?CacheInterface $cache, LoggerInterface $logger)
    {
        $this->cache = $cache;
        $this->logger = $logger;
    }

    public function __clone()
    {
        $this->clearData();
        $this->savedObLevel = self::NOCACHE;
        $this->status = self::STATUS_INIT;
    }

    public function beginCache(?View $view, string $id, array $params = [], array $vars = []): FragmentCacheInterface
    {
        $obj = clone $this;
        $obj->view = $view;
        $obj->id = $id;
        if (isset($params['duration'])) {
            $obj->duration = (int)$params['duration'];
        }
        if (isset($params['enabled'])) {
            $obj->enabled = $obj->enabled && (bool)$params['enabled'];
        }
        if (isset($params['cache'])) {
            $obj->cache = $params['cache'];
        }
        $obj->vars = $vars;
        if (!$obj->cache || !$obj->enabled) {
            $obj->status = self::STATUS_BEGIN;
            return $obj;
        }
        if (isset($params['dependency'])) {
            if (!($obj->cache instanceof YiiCacheInterface)) {
                throw new RuntimeException('Dependencies are available only for the cache supporting the interface Yiisoft\Cache\CacheInterface.');
            }
            $obj->dependency = $params['dependency'];
        }
        $obj->key = $obj->buildKey(isset($params['variations']) ? [$id, $params['variations']] : $id);
        if ($obj->readFromCache()) {
            $obj->status = self::STATUS_IN_CACHE;
            return $obj;
        }
        $obj->status = self::STATUS_BEGIN;
        $obj->startCache();
        return $obj;
    }

    public function endCache(): void
    {
        if ($this->status === self::STATUS_IN_CACHE) {
            $this->status = self::STATUS_END;
            $this->logger->debug("Rendering Fragment (from cache): {$this->id}");
            echo $this->content;
            $this->clearData();
            return;
        }
        if ($this->status !== self::STATUS_BEGIN) {
            throw new RuntimeException('This method is not available in the current state.');
        }
        $this->status = self::STATUS_END;
        if ($this->savedObLevel === self::NOCACHE) {
            $this->logger->debug("Rendering Fragment (cache disabled): {$this->id}");
            return;
        }
        $this->logger->debug("Rendering Fragment (miss): {$this->id}");
        $content = $this->getCachedContent();
        if ($this->view) {
            $this->view->popDynamicContent($this);
        }
        if ($this->enabled && $this->cache) {
            if ($this->cache instanceof YiiCacheInterface) {
                $this->cache->set($this->key, [$content, $this->renderVars, $this->getDynamicPlaceholders()], $this->duration, $this->dependency);
            } else {
                $this->cache->set($this->key, [$content, $this->renderVars, $this->getDynamicPlaceholders()], $this->duration);
            }
        }
        $content = $this->updateContent($content, $this->getDynamicPlaceholders());
        echo $content;
        $this->clearData();
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function renderVar(string $name): string
    {
        if ($this->savedObLevel === self::NOCACHE) {
            return \strval($this->vars[$name] ?? '');
        }
        $this->renderVars[$name] = true;
        return "<![CDATA[YII-VAR-$name]]>";
    }

    protected function getView(): View
    {
        if (!$this->view) {
            throw new RuntimeException('The view is undefined.');
        }
        return $this->view;
    }

    /**
     * Builds a normalized cache key from a given key.
     *
     * If the given key is a string containing alphanumeric characters only and no more than 32 characters,
     * then the key will be returned back as it is, integers will be converted to strings. Otherwise, a normalized key
     * is generated by serializing the given key and applying MD5 hashing.
     * @param mixed $key the key to be normalized
     * @return string the generated cache key
     */
    private function buildKey($key): string
    {
        $jsonKey = \json_encode($key);
        if ($jsonKey === false) {
            throw new InvalidArgumentException('Invalid key. ' . \json_last_error_msg());
        }
        return \md5($jsonKey);
    }

    /**
     * Clearing memory after caching is complete.
     */
    private function clearData()
    {
        $this->content = '';
        $this->dependency = null;
        $this->id = '';
        $this->key = '';
        $this->renderVars = [];
        $this->vars = [];
        $this->view = null;
        $this->setDynamicPlaceholders([]);
    }

    private function getCachedContent(): string
    {
        if (\ob_get_level() < $this->savedObLevel) {
            throw new RuntimeException('The cache level is less than expected.');
        }
        while (\ob_get_level() > $this->savedObLevel) {
            if (!\ob_end_flush()) {
                if (!\is_string($content = \ob_get_clean())) {
                    throw new RuntimeException('It is not possible to delete a higher-level cache.');
                }
                echo $content;
            }
        }
        return \ob_get_clean() ?: '';
    }

    private function readFromCache(): bool
    {
        if (!$this->cache || !\is_array($value = $this->cache->get($this->key)) || \count($value) !== 3) {
            return false;
        }
        [$content, $renderVars, $placeholders] = $value;
        if (!\is_string($content) || !\is_array($renderVars) || !\is_array($placeholders)) {
            return false;
        }
        if ($placeholders && !$this->view) {
            return false;
        }
        $this->renderVars = $renderVars;
        $this->content = $this->updateContent($content, $placeholders, true);
        return true;
    }

    private function startCache(): void
    {
        if (!\ob_start()) {
            return;
        }
        \ob_implicit_flush(0);
        $this->savedObLevel = \ob_get_level();
        if ($this->view) {
            $this->view->pushDynamicContent($this);
        }
    }

    private function updateContent(string $content, array $placeholders, bool $isRestoredFromCache = false): string
    {
        $content = $this->updateVarContent($content);
        if ($this->view) {
            $content = $this->updateDynamicContent($content, $placeholders, $isRestoredFromCache);
        }
        return $content;
    }

    private function updateVarContent(string $content): string
    {
        if (!$this->renderVars) {
            return $content;
        }
        $vars = [];
        foreach (\array_merge($this->renderVars, \array_keys($this->vars)) as $name => $_) {
            $vars["<![CDATA[YII-VAR-$name]]>"] = \strval($this->vars[$name] ?? '');
        }
        return \strtr($content, $vars);
    }
}
