<?php

/**
 * Service Provider
 *
 * @package WpRollback\SharedCore\Core
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Core;

use WpRollback\SharedCore\Core\Container\Exceptions\BindingResolutionException;
use WpRollback\SharedCore\Rollbacks\Services\PackageValidationService;
use WpRollback\SharedCore\Rollbacks\Services\BackupService;
use WpRollback\SharedCore\Migrations\ServiceProvider as MigrationsServiceProvider;

/**
 * Class ServiceProvider
 *
 * @package WpRollback\SharedCore\Core
 */
class ServiceProvider implements Contracts\ServiceProvider
{
    /**
     * @var MigrationsServiceProvider|null
     */
    private ?MigrationsServiceProvider $migrationsServiceProvider = null;

    /**
     * Register services with the container.
     *
     * @throws BindingResolutionException
     */
    public function register(): void
    {
        // Register core shared services that don't depend on plugin-specific implementations
        
        // Register PackageValidationService for integrity verification using WordPress Core methods
        SharedCore::container()->singleton(PackageValidationService::class);

        // Register BackupService for creating asset backups
        SharedCore::container()->singleton(BackupService::class);

        // Register Migrations provider. It's registered and booted here only, so
        // plugins must not also list it in their own service providers.
        $this->migrationsServiceProvider = new MigrationsServiceProvider();
        $this->migrationsServiceProvider->register();
    }

    /** @inheritDoc */
    public function boot(): void 
    {
        if ($this->migrationsServiceProvider) {
            $this->migrationsServiceProvider->boot();
        }
    }
}