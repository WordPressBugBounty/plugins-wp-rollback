<?php

/**
 * @package WpRollback\SharedCore\Rollbacks\RollbackSteps
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Rollbacks\RollbackSteps;

use WpRollback\SharedCore\Rollbacks\DTO\RollbackApiRequestDTO;
use WpRollback\SharedCore\Rollbacks\Contract\RollbackStep;
use WpRollback\SharedCore\Rollbacks\Contract\RollbackStepResult;
use WpRollback\SharedCore\Rollbacks\Traits\PluginHelpers;

/**
 * @since 1.0.0
 */
class ReplaceAsset implements RollbackStep
{
    use PluginHelpers;

    /**
     * @inheritdoc
     * @since 1.0.0
     */
    public static function id(): string
    {
        return 'replace-asset';
    }

    /**
     * @inheritdoc
     * @since 1.0.0
     */
    public function execute(RollbackApiRequestDTO $rollbackApiRequestDTO): RollbackStepResult
    {
        $assetType = $rollbackApiRequestDTO->getType();
        $assetSlug = $rollbackApiRequestDTO->getSlug();
        $package = get_transient("wpr_{$assetType}_{$assetSlug}_package");

        // Get current version before replacement
        $currentVersion = $this->getCurrentVersion($assetType, $assetSlug);

        // Validate package
        $validationResult = $this->validatePackage($package, $rollbackApiRequestDTO);
        if (!$validationResult->isSuccess()) {
            return $validationResult;
        }

        // Setup filesystem
        $this->setupFilesystem();

        // Prepare destination and clean existing files
        $destination = $this->prepareDestination($assetType, $assetSlug);

        // Perform the rollback
        return $this->performRollback(
            $package,
            $destination,
            $assetType,
            $assetSlug,
            $currentVersion,
            $rollbackApiRequestDTO
        );
    }

    /**
     * Get current version based on asset type
     *
     * @since 1.0.0
     * @param string $assetType The type of asset (plugin/theme)
     * @param string $assetSlug The asset slug
     * @return string The current version
     */
    private function getCurrentVersion(string $assetType, string $assetSlug): string
    {
        return 'plugin' === $assetType
            ? $this->getCurrentPluginVersion($assetSlug)
            : $this->getCurrentThemeVersion($assetSlug);
    }

    /**
     * Get current plugin version
     *
     * @since 1.0.0
     * @param string $pluginSlug The plugin slug
     * @return string The current plugin version
     */
    private function getCurrentPluginVersion(string $pluginSlug): string
    {
        $this->loadPluginFunctions();
        $plugins = get_plugins();
        
        foreach ($plugins as $path => $data) {
            if (strpos((string) $path, $pluginSlug . '/') === 0) {
                return $data['Version'];
            }
        }

        return '';
    }

    /**
     * Get current theme version
     *
     * @since 1.0.0
     * @param string $themeSlug The theme slug
     * @return string The current theme version
     */
    private function getCurrentThemeVersion(string $themeSlug): string
    {
        $theme = wp_get_theme($themeSlug);
        return $theme->exists() ? $theme->get('Version') : '';
    }

    /**
     * Validate the downloaded package
     *
     * @since 1.0.0
     * @param string|\WP_Error $package The package file path or WP_Error
     * @param RollbackApiRequestDTO $rollbackApiRequestDTO The rollback request DTO
     * @return RollbackStepResult The validation result
     */
    private function validatePackage($package, RollbackApiRequestDTO $rollbackApiRequestDTO): RollbackStepResult
    {
        // Handle WP_Error case
        if (is_wp_error($package)) {
            return new RollbackStepResult(
                false,
                $rollbackApiRequestDTO,
                $package->get_error_message()
            );
        }

        if (!is_string($package) || !file_exists($package)) {
            return new RollbackStepResult(
                false,
                $rollbackApiRequestDTO,
                __('Downloaded package for rollback not found.', 'wp-rollback')
            );
        }

        include_once ABSPATH . 'wp-admin/includes/file.php';
        include_once ABSPATH . 'wp-admin/includes/misc.php';
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

        if (!wp_zip_file_is_valid($package)) {
            return new RollbackStepResult(
                false,
                $rollbackApiRequestDTO,
                __('Downloaded package for rollback is not a valid ZIP file.', 'wp-rollback')
            );
        }

        return new RollbackStepResult(true, $rollbackApiRequestDTO);
    }

    /**
     * Setup WordPress filesystem
     *
     * @since 1.0.0
     * @return void
     */
    private function setupFilesystem(): void
    {
        if (!defined('FS_METHOD')) {
            define('FS_METHOD', 'direct');
        }
    }

