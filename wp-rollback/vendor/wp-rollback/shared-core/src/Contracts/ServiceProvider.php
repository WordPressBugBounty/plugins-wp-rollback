<?php

/**
 * @package WpRollback\SharedCore\Contracts
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Contracts;

/**
 * Interface for service providers.
 *
 */
interface ServiceProvider
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