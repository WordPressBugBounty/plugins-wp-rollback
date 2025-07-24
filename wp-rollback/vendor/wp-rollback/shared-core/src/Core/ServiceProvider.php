<?php

/**
 * Service Provider
 *
 * @package WpRollback\SharedCore\Core
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Core;

use WpRollback\SharedCore\Core\Container\Exceptions\BindingResolutionException;
use WpRollback\SharedCore\Rollbacks\Services\PackageValidationService;
use WpRollback\SharedCore\Rollbacks\Services\BackupService;
use WpRollback\SharedCore\Rollbacks\ServiceProvider as RollbackServiceProvider;

/**
 * Class ServiceProvider
 *
 * @package WpRollback\SharedCore\Core
 * @since 1.0.0
 */
class ServiceProvider implements Contracts\ServiceProvider
{
    /**
     * Register services with the container.
     *
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function register(): void
    {
        SharedCore::container()->singleton(Request::class);
        SharedCore::container()->singleton(Cache::class);
        SharedCore::container()->singleton(DebugMode::class, function () {
            return DebugMode::makeWithWpDebugConstant();
        });

        // Bind BaseConstants to defer to the plugin's implementation
        SharedCore::container()->singleton(BaseConstants::class, function () {
            // This will be overridden by the plugin's ServiceProvider
            throw new BindingResolutionException('BaseConstants must be bound by the plugin\'s ServiceProvider');
        });

        // Register PackageValidationService for integrity verification using WordPress Core methods
        SharedCore::container()->singleton(PackageValidationService::class);

        // Register BackupService for creating asset backups
        SharedCore::container()->singleton(BackupService::class);

        // Register and boot the shared rollback service provider
        $rollbackServiceProvider = new RollbackServiceProvider();
        $rollbackServiceProvider->register();
    }

    /** @inheritDoc */
    public function boot(): void 
    {
        // Boot the shared rollback service provider
        $rollbackServiceProvider = new RollbackServiceProvider();
        $rollbackServiceProvider->boot();
    }
} 