<?php

/**
 * Shared Rollback Service Provider
 *
 * This service provider handles common rollback-related service registrations
 * that are shared between the free and pro plugins.
 *
 * @package WpRollback\SharedCore\Rollbacks
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Rollbacks;

use WpRollback\SharedCore\Core\Exceptions\BindingResolutionException;
use WpRollback\SharedCore\Core\Contracts\ServiceProvider as ServiceProviderContract;
use WpRollback\SharedCore\Core\SharedCore;
use WpRollback\SharedCore\Rollbacks\Registry\RollbackStepRegisterer;
use WpRollback\SharedCore\Rollbacks\RollbackSteps\DownloadAsset;
use WpRollback\SharedCore\Rollbacks\RollbackSteps\BackupAsset;
use WpRollback\SharedCore\Rollbacks\RollbackSteps\ValidatePackage;
use WpRollback\SharedCore\Rollbacks\RollbackSteps\ReplaceAsset;
use WpRollback\SharedCore\Rollbacks\RollbackSteps\Cleanup;
use WpRollback\SharedCore\Rollbacks\Services\PackageValidationService;
use WpRollback\SharedCore\Rollbacks\Services\BackupService;
use WpRollback\SharedCore\Rollbacks\ToolsPage\ToolsPage;

/**
 * Class ServiceProvider
 *
 * @since 1.0.0
 */
class ServiceProvider implements ServiceProviderContract
{
    /**
     * @inheritdoc
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function register(): void
    {
        // Register ToolsPage - shared between free and pro
        SharedCore::container()->singleton(ToolsPage::class);

        // Register ValidatePackage step with PackageValidationService dependency
        // This is identical in both free and pro plugins
        SharedCore::container()->singleton(ValidatePackage::class, function ($container) {
            return new ValidatePackage($container->make(PackageValidationService::class));
        });

        // Register BackupAsset step with BackupService dependency
        // This is identical in both free and pro plugins
        SharedCore::container()->singleton(BackupAsset::class, function ($container) {
            return new BackupAsset($container->make(BackupService::class));
        });

        // Register base RollbackStepRegisterer with common steps
        // Plugins can extend this by adding additional steps
        SharedCore::container()->singleton(RollbackStepRegisterer::class, function () {
            $registerer = new RollbackStepRegisterer();
            $registerer->addStep(DownloadAsset::class);
            $registerer->addStep(BackupAsset::class);
            $registerer->addStep(ValidatePackage::class);
            $registerer->addStep(ReplaceAsset::class);
            $registerer->addStep(Cleanup::class);
            return $registerer;
        });
    }

    /**
     * @inheritdoc
     * @since 1.0.0
     */
    public function boot(): void
    {
        // Initialize backup service and set up rollback directory for shared functionality
        try {
            $backupService = SharedCore::container()->make(BackupService::class);
            $backupService->setupRollbackDirectory();
            
            // Register WordPress hooks for backup functionality - shared by both free and pro
            $this->registerBackupHooks($backupService);
        } catch (\RuntimeException $e) {
            // Log error but continue
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log(sprintf('[WP Rollback Shared] Failed to set up rollback directory: %s', $e->getMessage()));
            }
        }
    }

    /**
     * Register WordPress hooks for backup functionality.
     * These hooks are shared between free and pro plugins.
     *
     * @since 1.0.0
     * @param BackupService $backupService The backup service instance
     */
    private function registerBackupHooks(BackupService $backupService): void
    {
        // Register the critical upgrader hook that intercepts WordPress updates
        add_filter('upgrader_package_options', function($options) use ($backupService) {
            return $backupService->interceptUpgrade($options);
        }, 10, 1);
        
        // Register hooks for rollback request data modification
        add_filter('wpr_rollback_api_request_data', function($data, $context) use ($backupService) {
            return $backupService->modifyRollbackRequestData($data, $context);
        }, 10, 2);
        
        // Register hook to check if asset has backup versions
        add_filter('wpr_is_pro_rollback', function($isPro, $slug) use ($backupService) {
            return $backupService->hasBackupVersions($isPro, $slug);
        }, 10, 2);
        
        // Register hook to get available backup versions
        add_filter('wpr_get_pro_versions', function($versions, $slug) use ($backupService) {
            return $backupService->getAvailableVersions($versions, $slug);
        }, 10, 2);
        
        // Register hook to control asset deletion during rollback
        add_filter('wpr_should_delete_existing_plugin', function($shouldDelete, $pluginFile, $pluginSlug) use ($backupService) {
            return $backupService->shouldDeleteExistingAsset($shouldDelete, $pluginFile, $pluginSlug);
        }, 10, 3);
    }
} 