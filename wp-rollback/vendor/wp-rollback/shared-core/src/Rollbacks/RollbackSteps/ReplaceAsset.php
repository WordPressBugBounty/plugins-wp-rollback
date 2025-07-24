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
     * Get theme directory path by slug.
     *
     * @since 1.0.0
     * @param string $themeSlug The theme slug
     * @return string The theme directory path
     */
    private function getThemePathBySlug(string $themeSlug): string
    {
        $theme = wp_get_theme($themeSlug);
        return $theme->exists() ? $theme->get_stylesheet_directory() : '';
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
     * @inheritdoc
     * @since 1.0.0
     */
    public static function rollbackProcessingMessage(): string
    {
        return esc_html__('Replacing files…', 'wp-rollback');
    }
} 