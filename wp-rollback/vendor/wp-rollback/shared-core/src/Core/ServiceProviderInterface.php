<?php

/**
 * @package WpRollback\SharedCore\Core
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Core;

/**
 * Interface for service providers.
 *
 */
interface ServiceProviderInterface
{
    /**
     * Register the service provider.
     *
     *
     * @return void
     */
    public function register(): void;

    /**
     * Boot the service provider.
     *
     *
     * @return void
     */
    public function boot(): void;
} 