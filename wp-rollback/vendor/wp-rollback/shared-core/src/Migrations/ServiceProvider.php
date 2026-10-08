<?php

/**
 * Migration Service Provider
 *
 * Registers migration services, REST routes, and triggers pending migrations.
 *
 * @package WpRollback\SharedCore\Migrations
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Migrations;

use WpRollback\SharedCore\Core\Contracts\ServiceProvider as ServiceProviderContract;
use WpRollback\SharedCore\Core\Hooks;
use WpRollback\SharedCore\Core\SharedCore;
use WpRollback\SharedCore\Migrations\Tasks\SecureBackupDirectory;
use WpRollback\SharedCore\RestAPI\MigrationsController;

/**
 * Class ServiceProvider
 */
class ServiceProvider implements ServiceProviderContract
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        // Register MigrationRepository
        SharedCore::container()->singleton(MigrationRepository::class);

        // Register MigrationManager with MigrationRepository dependency
        SharedCore::container()->singleton(MigrationManager::class, function ($container) {
            $manager = new MigrationManager($container->make(MigrationRepository::class));

            // Register built-in migration tasks by FQCN string
            $manager->registerMigrations([
                SecureBackupDirectory::class,
            ]);

            return $manager;
        });

        // Register MigrationsController for REST API
        SharedCore::container()->singleton(MigrationsController::class);
    }

    /**
     * @inheritDoc
     */
    public function boot(): void
    {
        Hooks::addAction('admin_init', self::class, 'runPendingMigrations');

        Hooks::addAction('wp_rollback_upgraded', self::class, 'runPendingMigrations');
        Hooks::addAction('wp_rollback_installed', self::class, 'handleFreshInstall');

        Hooks::addAction('rest_api_init', self::class, 'registerRestRoutes');
    }

    /**
     * Callback for wp_rollback_installed to autocomplete legacy migrations on fresh install.
     *
     * @return void
     */
    public function handleFreshInstall(): void
    {
        /** @var MigrationManager $manager */
        $manager = SharedCore::container()->make(MigrationManager::class);
        $manager->autocompleteMigrationsForFreshInstall();
        $manager->runPendingMigrations();
    }

    /**
     * Callback for admin_init to run pending migrations.
     *
     * @return void
     */
    public function runPendingMigrations(): void
    {
        /** @var MigrationManager $manager */
        $manager = SharedCore::container()->make(MigrationManager::class);
        $manager->runPendingMigrations();
    }

    /**
     * Callback for rest_api_init to register REST API routes.
     *
     * @return void
     */
    public function registerRestRoutes(): void
    {
        /** @var MigrationsController $controller */
        $controller = SharedCore::container()->make(MigrationsController::class);
        $controller->registerRoutes();
    }
}
