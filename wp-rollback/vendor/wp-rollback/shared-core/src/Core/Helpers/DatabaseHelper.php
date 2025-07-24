<?php

declare(strict_types=1);

namespace WpRollback\SharedCore\Core\Helpers;

use WpRollback\SharedCore\Core\ConstantsInterface;

/**
 * Database helper class
 *
 * @package WpRollback\SharedCore\Core\Helpers
 * @since 1.0.0
 */
class DatabaseHelper
{
    /**
     * This function is used to generate a meta key.
     *
     * This function helps to generate consistent meta keys for the plugin.
     *
     * @param ConstantsInterface $constants The Constants instance
     * @param string $key The key to generate a meta key for
     * @param bool $hide Whether to hide the key or not
     *
     * @since 1.0.0
     *
     * @return string The generated meta key
     */
    public static function dbMetaKeyGenerator(ConstantsInterface $constants, string $key, bool $hide = false): string
    {
        $prefix = $constants->getSlug();
        $key = $prefix . "_$key";

        return $hide ? "_$key" : $key;
    }

    /**
     * This function is used to generate an option key.
     *
     * This function helps to generate consistent meta keys for the plugin.
     *
     * @param ConstantsInterface $constants The Constants instance
     * @param string $key The key to generate a meta key for
     *
     * @since 1.0.0
     *
     * @return string The generated meta key
     */
    public static function dbOptionKeyGenerator(ConstantsInterface $constants, string $key): string
    {
        $prefix = $constants->getSlug();
        return $prefix . "_$key";
    }
} 