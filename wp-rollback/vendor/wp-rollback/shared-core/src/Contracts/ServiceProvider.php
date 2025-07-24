<?php

/**
 * @package WpRollback\SharedCore\Contracts
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Contracts;

/**
 * Interface for service providers.
 *
 * @since 1.0.0
 */
interface ServiceProvider
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