    /**
     * Prepare destination based on asset type
     *
     * @since 1.0.0
     * @param string $assetType The type of asset (plugin/theme)
     * @param string $assetSlug The asset slug
     * @return string The destination path
     */
    private function prepareDestination(string $assetType, string $assetSlug): string
    {
        return 'plugin' === $assetType
            ? $this->preparePluginDestination($assetSlug)
            : $this->prepareThemeDestination($assetSlug);
    }

    /**
     * Prepare plugin destination
     *
     * @since 1.0.0
     * @param string $pluginSlug The plugin slug
     * @return string The plugin destination path
     */
    private function preparePluginDestination(string $pluginSlug): string
    {
        $destination = WP_PLUGIN_DIR;
        $pluginDir = $destination . '/' . $pluginSlug;

        if (is_dir($pluginDir)) {
            $this->loadPluginFunctions();
            $pluginFile = $this->getPluginFileBySlug($pluginSlug);
            
            if ($pluginFile) {
                /**
                 * Filter whether to delete the existing plugin before rollback.
                 *
                 * @since 1.0.0
                 * @param bool   $shouldDelete Whether to delete the plugin
                 * @param string $pluginFile    The plugin file path
                 * @param string $pluginSlug    The plugin slug
                 */
                $shouldDelete = apply_filters('wpr_should_delete_existing_plugin', true, $pluginFile, $pluginSlug);
                
                if ($shouldDelete) {
                    // Store the plugin's active state before deactivation
                    $wasActive = is_plugin_active($pluginFile);
                    $wasNetworkActive = is_multisite() && is_plugin_active_for_network($pluginFile);
                    
                    // Store state in transient for reactivation after rollback
                    set_transient(
                        'wpr_plugin_was_active_' . $pluginSlug,
                        [
                            'was_active' => $wasActive,
                            'was_network_active' => $wasNetworkActive,
                            'plugin_file' => $pluginFile
                        ],
                        300 // 5 minute expiration
                    );
                    
                    // Deactivate the plugin if it's active to prevent fatal errors
                    // This is critical for plugins with autoloaders like WooCommerce
                    if ($wasActive) {
                        deactivate_plugins($pluginFile, true);
                    }
                    
                    // For multisite, also deactivate network-wide if needed
                    if ($wasNetworkActive) {
                        deactivate_plugins($pluginFile, true, true);
                    }
                    
                    delete_plugins([$pluginFile]);
                }
            }
        }

        return $destination;
    }

    /**
     * Prepare theme destination
     * 
     * @since 1.0.0
     * @param string $themeSlug The theme slug
     * @return string The theme destination path
     */
    private function prepareThemeDestination(string $themeSlug): string
    {
        $destination = get_theme_root();
        $themeDir = $destination . '/' . $themeSlug;

        if (is_dir($themeDir)) {
            include_once ABSPATH . 'wp-admin/includes/theme.php';
            
            // Check if this is the active theme or parent theme
            $currentTheme = get_stylesheet();
            $currentTemplate = get_template();
            $isActiveTheme = ($themeSlug === $currentTheme);
            $isActiveParentTheme = ($themeSlug === $currentTemplate);
            
            // Maintenance mode should handle the active theme case
            // The theme files will be replaced while active, just like Core does
            if ($isActiveTheme || $isActiveParentTheme) {
                // Store the active theme info in case folder name changes after rollback
                set_transient(
                    'wpr_theme_was_active_' . $themeSlug,
                    [
                        'was_active' => $isActiveTheme,
                        'was_parent' => $isActiveParentTheme,
                        'stylesheet' => $currentTheme,
                        'template' => $currentTemplate,
                        'original_slug' => $themeSlug
                    ],
                    300 // 5 minute expiration
                );
            }
            
            // Delete the theme files (like WordPress Core does)
            // Maintenance mode should be active if this is the active theme
            delete_theme($themeSlug);
        }

        return $destination;
    }

