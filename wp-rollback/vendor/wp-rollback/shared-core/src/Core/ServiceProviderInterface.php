<?php

/**
 * @package WpRollback\SharedCore\Core
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Core;

/**
 * Interface for service providers.
 *
 * @since 1.0.0
 */
interface ServiceProviderInterface
{
    /**
     * Register the service provider.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register(): void;

    /**
     * Boot the service provider.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function boot(): void;
} 