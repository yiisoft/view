<?php
declare(strict_types = 1);

namespace Yiisoft\Widget;

use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\Dependency\Dependency;
use Yiisoft\View\DynamicContentAwareInterface;
use Yiisoft\View\DynamicContentAwareTrait;

/**
 * FragmentCache is used by [[\yii\base\View]] to provide caching of page fragments.
 *
 * @property string|false $cachedContent The cached content. False is returned if valid content is not found
 * in the cache. This property is read-only.
 */
class FragmentCache extends Widget implements DynamicContentAwareInterface
{
    use DynamicContentAwareTrait;

    /**
     * @var CacheInterface|array|string the cache object or the application component ID of the cache object.
     *                                  After the FragmentCache object is created, if you want to change this property,
     *                                  you should only assign it with a cache object.
     *                                  Starting from version 2.0.2, this can also be a configuration array for creating the object.
     */
    public $cache = 'cache';
    /**
     * @var int number of seconds that the data can remain valid in cache.
     *          Use 0 to indicate that the cached data will never expire.
     */
    public $duration = 60;
    /**
     * @var Dependency the dependency that the cached content depends on.
     *                       This can be either a [[Dependency]] object or a configuration array for creating the dependency object.
     *                       For example,
     *
     * ```php
     * [
     *     '__class' => \yii\caching\DbDependency::class,
     *     'sql' => 'SELECT MAX(updated_at) FROM post',
     * ]
     * ```
     *
     * would make the output cache depends on the last modified time of all posts.
     * If any post has its modification time changed, the cached content would be invalidated.
     */
    public $dependency;
    /**
     * @var string[]|string list of factors that would cause the variation of the content being cached.
     *                      Each factor is a string representing a variation (e.g. the language, a GET parameter).
     *                      The following variation setting will cause the content to be cached in different versions
     *                      according to the current application language:
     *
     * ```php
     * [
     *     Yii::getApp()->language,
     * ]
     * ```
     */
    public $variations;
    /**
     * @var string|bool the cached content. False if the content is not cached.
     */
    private $content = false;

    /**
     * Initializes the FragmentCache object.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function init(): void
    {
        parent::init();

        if ($this->cache instanceof CacheInterface && $this->getCachedContent() === false) {
            $this->getView()->pushDynamicContent($this);
            ob_start();
            ob_implicit_flush(false);
        }
    }

    /**
     * Marks the end of content to be cached.
     * Content displayed before this method call and after [[init()]]
     * will be captured and saved in cache.
     * This method does nothing if valid content is already found in cache.
     *
     * @return string the result of widget execution to be outputted.
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function run(): string
    {
        if (($content = $this->getCachedContent()) !== false) {
            return $content;
        }

        if ($this->cache instanceof CacheInterface) {
            $this->getView()->popDynamicContent();

            $content = ob_get_clean();
            if ($content === false || $content === '') {
                return '';
            }
            $data = [$content, $this->getDynamicPlaceholders()];
            $this->cache->set($this->calculateKey(), $data, $this->duration, $this->dependency);

            return $this->updateDynamicContent($content, $this->getDynamicPlaceholders());
        }

        return '';
    }

    /**
     * Returns the cached content if available.
     *
     * @return string|false the cached content. False is returned if valid content is not found in the cache.
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getCachedContent()
    {
        if ($this->content !== null) {
            return $this->content;
        }

        if (!($this->cache instanceof CacheInterface)) {
            return $this->content;
        }

        $key = $this->calculateKey();
        $data = $this->cache->get($key);
        if (!is_array($data) || count($data) !== 2) {
            return $this->content;
        }

        [$this->content, $placeholders] = $data;
        if (!is_array($placeholders) || count($placeholders) === 0) {
            return $this->content;
        }

        $this->content = $this->updateDynamicContent($this->content, $placeholders, true);

        return $this->content;
    }

    /**
     * Generates a unique key used for storing the content in cache.
     * The key generated depends on both [[id]] and [[variations]].
     *
     * @return mixed a valid cache key
     */
    protected function calculateKey()
    {
        return array_merge([__CLASS__, $this->getId()], (array) $this->variations);
    }
}