    /**
     * Perform the rollback operation
     *
     * @since 1.0.0
     * @param string $package The package file path
     * @param string $destination The destination path
     * @param string $assetType The type of asset (plugin/theme)
     * @param string $assetSlug The asset slug
     * @param string $currentVersion The current version before rollback
     * @param RollbackApiRequestDTO $rollbackApiRequestDTO The rollback request DTO
     * @return RollbackStepResult The rollback result
     */
    private function performRollback(
        string $package,
        string $destination,
        string $assetType,
        string $assetSlug,
        string $currentVersion,
        RollbackApiRequestDTO $rollbackApiRequestDTO
    ): RollbackStepResult {

        $result = unzip_file($package, $destination);

        if (is_wp_error($result)) {
            $errorMessage = __('Unable to unzip the downloaded package.', 'wp-rollback');
            return new RollbackStepResult(false, $rollbackApiRequestDTO, $errorMessage);
        }

        $fullAssetPath = $assetSlug;
        if ('plugin' === $assetType) {
            $fullAssetPath = $this->getPluginFileBySlug($assetSlug) ?: $fullAssetPath;
            
            // Attempt to reactivate the plugin if it was active before rollback
            $this->reactivatePluginIfNeeded($assetSlug);
        } elseif ('theme' === $assetType) {
            // Attempt to reactivate the theme if it was active before rollback
            $this->reactivateThemeIfNeeded($assetSlug);
        }

        return new RollbackStepResult(
            true, 
            $rollbackApiRequestDTO,
            __('Files replaced successfully.', 'wp-rollback'),
            null,
            [
                'asset_path' => $fullAssetPath,
                'current_version' => $currentVersion,
            ]
        );
    }

    /**
     * Reactivate plugin if it was active before rollback
     *
     * @since 1.0.0
     * @param string $pluginSlug The plugin slug
     * @return void
     */
    private function reactivatePluginIfNeeded(string $pluginSlug): void
    {
        $transientKey = 'wpr_plugin_was_active_' . $pluginSlug;
        $activeState = get_transient($transientKey);
        
        if (!$activeState || !is_array($activeState)) {
            return;
        }
        
        // Clean up the transient
        delete_transient($transientKey);
        
        // Get the plugin file - it might have changed after rollback
        $pluginFile = $this->getPluginFileBySlug($pluginSlug);
        if (!$pluginFile) {
            return;
        }
        
        // Only reactivate if not doing cron (following WordPress Core pattern)
        if (wp_doing_cron()) {
            return;
        }
        
        // Reactivate if it was active before
        if (!empty($activeState['was_active'])) {
            $networkWide = !empty($activeState['was_network_active']);
            
            // Silently activate to avoid running activation hooks that might fail
            // with the older version (following WordPress Core pattern)
            $result = activate_plugin($pluginFile, '', $networkWide, true);
            
            // If activation failed, store error for admin notice
            if (is_wp_error($result)) {
                set_transient(
                    'wpr_plugin_activation_failed_' . $pluginSlug,
                    [
                        'plugin' => $pluginFile,
                        'error' => $result->get_error_message()
                    ],
                    3600 // 1 hour expiration
                );
            }
        }
    }

    /**
     * Handle theme reactivation if needed after rollback
     *
     * Following WordPress Core pattern: only switch theme if the folder name changed
     * 
     * @since 1.0.0
     * @param string $themeSlug The theme slug after rollback
     * @return void
     */
    private function reactivateThemeIfNeeded(string $themeSlug): void
    {
        $transientKey = 'wpr_theme_was_active_' . $themeSlug;
        $activeState = get_transient($transientKey);
        
        if (!$activeState || !is_array($activeState)) {
            return;
        }
        
        // Clean up the transient
        delete_transient($transientKey);
        
        // Only proceed if not doing cron (following WordPress Core pattern)
        if (wp_doing_cron()) {
            return;
        }
        
        // Following WordPress Core approach:
        // Only switch theme if the stylesheet name changed after the rollback
        // This can happen if the theme folder name is different in the rolled-back version
        
        if (!empty($activeState['was_active'])) {
            $currentStylesheet = get_stylesheet();
            $originalSlug = $activeState['original_slug'] ?? $themeSlug;
            
            // Check if we're still on the temporary theme or folder name changed
            if ($currentStylesheet !== $themeSlug && $originalSlug === $activeState['stylesheet']) {
                // The theme folder name must have changed during rollback
                // Check if the rolled-back theme exists and is valid
                $theme = wp_get_theme($themeSlug);
                
                if ($theme->exists() && !$theme->errors()) {
                    // Clean theme cache to ensure WordPress sees the new theme
                    wp_clean_themes_cache();
                    
                    // Switch to the rolled-back theme with its new folder name
                    switch_theme($themeSlug);
                }
            }
        }
    }

    /**
     * @inheritdoc
     * @since 1.0.0
     */
    public static function rollbackProcessingMessage(): string
    {
        return esc_html__('Replacing files…', 'wp-rollback');
    }
} 