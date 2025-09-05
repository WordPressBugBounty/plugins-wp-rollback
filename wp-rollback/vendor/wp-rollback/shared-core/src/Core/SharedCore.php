<?php

/**
 * @package WpRollback\SharedCore\Core
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Core;

use WpRollback\SharedCore\Core\Container\ContainerSingleton;
use WpRollback\SharedCore\Core\Container\ContainerInterface;

/**
 * SharedCore class
 * 
 * Provides central access to the shared core functionality
 * Replaces the global functions with static methods
 */
class SharedCore
{
    /**
     * Whether the shared core has been initialized
     *
     * @var bool
     */
    private static bool $initialized = false;

    /**
     * Get the dependency injection container instance.
     *
     * @since 1.0.0
     *
     * @return ContainerInterface
     */
    public static function container(): ContainerInterface
    {
        return ContainerSingleton::getInstance();
    }

    /**
     * Initialize the shared core.
     *
     * This function sets up the core shared functionality used by both plugins.
     * It should be called from the main plugin files before using any SharedCore functionality.
     *
     * @since 1.0.0
     * @return void
     */
    public static function initialize(): void
    {
        if (self::$initialized) {
            return;
        }
        
        self::$initialized = true;
    }
    
    /**
     * Check if SharedCore has been initialized
     *
     * @since 1.0.0
     * @return bool
     */
    public static function isInitialized(): bool
    {
        return self::$initialized;
    }
}