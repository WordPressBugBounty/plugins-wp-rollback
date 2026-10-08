<?php

/**
 * Base PluginSetup class for both Free and Pro plugins to extend.
 *
 * @package WpRollback\SharedCore\PluginSetup
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\PluginSetup;

use WpRollback\SharedCore\Core\Contracts\ServiceProvider;
use WpRollback\SharedCore\Core\Exceptions\Primitives\InvalidArgumentException;

/**
 * Base Plugin Setup Class
 *
 */
abstract class PluginSetup
{
    /**
     * This flag is used to check if the main service providers have been loaded.
     *
     */
    protected bool $providersLoaded = false;

    /**
     * This flag is used to check if the pre-boot service providers have been loaded.
     *
     */
    protected bool $preBootProvidersLoaded = false;

    /**
     * This is a list of service providers that will be loaded during init (plugins_loaded).
     *
     */
    protected array $serviceProviders = [];

    /**
     * This is a list of pre-boot service providers loaded before/during boot.
     *
     */
    protected array $preBootServiceProviders = [];

    /**
     * Bootstraps the WP Rollback Plugin
     *
     *
     * @throws \Exception
     */
    abstract public function boot(): void;

    /**
     * Initiate plugin when WordPress initializes plugins.
     *
     */
    abstract public function init(): void;

    /**
     * Load pre-boot service providers prior to plugin boot completion.
     *
     */
    protected function loadPreBootServiceProviders(): void
    {
        $this->instantiateAndLoadProviders($this->preBootServiceProviders, $this->preBootProvidersLoaded);
    }

    /**
     * This function is used to load main service providers during init.
     *
     */
    protected function loadServiceProviders(): void
    {
        $this->instantiateAndLoadProviders($this->serviceProviders, $this->providersLoaded);
    }

    /**
     * Register external libraries
     *
     */
    abstract protected function registerLibraries(): void;

    /**
     * Helper function to instantiate, register, and boot service providers.
     *
     * @param array $providerClasses List of service provider class names.
     * @param bool  $loadedFlag      Flag passed by reference to track loading status.
     *
     * @return void
     * @throws InvalidArgumentException If a provider does not implement ServiceProvider.
     */
    protected function instantiateAndLoadProviders(array $providerClasses, bool &$loadedFlag): void
    {
        if ($loadedFlag) {
            return;
        }

        $providers = [];

        foreach ($providerClasses as $serviceProvider) {
            $providerInstance = $this->validateAndInstantiate($serviceProvider);
            $providerInstance->register();
            $providers[] = $providerInstance;
        }

        foreach ($providers as $providerInstance) {
            $providerInstance->boot();
        }

        $loadedFlag = true;
    }

    /**
     * Validate that a class implements ServiceProvider and return a new instance.
     *
     * @param string $serviceProvider Service provider class name.
     *
     * @return ServiceProvider
     * @throws InvalidArgumentException If class does not implement ServiceProvider.
     */
    protected function validateAndInstantiate(string $serviceProvider): ServiceProvider
    {
        if (!is_subclass_of($serviceProvider, ServiceProvider::class)) {
            throw new InvalidArgumentException(
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                "$serviceProvider class must implement the ServiceProvider interface"
            );
        }

        return new $serviceProvider();
    }
}