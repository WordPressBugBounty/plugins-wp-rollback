<?php

/**
 * Base PluginSetup class for both Free and Pro plugins to extend.
 *
 * @package WpRollback\SharedCore\PluginSetup
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\PluginSetup;

/**
 * Base Plugin Setup Class
 *
 * @since 1.0.0
 */
abstract class PluginSetup
{
    /**
     * This flag is used to check if the service providers have been loaded.
     *
     * @since 1.0.0
     */
    protected bool $providersLoaded = false;

    /**
     * This is a list of service providers that will be loaded into the application.
     *
     * @since 1.0.0
     */
    protected array $serviceProviders = [];

    /**
     * Bootstraps the WP Rollback Plugin
     *
     * @since 1.0.0
     *
     * @throws \Exception
     */
    abstract public function boot(): void;

    /**
     * Initiate plugin when WordPress initializes plugins.
     *
     * @since 1.0.0
     */
    abstract public function init(): void;

    /**
     * This function is used to load service providers.
     *
     * @since 1.0.0
     */
    abstract protected function loadServiceProviders(): void;

    /**
     * Register external libraries
     *
     * @since 1.0.0
     */
    abstract protected function registerLibraries(): void;
} 