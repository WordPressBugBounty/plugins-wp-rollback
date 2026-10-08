<?php

/**
 * @package WpRollback\SharedCore\Rollbacks\RollbackSteps
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Rollbacks\RollbackSteps;

use WpRollback\SharedCore\Rollbacks\DTO\RollbackApiRequestDTO;
use WpRollback\SharedCore\Rollbacks\Contract\RollbackStep;
use WpRollback\SharedCore\Rollbacks\Contract\RollbackStepResult;
use WpRollback\SharedCore\Rollbacks\Traits\PluginHelpers;

/**
 */
class ReplaceAsset implements RollbackStep
{
    /**
     * Where the plugin or theme's current folder was moved while the new version
     * is unzipped, or null if it wasn't moved.
     */
    private ?string $_tempBackupDir = null;

    use PluginHelpers;

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return 'replace-asset';
    }

    /**
     * @inheritdoc
     */
    public function execute(RollbackApiRequestDTO $rollbackApiRequestDTO): RollbackStepResult
    {
        $this->_tempBackupDir = null;

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

        // Set up the filesystem before any files are touched, so a host that
        // needs credentials fails here instead of after deactivating the plugin.
        if (!$this->setupFilesystem()) {
            return new RollbackStepResult(
                false,
                $rollbackApiRequestDTO,
                __('Unable to access the filesystem to replace the files.', 'wp-rollback')
            );
        }

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
     * Set up WordPress's filesystem API for this step.
     *
     * Deleting the old files, unzip_file() and wp_opcache_invalidate_directory()
     * all use the global $wp_filesystem, so it's set up once here for all of them.
     *
     * @return bool Whether the filesystem is available.
     */
    private function setupFilesystem(): bool
    {
        if (!defined('FS_METHOD')) {
            define('FS_METHOD', 'direct');
        }

        return (bool) WP_Filesystem();
    }

    /**
     * Move a plugin or theme folder to WordPress core's temporary backup location.
     *
     * Core's updater uses the same places (wp-content/upgrade-temp-backup/plugins
     * and wp-content/upgrade-temp-backup/themes) and its weekly cleanup removes
     * anything left there.
     *
     * @param string $assetDir  Absolute path of the plugin or theme folder.
     * @param string $assetType The type of asset (plugin/theme).
     * @param string $assetSlug The asset slug.
     * @return string|null Where the folder was moved, or null if it couldn't be moved.
     */
    private function moveToTempBackup(string $assetDir, string $assetType, string $assetSlug): ?string
    {
        $backupRoot = trailingslashit(WP_CONTENT_DIR) . 'upgrade-temp-backup/' . ('theme' === $assetType ? 'themes' : 'plugins');
        if (!wp_mkdir_p($backupRoot)) {
            return null;
        }

        $backupDir = $backupRoot . '/' . $assetSlug;

        return true === move_dir($assetDir, $backupDir, true) ? $backupDir : null;
    }

    /**
     * Put the folder moved aside by moveToTempBackup() back in place.
     *
     * @param string $assetDir Absolute path the plugin or theme folder belongs at.
     * @return bool Whether it was put back.
     */
    private function restoreTempBackup(string $assetDir): bool
    {
        if (null === $this->_tempBackupDir) {
            return false;
        }

        // Overwriting also removes anything a failed unzip left behind.
        $restored = true === move_dir($this->_tempBackupDir, $assetDir, true);
        $this->_tempBackupDir = null;

        if ($restored) {
            wp_opcache_invalidate_directory($assetDir);
        }

        return $restored;
    }

    /**
     * Delete the folder moved aside by moveToTempBackup(), once the new version is in place.
     */
    private function deleteTempBackup(): void
    {
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps -- WordPress core global
        global $wp_filesystem;

        if (null !== $this->_tempBackupDir) {
            // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps -- WordPress core global
            $wp_filesystem->delete($this->_tempBackupDir, true);
            $this->_tempBackupDir = null;
        }
    }

    /**
     * Delete plugin or theme files using WP_Filesystem directly, bypassing
     * delete_plugins() and delete_theme().
     *
     * WordPress's delete_plugins() triggers uninstall_plugin() which runs a plugin's
     * uninstall.php or registered uninstall hook, deleting user data, and delete_theme()
     * also deletes the theme's translations. During a rollback we only want to remove
     * files — matching how WordPress core's upgraders handle updates via
     * WP_Upgrader::clear_destination().
     *
     * @param string $assetDir Absolute path to the plugin or theme directory to remove.
     * @return bool Whether the deletion was successful.
     */
    private function deleteAssetFiles(string $assetDir): bool
    {
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps -- WordPress core global
        global $wp_filesystem;

        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps -- WordPress core global
        if ($wp_filesystem->is_dir($assetDir)) {
            // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps -- WordPress core global
            return $wp_filesystem->delete($assetDir, true);
        }

        return true;
    }

    /**
     * Prepare destination based on asset type
     *
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
                 * Filter whether to replace the existing plugin folder before rollback.
                 *
                 * When false, the plugin stays active and the new version is unzipped
                 * over its folder, so files only the current version has are left behind.
                 *
                 * @param bool   $shouldDelete Whether to replace the plugin folder
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
                    
                    // Like WordPress core's updater, move the current folder aside rather
                    // than deleting it, so it can be put back if unzipping fails.
                    $this->_tempBackupDir = $this->moveToTempBackup($pluginDir, 'plugin', $pluginSlug);
                    if (null === $this->_tempBackupDir) {
                        $this->deleteAssetFiles($pluginDir);
                    }
                }
            }
        }

        return $destination;
    }

    /**
     * Prepare theme destination
     * 
     * @param string $themeSlug The theme slug
     * @return string The theme destination path
     */
    private function prepareThemeDestination(string $themeSlug): string
    {
        $destination = get_theme_root();
        $themeDir = $destination . '/' . $themeSlug;

        if (is_dir($themeDir)) {
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
            
            // Like WordPress core's updater, move the current folder aside rather
            // than deleting it, so it can be put back if unzipping fails.
            // Maintenance mode should be active if this is the active theme
            $this->_tempBackupDir = $this->moveToTempBackup($themeDir, 'theme', $themeSlug);
            if (null === $this->_tempBackupDir) {
                $this->deleteAssetFiles($themeDir);
            }
        }

        return $destination;
    }

    /**
     * Perform the rollback operation
     *
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
            if ($this->restoreTempBackup(trailingslashit($destination) . $assetSlug)) {
                if ('plugin' === $assetType) {
                    $this->reactivatePluginIfNeeded($assetSlug);
                } elseif ('theme' === $assetType) {
                    // The theme was never switched away from, so this only clears
                    // its saved state before a later rollback can act on it.
                    $this->reactivateThemeIfNeeded($assetSlug);
                }

                return new RollbackStepResult(
                    false,
                    $rollbackApiRequestDTO,
                    __('Unable to unzip the downloaded package. The version you had has been put back.', 'wp-rollback')
                );
            }

            $errorMessage = __('Unable to unzip the downloaded package.', 'wp-rollback');
            return new RollbackStepResult(false, $rollbackApiRequestDTO, $errorMessage);
        }

        $this->deleteTempBackup();

        // unzip_file() writes straight into the plugins/themes folder, so PHP's
        // opcode cache can keep serving the replaced version's compiled files
        // until it revalidates. The next request then runs a mix of old and new
        // code, and fatals if the old code loads a file the rollback removed
        // (e.g. FooGallery 3.3.7 to 3.3.3 drops lib/action-scheduler). WordPress
        // core invalidates after copy_dir()/move_dir() during updates; do the same.
        wp_opcache_invalidate_directory(trailingslashit($destination) . $assetSlug);

        $fullAssetPath = $assetSlug;
        if ('plugin' === $assetType) {
            $fullAssetPath = $this->getPluginFileBySlug($assetSlug) ?: $fullAssetPath;
            
            // Attempt to reactivate the plugin if it was active before rollback
            $this->reactivatePluginIfNeeded($assetSlug);
        } elseif ('theme' === $assetType) {
            // Clear the cached theme headers and update data, like WordPress
            // core's theme updater, so nothing reads the replaced version.
            wp_clean_themes_cache();

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
     */
    public static function rollbackProcessingMessage(): string
    {
        return esc_html__('Replacing files…', 'wp-rollback');
    }
} 