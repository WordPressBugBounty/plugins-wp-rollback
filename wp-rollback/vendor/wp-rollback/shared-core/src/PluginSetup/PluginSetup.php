<?php

/**
 * Base PluginSetup class for both Free and Pro plugins to extend.
 *
 * @package WpRollback\SharedCore\PluginSetup
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\PluginSetup;

/**
 * Base Plugin Setup Class
 *
 */
abstract class PluginSetup
{
    /**
     * This flag is used to check if the service providers have been loaded.
     *
     */
    protected bool $providersLoaded = false;

    /**
     * This is a list of service providers that will be loaded into the application.
     *
     */
    protected array $serviceProviders = [];

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
     * This function is used to load service providers.
     *
     */
    abstract protected function loadServiceProviders(): void;

    /**
     * Register external libraries
     *
     */
    abstract protected function registerLibraries(): void;
} 