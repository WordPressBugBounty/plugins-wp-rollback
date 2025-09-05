<?php

/**
 * @package WpRollback\SharedCore\Core
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Core;

use WpRollback\SharedCore\Core\Request;

/**
 * Hooks class.
 *
 * @since 1.0.0
 */
class Hooks
{
    /**
     * Dependency container for class resolution.
     *
     * @since 1.0.0
     * @var array<string, callable>
     */
    private static array $container = [];

    /**
     * Add an action hook.
     *
     * @since 1.0.0
     *
     * @param string $hook          The action hook.
     * @param string $class         The class to call.
     * @param string $method        The method to call.
     * @param int    $priority      The priority level.
     * @param int    $acceptedArgs  The number of arguments to accept.
     *
     * @return void
     */
    public static function addAction(
        string $hook,
        string $class,
        string $method = '__invoke',
        int $priority = 10,
        int $acceptedArgs = 1
    ): void {
        add_action($hook, [self::resolveClass($class), $method], $priority, $acceptedArgs);
    }

    /**
     * Add a filter hook.
     *
     * @since 1.0.0
     *
     * @param string $hook          The filter hook.
     * @param string $class         The class to call.
     * @param string $method        The method to call.
     * @param int    $priority      The priority level.
     * @param int    $acceptedArgs  The number of arguments to accept.
     *
     * @return void
     */
    public static function addFilter(
        string $hook,
        string $class,
        string $method = '__invoke',
        int $priority = 10,
        int $acceptedArgs = 1
    ): void {
        add_filter($hook, [self::resolveClass($class), $method], $priority, $acceptedArgs);
    }

    /**
     * Register a factory for a class.
     *
     * This allows defining how specific classes should be instantiated
     * when they're needed by the hook system.
     *
     * @since 1.0.0
     *
     * @param string   $class   The fully qualified class name.
     * @param callable $factory A function that returns an instance of the class.
     *
     * @return void
     */
    public static function registerFactory(string $class, callable $factory): void
    {
        self::$container[$class] = $factory;
    }

    /**
     * Resolve the class with appropriate dependencies.
     *
     * This method instantiates classes with their required dependencies.
     * If a factory was registered for the class, it will be used.
     * Controllers will automatically receive a Request object.
     *
     * @since 1.0.0
     *
     * @param string $class The fully qualified class name to resolve.
     *
     * @return object The instantiated class instance with dependencies.
     */
    protected static function resolveClass(string $class): object
    {
        // If we have a factory registered for this class, use it
        if (isset(self::$container[$class])) {
            $factory = self::$container[$class];
            return $factory();
        }
        
        // Check if the class is a Controller
        if (is_subclass_of($class, 'WpRollback\\SharedCore\\Core\\Contracts\\Controller')) {
            // Provide a Request object for controllers
            return new $class(new Request());
        }

        // Default case: instantiate without arguments
        return new $class();
    }
} 