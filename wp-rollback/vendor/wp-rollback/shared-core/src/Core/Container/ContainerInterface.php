<?php

/**
 * @package WpRollback\SharedCore\Core\Container
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Core\Container;

/**
 * Container interface for dependency injection.
 *
 */
interface ContainerInterface
{
    /**
     * Register a binding with the container.
     *
     *
     * @param string $abstract The abstract key to register
     * @param mixed $concrete The concrete implementation
     * @param bool $shared Whether the binding should be a singleton
     *
     * @return void
     */
    public function bind(string $abstract, $concrete = null, bool $shared = false): void;

    /**
     * Register a shared binding in the container.
     *
     *
     * @param string $abstract The abstract key to register
     * @param mixed $concrete The concrete implementation
     *
     * @return void
     */
    public function singleton(string $abstract, $concrete = null): void;

    /**
     * Resolve an instance from the container.
     *
     *
     * @param string $abstract The abstract key to resolve
     * @param array<mixed> $parameters Optional parameters to pass to the resolver
     *
     * @return mixed The resolved instance
     * @throws \Exception
     */
    public function make(string $abstract, array $parameters = []);

    /**
     * Determine if a given type has been bound.
     *
     *
     * @param string $abstract The abstract key to check
     *
     * @return bool
     */
    public function has(string $abstract): bool;

    /**
     * Alias a type to a different name.
     *
     *
     * @param string $abstract The abstract key to alias
     * @param string $alias The alias name
     *
     * @return void
     */
    public function alias(string $abstract, string $alias): void;
} 