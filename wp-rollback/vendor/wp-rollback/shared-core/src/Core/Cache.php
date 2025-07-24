<?php

/**
 * Cache class.
 *
 * This class a wrapper for WordPress cache functions.
 * It provides a simple way to set, get and delete cache data using WordPress cache functions.
 * It also provides a way use persistence or object cache for caching.
 *
 * @package WpRollback\SharedCore\Core
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Core;

/**
 * Class Cache
 *
 * @since 1.0.0
 */
class Cache
{
    /**
     * Cache group name.
     *
     * @since 1.0.0
     */
    private string $cacheGroup;

    /**
     * Cache key prefix.
     *
     * This is used to prefix the persistence cache key name.
     *
     * @since 1.0.0
     */
    private string $cacheKeyPrefix;

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct(string $prefix = 'wp-rollback')
    {
        $this->cacheGroup = $prefix;
        $this->cacheKeyPrefix = $prefix;
    }

    /**
     * This method sets the cache.
     *
     * @since 1.0.0
     */
    public function set(string $key, $value, int $expiration = 0, bool $cacheInDatabase = false): bool
    {
        if ($cacheInDatabase) {
            return set_transient($this->getCacheKey($key), $value, $expiration);
        }

        return wp_cache_set($key, $value, $this->cacheGroup, $expiration); // phpcs:ignore WordPress.WP.AlternativeFunctions.wp_cache_set_wp_cache_set
    }

    /**
     * This method gets the cache.
     *
     * @since 1.0.0
     *
     * @return bool|mixed
     */
    public function get(string $key, bool $cacheInDatabase = false)
    {
        if ($cacheInDatabase) {
            return get_transient($this->getCacheKey($key));
        }

        return wp_cache_get($key, $this->cacheGroup); // phpcs:ignore WordPress.WP.AlternativeFunctions.wp_cache_get_wp_cache_get
    }

    /**
     * This method deletes the cache.
     *
     * @since 1.0.0
     */
    public function delete(string $key, $cacheInDatabase = false): bool
    {
        if ($cacheInDatabase) {
            return delete_transient($this->getCacheKey($key));
        }

        return wp_cache_delete($key, $this->cacheGroup); // phpcs:ignore WordPress.WP.AlternativeFunctions.wp_cache_delete_wp_cache_delete
    }

    /**
     * This method gets the cache key.
     *
     * This method is used to get the cache key when data stores in database.
     *
     * @since 1.0.0
     */
    private function getCacheKey(string $key): string
    {
        return "{$this->cacheKeyPrefix}_{$key}";
    }
} 