<?php

/**
 * @package WpRollback\SharedCore
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Core\Container;

/**
 * Container singleton class
 *
 * Provides a single point of access to the container across the application.
 */
class ContainerSingleton
{
    /**
     * @var ContainerInterface|null
     */
    private static ?ContainerInterface $instance = null;

    /**
     * Get the container instance
     *
     * @return ContainerInterface
     */
    public static function getInstance(): ContainerInterface
    {
        if (null === self::$instance) {
            self::$instance = new Container();
        }

        return self::$instance;
    }

    /**
     * Reset the container instance
     * 
     * Mainly used for testing
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    /**
     * Private constructor to prevent instantiation
     */
    private function __construct()
    {
        // Prevent instantiation
    }
} 