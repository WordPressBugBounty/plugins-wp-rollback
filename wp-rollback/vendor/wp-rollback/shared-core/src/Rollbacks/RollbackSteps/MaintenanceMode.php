<?php

/**
 * Maintenance mode rollback step.
 *
 * @package WpRollback\SharedCore\Rollbacks\RollbackSteps
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Rollbacks\RollbackSteps;

use WpRollback\SharedCore\Rollbacks\Services\MaintenanceService;
use WpRollback\SharedCore\Rollbacks\DTO\RollbackApiRequestDTO;
use WpRollback\SharedCore\Rollbacks\Contract\RollbackStep;
use WpRollback\SharedCore\Rollbacks\Contract\RollbackStepResult;

/**
 * Rollback step for enabling maintenance mode during rollback process
 *
 * @since 1.0.0
 */
class MaintenanceMode implements RollbackStep
{
    /**
     * Maintenance service instance
     *
     * @since 1.0.0
     * @var MaintenanceService
     */
    private MaintenanceService $maintenanceService;

    /**
     * Constructor
     *
     * @since 1.0.0
     * @param MaintenanceService $maintenanceService The maintenance service
     */
    public function __construct(MaintenanceService $maintenanceService)
    {
        $this->maintenanceService = $maintenanceService;
    }

    /**
     * @inheritdoc
     * @since 1.0.0
     */
    public static function id(): string
    {
        return 'maintenance-mode';
    }

    /**
     * @inheritdoc
     * @since 1.0.0
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
     * @since 1.0.0
     */
    public static function rollbackProcessingMessage(): string
    {
        return esc_html__('Enabling maintenance mode…', 'wp-rollback');
    }

    /**
     * Check if maintenance mode should be enabled for the asset
     * Only enable for active plugins and themes
     *
     * @since 1.0.0
     * @param string $assetType The asset type (plugin or theme)
     * @param string $assetSlug The asset slug
     * @return bool True if maintenance mode should be enabled
     */
    private function shouldEnableMaintenanceMode(string $assetType, string $assetSlug): bool
    {
        if ('plugin' === $assetType) {
            // Check if plugin is active
            if (!function_exists('is_plugin_active')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            
            // For plugins, the slug might be the full plugin path or just the directory
            // First, try with the slug as-is (could be full path like 'plugin-dir/plugin-file.php')
            if (is_plugin_active($assetSlug)) {
                return true;
            }
            
            // If not found, check all active plugins to see if any start with this slug
            $active_plugins = get_option('active_plugins', []);
            foreach ($active_plugins as $plugin) {
                // Check if the plugin file starts with our slug directory
                if (strpos($plugin, $assetSlug . '/') === 0) {
                    return true;
                }
                
                // Also check if the plugin file path matches when we add common file patterns
                $common_files = [$assetSlug . '.php', 'plugin.php', 'index.php', 'main.php'];
                foreach ($common_files as $file) {
                    if ($plugin === $assetSlug . '/' . $file) {
                        return true;
                    }
                }
            }
            
            // Check network activated plugins for multisite
            if (is_multisite()) {
                $network_plugins = get_site_option('active_sitewide_plugins', []);
                foreach ($network_plugins as $plugin => $time) {
                    if ($plugin === $assetSlug || strpos($plugin, $assetSlug . '/') === 0) {
                        return true;
                    }
                }
            }
            
            return false;
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
            
            // Check network enabled themes for multisite
            if (is_multisite()) {
                $allowed_themes = get_site_option('allowedthemes', []);
                if (isset($allowed_themes[$assetSlug]) && $allowed_themes[$assetSlug]) {
                    // Check if any site is using this theme
                    global $wpdb;
                    $sites_using_theme = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name = 'stylesheet' AND option_value = %s",
                        $assetSlug
                    ));
                    
                    if ($sites_using_theme > 0) {
                        return true;
                    }
                }
            }
            
            return false;
        }
        
        // Unknown asset type, don't enable maintenance mode
        return false;
    }
}
