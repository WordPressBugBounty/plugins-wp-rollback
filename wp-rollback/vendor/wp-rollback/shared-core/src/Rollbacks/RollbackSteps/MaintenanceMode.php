<?php

/**
 * Maintenance mode rollback step.
 *
 * @package WpRollback\SharedCore\Rollbacks\RollbackSteps
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Rollbacks\RollbackSteps;

use WpRollback\SharedCore\Core\Utilities\PluginUtility;
use WpRollback\SharedCore\Rollbacks\Services\MaintenanceService;
use WpRollback\SharedCore\Rollbacks\Services\NetworkAssetUsageService;
use WpRollback\SharedCore\Rollbacks\DTO\RollbackApiRequestDTO;
use WpRollback\SharedCore\Rollbacks\Contract\RollbackStep;
use WpRollback\SharedCore\Rollbacks\Contract\RollbackStepResult;

/**
 * Rollback step for enabling maintenance mode during rollback process
 *
 */
class MaintenanceMode implements RollbackStep
{
    /**
     * Maintenance service instance
     *
     * @var MaintenanceService
     */
    private MaintenanceService $maintenanceService;

    /**
     * Checks whether other sites on a network use the asset
     *
     * @var NetworkAssetUsageService
     */
    private NetworkAssetUsageService $networkAssetUsage;

    /**
     * Constructor
     *
     * @param MaintenanceService       $maintenanceService The maintenance service
     * @param NetworkAssetUsageService $networkAssetUsage  Checks whether other sites on a network use the asset
     */
    public function __construct(MaintenanceService $maintenanceService, NetworkAssetUsageService $networkAssetUsage)
    {
        $this->maintenanceService = $maintenanceService;
        $this->networkAssetUsage = $networkAssetUsage;
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return 'maintenance-mode';
    }

    /**
     * @inheritdoc
     */
    public function execute(RollbackApiRequestDTO $rollbackApiRequestDTO): RollbackStepResult
    {
        $assetType = $rollbackApiRequestDTO->getType();
        $assetSlug = $rollbackApiRequestDTO->getSlug();
        $assetVersion = $rollbackApiRequestDTO->getVersion();

        // Check if maintenance mode is already active
        if ($this->maintenanceService->isMaintenanceModeActive()) {
            return new RollbackStepResult(
                true,
                $rollbackApiRequestDTO,
                __('Maintenance mode is already active.', 'wp-rollback'),
                null,
                [
                    'maintenance_status' => 'already_active',
                    'asset_type' => $assetType,
                    'asset_slug' => $assetSlug,
                    'asset_version' => $assetVersion
                ]
            );
        }
        
        // Only enable maintenance mode for active plugins/themes
        $shouldEnableMaintenance = $this->shouldEnableMaintenanceMode($assetType, $assetSlug);
        
        if (!$shouldEnableMaintenance) {
            return new RollbackStepResult(
                true,
                $rollbackApiRequestDTO,
                __('Maintenance mode not needed for inactive asset.', 'wp-rollback'),
                null,
                [
                    'maintenance_status' => 'skipped_inactive',
                    'asset_type' => $assetType,
                    'asset_slug' => $assetSlug,
                    'asset_version' => $assetVersion
                ]
            );
        }

        // Enable maintenance mode
        $enabled = $this->maintenanceService->enableMaintenanceMode();

        if (!$enabled) {
            return new RollbackStepResult(
                true, // Continue with rollback even if maintenance mode fails
                $rollbackApiRequestDTO,
                __('Could not enable maintenance mode, but continuing with rollback.', 'wp-rollback'),
                null,
                [
                    'maintenance_status' => 'failed_non_critical',
                    'asset_type' => $assetType,
                    'asset_slug' => $assetSlug,
                    'asset_version' => $assetVersion
                ]
            );
        }

        // Store maintenance mode state in transient for cleanup tracking
        set_transient(
            "wpr_maintenance_mode_{$assetType}_{$assetSlug}",
            [
                'enabled_at' => time(),
                'version' => $assetVersion,
                'process_id' => uniqid('wpr_', true)
            ],
            600 // 10 minutes expiration
        );

        return new RollbackStepResult(
            true,
            $rollbackApiRequestDTO,
            __('Maintenance mode enabled successfully.', 'wp-rollback'),
            null,
            [
                'maintenance_status' => 'enabled',
                'asset_type' => $assetType,
                'asset_slug' => $assetSlug,
                'asset_version' => $assetVersion,
                'enabled_at' => time()
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public static function rollbackProcessingMessage(): string
    {
        return esc_html__('Enabling maintenance mode…', 'wp-rollback');
    }

    /**
     * Check if maintenance mode should be enabled for the asset
     * Only enable for active plugins and themes
     *
     * @param string $assetType The asset type (plugin or theme)
     * @param string $assetSlug The asset slug
     * @return bool True if maintenance mode should be enabled
     */
    private function shouldEnableMaintenanceMode(string $assetType, string $assetSlug): bool
    {
        if ('plugin' === $assetType) {
            if (PluginUtility::listIncludesPlugin((array) get_option('active_plugins', []), $assetSlug)) {
                return true;
            }

            if (!is_multisite()) {
                return false;
            }

            $networkPlugins = array_keys((array) get_site_option('active_sitewide_plugins', []));
            if (PluginUtility::listIncludesPlugin($networkPlugins, $assetSlug)) {
                return true;
            }

            // Every site on a network loads its plugins from the same folder, so check the other sites too
            return $this->networkAssetUsage->isPluginActiveOnOtherSites($assetSlug);
        }
        
        if ('theme' === $assetType) {
            // Check if theme is currently active
            $current_theme = wp_get_theme();
            if ($current_theme->get_stylesheet() === $assetSlug) {
                return true;
            }
            
            // Check parent theme if using child theme
            if ($current_theme->parent()) {
                $parent_theme = $current_theme->parent();
                if ($parent_theme->get_stylesheet() === $assetSlug) {
                    return true;
                }
            }
            
            // Every site on a network loads its theme from the same folder, so check the other sites too
            if (is_multisite()) {
                return $this->networkAssetUsage->isThemeUsedOnOtherSites($assetSlug);
            }
            
            return false;
        }
        
        // Unknown asset type, don't enable maintenance mode
        return false;
    }
}
