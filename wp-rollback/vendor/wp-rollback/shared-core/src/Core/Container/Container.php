<?php

/**
 * @package WpRollback\SharedCore\Core\Container
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Core\Container;

use Closure;
use ReflectionClass;
use ReflectionParameter;
use WpRollback\SharedCore\Core\Container\Exceptions\BindingResolutionException;

/**
 * Container implementation.
 *
 * @since 1.0.0
 */
class Container implements ContainerInterface
{
    /**
     * Bindings registered in the container.
     *
     * @var array<string, mixed>
     */
    protected array $bindings = [];

    /**
     * Resolved instances.
     *
     * @var array<string, mixed>
     */
    protected array $instances = [];

    /**
     * Aliases registered in the container.
     *
     * @var array<string, string>
     */
    protected array $aliases = [];

    /**
     * {@inheritdoc}
     */
    public function bind(string $abstract, $concrete = null, bool $shared = false): void
    {
        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        if (!$concrete instanceof Closure) {
            $concrete = $this->getClosure($abstract, $concrete);
        }

        $this->bindings[$abstract] = compact('concrete', 'shared');
    }

    /**
     * {@inheritdoc}
     */
    public function singleton(string $abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * {@inheritdoc}
     */
    public function make(string $abstract, array $parameters = [])
    {
        $abstract = $this->getAlias($abstract);

        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $concrete = $this->getConcrete($abstract);

        $object = $this->build($concrete, $parameters);

        if ($this->isShared($abstract)) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * {@inheritdoc}
     */
    public function alias(string $abstract, string $alias): void
    {
        $this->aliases[$alias] = $abstract;
    }

    /**
     * Get the concrete implementation for a given abstract.
     *
     * @since 1.0.0
     *
     * @param string $abstract
     * @return mixed
     */
    protected function getConcrete(string $abstract)
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    /**
     * Determine if the given abstract type has been bound as a shared instance.
     *
     * @since 1.0.0
     *
     * @param string $abstract
     * @return bool
     */
    protected function isShared(string $abstract): bool
    {
        return isset($this->bindings[$abstract]['shared']) && true === $this->bindings[$abstract]['shared'];
    }

    /**
     * Get the alias for an abstract if available.
     *
     * @since 1.0.0
     *
     * @param string $abstract
     * @return string
     */
    protected function getAlias(string $abstract): string
    {
        return isset($this->aliases[$abstract]) ? $this->getAlias($this->aliases[$abstract]) : $abstract;
    }

    /**
     * Get the closure for building a concrete instance.
     *
     * @since 1.0.0
     *
     * @param string $abstract
     * @param string|object $concrete
     * @return Closure
     */
    protected function getClosure(string $abstract, $concrete): Closure
    {
        return function ($container, $parameters = []) use ($abstract, $concrete) {
            if ($abstract === $concrete) {
                return $container->build($concrete, $parameters);
            }

            return $container->resolve($concrete, $parameters);
        };
    }

    /**
     * Build a concrete instance.
     *
     * @since 1.0.0
     *
     * @param mixed $concrete
     * @param array<mixed> $parameters
     * @return mixed
     * @throws BindingResolutionException
     */
    public function build($concrete, array $parameters = [])
    {
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }

        try {
            $reflector = new ReflectionClass($concrete);
        } catch (\ReflectionException $e) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new BindingResolutionException(sprintf("Target class [%s] does not exist.", $concrete), 0, $e);
        }

        if (!$reflector->isInstantiable()) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            throw new BindingResolutionException(sprintf("Target [%s] is not instantiable.", $concrete));
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $concrete();
        }

        $dependencies = $constructor->getParameters();

        $instances = $this->resolveDependencies($dependencies, $parameters);

        return $reflector->newInstanceArgs($instances);
    }

    /**
     * Resolve a class from the container.
     *
     * @since 1.0.0
     *
     * @param string $concrete
     * @param array<mixed> $parameters
     * @return mixed
     * @throws BindingResolutionException
     */
    protected function resolve(string $concrete, array $parameters = [])
    {
        return $this->build($concrete, $parameters);
    }

    /**
     * Resolve dependencies from the container.
     *
     * @since 1.0.0
     *
     * @param array<ReflectionParameter> $dependencies
     * @param array<mixed> $parameters
     * @return array<mixed>
     * @throws BindingResolutionException
     */
    protected function resolveDependencies(array $dependencies, array $parameters = []): array
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            $name = $dependency->getName();

            if (array_key_exists($name, $parameters)) {
                $results[] = $parameters[$name];
                continue;
            }

            $class = $dependency->getType() && !$dependency->getType()->isBuiltin() ? new ReflectionClass($dependency->getType()->getName()) : null;

            if (is_null($class)) {
                if ($dependency->isDefaultValueAvailable()) {
                    $results[] = $dependency->getDefaultValue();
                } else {
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    throw new BindingResolutionException(sprintf("Unresolvable dependency resolving [%s]", $name));
                }
            } else {
                $results[] = $this->make($class->getName());
            }
        }

        return $results;
    }
} 