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
use WpRollback\SharedCore\Core\Exceptions\Primitives\Exception;
use WpRollback\SharedCore\Core\Contracts\ServiceProvider as ServiceProviderContract;
use WpRollback\SharedCore\Core\Hooks;
use WpRollback\SharedCore\Core\SharedCore;
use WpRollback\SharedCore\Rollbacks\Registry\RollbackStepRegisterer;
use WpRollback\SharedCore\Rollbacks\RollbackSteps\MaintenanceMode;
use WpRollback\SharedCore\Rollbacks\RollbackSteps\BackupAsset;
use WpRollback\SharedCore\Rollbacks\RollbackSteps\ValidatePackage;
use WpRollback\SharedCore\Rollbacks\RollbackSteps\Cleanup;
use WpRollback\SharedCore\Rollbacks\Services\PackageValidationService;
use WpRollback\SharedCore\Rollbacks\Services\BackupService;
use WpRollback\SharedCore\Rollbacks\Services\MaintenanceService;
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

        // Register MaintenanceService
        SharedCore::container()->singleton(MaintenanceService::class);

        // Register MaintenanceMode step with MaintenanceService dependency
        // This step enables maintenance mode at the beginning of rollback
        SharedCore::container()->singleton(MaintenanceMode::class, function ($container) {
            return new MaintenanceMode($container->make(MaintenanceService::class));
        });

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

        // Register Cleanup step with MaintenanceService dependency
        // This step ensures maintenance mode is always disabled
        SharedCore::container()->singleton(Cleanup::class, function ($container) {
            return new Cleanup($container->make(MaintenanceService::class));
        });

        // Register base RollbackStepRegisterer only if not already registered
        // This allows plugins to customize while providing a sensible default
        if (!SharedCore::container()->has(RollbackStepRegisterer::class)) {            
            SharedCore::container()->singleton(RollbackStepRegisterer::class, function () {
                $registerer = new RollbackStepRegisterer();
                // Register base steps and add ValidatePackage (pro feature)
                $registerer->register(RollbackStepRegisterer::getBaseSteps());
                $registerer->registerAfter(ValidatePackage::class, BackupAsset::class);
                return $registerer;
            });
        } 
    }

    /**
     * @inheritdoc
     * @since 1.0.0
     */
    public function boot(): void
    {
        // Initialize maintenance service to handle maintenance mode checks
        try {
            $maintenanceService = SharedCore::container()->make(MaintenanceService::class);
            $maintenanceService->init();
        } catch (\Exception $e) {
            // Log error but continue
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log(sprintf('[WP Rollback Shared] Failed to initialize maintenance service: %s', $e->getMessage()));
            }
        }

        // Initialize backup service and set up rollback directory for shared functionality
        try {
            $backupService = SharedCore::container()->make(BackupService::class);
            $backupService->setupRollbackDirectory();
            
            // Register WordPress hooks for backup functionality - shared by both free and pro
            $this->registerBackupHooks($backupService);
        } catch (Exception $e) {
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
        
        // Multisite: Register filters for rollback operations
        if (is_multisite()) {
            Hooks::addFilter('upload_mimes', self::class, 'filterUploadMimes', 10, 1);
            Hooks::addFilter('pre_site_option_upload_filetypes', self::class, 'filterUploadFileTypes', 10, 1);
            Hooks::addFilter('upload_size_limit', self::class, 'filterUploadSizeLimit', 999, 1);
        }
    }
    
    /**
     * Filter upload MIME types to allow ZIP files during rollback operations.
     *
     * @since 1.0.0
     * @param array $mimes Array of allowed MIME types.
     * @return array Modified array of allowed MIME types.
     */
    public static function filterUploadMimes($mimes): array
    {
        // Only add ZIP support during rollback operations
        if (defined('WPR_ROLLBACK_ACTIVE') || did_action('wp_ajax_wpr_process_rollback')) {
            $mimes['zip'] = 'application/zip';
        }
        return $mimes;
    }
    
    /**
     * Filter allowed upload file types for multisite during rollback operations.
     *
     * @since 1.0.0
     * @param string|false $filetypes Space-separated list of allowed file extensions.
     * @return string|false Modified list of allowed file extensions.
     */
    public static function filterUploadFileTypes($filetypes)
    {
        if ((defined('WPR_ROLLBACK_ACTIVE') || did_action('wp_ajax_wpr_process_rollback')) && is_super_admin()) {
            // Ensure zip is in the allowed file types list
            $allowed = explode(' ', $filetypes ?: '');
            if (!in_array('zip', $allowed, true)) {
                $allowed[] = 'zip';
                return implode(' ', array_filter($allowed));
            }
        }
        return $filetypes;
    }
    
    /**
     * Filter upload size limit for multisite during rollback operations.
     *
     * @since 1.0.0
     * @param int $size Maximum upload size in bytes.
     * @return int Modified maximum upload size in bytes.
     */
    public static function filterUploadSizeLimit($size): int
    {
        if ((defined('WPR_ROLLBACK_ACTIVE') || did_action('wp_ajax_wpr_process_rollback')) && is_super_admin()) {
            // Set a reasonable limit for plugin/theme rollbacks (100MB)
            return max($size, 104857600);
        }
        return $size;
    }
} 