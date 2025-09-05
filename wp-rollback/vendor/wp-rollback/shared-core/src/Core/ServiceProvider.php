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
use WpRollback\SharedCore\RestAPI\ArchivesController;

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
        // Register core shared services that don't depend on plugin-specific implementations
        
        // Register PackageValidationService for integrity verification using WordPress Core methods
        SharedCore::container()->singleton(PackageValidationService::class);

        // Register BackupService for creating asset backups
        SharedCore::container()->singleton(BackupService::class);

        // Register ArchivesController for REST API
        SharedCore::container()->singleton(ArchivesController::class);
    }

    /** @inheritDoc */
    public function boot(): void 
    {
        // Register REST routes for archives
        Hooks::addAction('rest_api_init', self::class, 'registerArchivesRoutes');
    }

    /**
     * Register REST routes for archives
     *
     * @since 1.0.0
     * @return void
     */
    public function registerArchivesRoutes(): void
    {
        $controller = SharedCore::container()->make(ArchivesController::class);
        $controller->registerRoutes();
    }
}