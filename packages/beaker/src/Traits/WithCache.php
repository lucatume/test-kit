<?php
/**
 * Methods to interact with the PSR cache implementation.
 *
 * @package lucatume\Beaker\Traits
 */

namespace lucatume\Beaker\Traits;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Trait WithCache
 *
 * @package lucatume\Beaker\Traits
 */
trait WithCache
{
    /**
     * A cache to map URIs to the resolved file.
     *
     * @var CacheInterface
     */
    protected $cache;

    /**
     * Sets the cache object the instance should use.
     *
     * @param CacheInterface $cache The cache object to use.
     */
    protected function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Returns the cache key for a method and the salt.
     *
     * @param string $group The group to build the cache key for.
     * @param string $element The element to build the cache key for.
     *
     * @return string The cache key.
     */
    protected function cacheKey(string $group, string $element): string
    {
        return $group . '_' . md5($element);
    }

    /**
     * Safely get a cache value, w/o throwing exceptions.
     *
     * @param string $key The cache key for the value.
     * @param mixed $value The value to store in the cache.
     */
    protected function cacheSafeSet(string $key, $value)
    {
        try {
            $cached = $this->cache->set($key, $value);
        } catch (InvalidArgumentException $e) {
            // Move on.
        }
    }

    /**
     * Safely get a cached value.
     *
     * @param string $key The cache key for the value.
     * @param mixed|null $default The default value to return if the cached value is not found.
     *
     * @return mixed|null The cached value, if found, or the default value.
     */
    protected function cacheSafeGet(string $key, $default = null)
    {
        try {
            $cached = $this->cache->get($key, $default);
        } catch (InvalidArgumentException $e) {
            return $default;
        }

        return $cached;
    }
}
