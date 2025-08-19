<?php

/**
 * Backup service for creating and managing asset backups.
 *
 * @package WpRollback\SharedCore\Rollbacks\Services
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Rollbacks\Services;

use WP_Filesystem_Direct;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use WP_REST_Request;

/**
 * Service for creating and managing asset backups
 *
 * @since 1.0.0
 */
class BackupService
{
    /**
     * @var string Directory path for rollback files
     */
    private string $rollbackDir;

    /**
     * @var WP_Filesystem_Direct|null WordPress filesystem
     */
    private ?WP_Filesystem_Direct $filesystem = null;

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $uploadDir = wp_upload_dir();
        $this->rollbackDir = trailingslashit($uploadDir['basedir']) . 'wp-rollback';
    }

    /**
     * Set up the rollback directory.
     *
     * @since 1.0.0
     * @throws \RuntimeException If directory creation fails
     */
    public function setupRollbackDirectory(): void
    {
        $this->initializeFilesystem();

        // Create directory if it doesn't exist
        if (!$this->filesystem->is_dir($this->rollbackDir)) {
            if (!$this->filesystem->mkdir($this->rollbackDir)) {
                throw new \RuntimeException('Failed to create rollback directory.');
            }

            // Create an index.php file for security
            $this->filesystem->put_contents(
                $this->rollbackDir . '/index.php',
                '<?php // Silence is golden'
            );

            // Create .htaccess to prevent direct access
            $this->filesystem->put_contents(
                $this->rollbackDir . '/.htaccess',
                'Deny from all'
            );
        }
    }

    /**
     * Create a backup of a plugin or theme.
     *
     * @since 1.0.0
     * @param string $assetSlug The asset slug
     * @param string $assetType The asset type ('plugin' or 'theme')
     * @return bool True if backup was created successfully, false otherwise
     */
    public function createAssetBackup(string $assetSlug, string $assetType): bool
    {
        try {
            $this->initializeFilesystem();
            $this->setupRollbackDirectory();

            if ('plugin' === $assetType) {
                return $this->createPluginBackup($assetSlug);
            } elseif ('theme' === $assetType) {
                return $this->createThemeBackup($assetSlug);
            }

            return false;
        } catch (\Exception $e) {
            // Silently fail if backup creation fails
            return false;
        }
    }

    /**
     * Intercept plugin/theme upgrade to store a backup.
     *
     * @since 1.0.0
     * @param array $options Upgrader package options
     * @return array Modified options
     */
    public function interceptUpgrade(array $options): array
    {
        // Skip WordPress.org packages
        if (isset($options['package']) && is_string($options['package']) && strpos($options['package'], 'downloads.wordpress.org') !== false) {
            return $options;
        }

        // Handle plugin updates
        if (isset($options['destination'], $options['hook_extra']['plugin'])) {
            $plugin = $options['hook_extra']['plugin'];
            $pluginSlug = dirname($plugin);
            
            try {
                $this->createAssetBackup($pluginSlug, 'plugin');
            } catch (\Exception $e) {
                // Silently continue if backup fails
            }
        } // Handle theme updates
        elseif (isset($options['destination'], $options['hook_extra']['theme'])) {
            $themeSlug = $options['hook_extra']['theme'];
            
            try {
                $this->createAssetBackup($themeSlug, 'theme');
            } catch (\Exception $e) {
                // Silently continue if backup fails
            }
        }

        return $options;
    }

    /**
     * Get available versions for a plugin/theme from backup files.
     *
     * @since 1.0.0
     * @param array  $versions Current versions array
     * @param string $slug     Plugin/theme slug
     * @return array Modified versions array
     */
    public function getAvailableVersions(array $versions, string $slug): array
    {
        $pattern = sprintf('%s/%s-*.zip', $this->rollbackDir, $slug);
        
        foreach (glob($pattern) as $file) {
            if (!preg_match('/-([0-9.]+)\.zip$/', $file, $matches)) {
                continue;
            }
            
            $version = $matches[1];
            // Ensure version number is valid
            if (!preg_match('/^\d+(\.\d+)*$/', $version)) {
                continue;
            }

            if (!isset($versions[$version])) {
                $versions[$version] = [
                    'file' => basename($file),
                    'downloadUrl' => '',
                    'released' => null
                ];
            }
        }

        return $versions;
    }

    /**
     * Check if a plugin/theme has backup versions available.
     *
     * @since 1.0.0
     * @param bool   $isPro Current pro status
     * @param string $slug  Plugin/theme slug
     * @return bool Modified pro status
     */
    public function hasBackupVersions(bool $isPro, string $slug): bool
    {
        if ($isPro) {
            return true;
        }

        $pattern = sprintf('%s/%s-*.zip', $this->rollbackDir, $slug);
        return !empty(glob($pattern));
    }

    /**
     * Control whether to delete the existing plugin/theme during rollback.
     *
     * @since 1.0.0
     * @param bool   $shouldDelete Whether to delete the asset
     * @param string $assetFile    The asset file path
     * @param string $assetSlug    The asset slug
     * @return bool Whether to delete the asset
     */
    public function shouldDeleteExistingAsset(bool $shouldDelete, string $assetFile, string $assetSlug): bool
    {
        // For assets with backups, we want to handle deletion ourselves
        if (false === $this->hasBackupVersions(false, $assetSlug)) {
            return $shouldDelete;
        }

        return false;
    }

    /**
     * Modify rollback request data for assets with backup versions.
     *
     * @since 1.0.0
     * @param array         $data    Current request data
     * @param WP_REST_Request $request Raw request data
     * @return array Modified request data
     */
    public function modifyRollbackRequestData(array $data, WP_REST_Request $request): array
    {
        if (!isset($data['assetSlug'], $data['assetVersion'])) {
            return $data;
        }

        $slug = $data['assetSlug'];
        $version = $data['assetVersion'];
        $type = $data['assetType'] ?? 'plugin';
        
        $originalZipPath = sprintf('%s/%s-%s.zip', $this->rollbackDir, $slug, $version);
        $tempZipPath = sprintf('%s/%s-%s-temp.zip', $this->rollbackDir, $slug, $version);
        
        // Check if backup for this version already exists
        if (file_exists($originalZipPath)) {
            // Create a copy of the zip file that will be used for rollback
            copy($originalZipPath, $tempZipPath);
            
            // Set the package path in the transient to use the temp copy
            set_transient("wpr_{$type}_{$slug}_package", $tempZipPath, HOUR_IN_SECONDS);
            
            // Register a shutdown function to clean up the temp file after everything is done
            add_action('shutdown', function () use ($tempZipPath) {
                if (file_exists($tempZipPath)) {
                    @unlink($tempZipPath);
                }
            });
            
            // Set all required parameters
            $data['package'] = $tempZipPath;
            $data['isPro'] = true;
            $data['type'] = $type;
            $data['slug'] = $slug;
            $data['version'] = $version;
            
            // For themes, ensure the proper clearing and destination
            if ('theme' === $type) {
                // WordPress's theme upgrader typically expects the theme to be contained 
                // in a directory with its slug inside the zip. We've already ensured this 
                // in the backup creation, so we don't need to specify a destination.
                // WordPress will handle it correctly based on the zip structure.
                
                // Don't clear the destination - this can cause loss of directories
                $data['clear_destination'] = false;
                
                // Set WordPress to overwrite files but not delete directories
                $data['abort_if_destination_exists'] = false;
            }
        } else {
            // This is now a silent failure
        }

        return $data;
    }

    /**
     * Create a backup of a plugin.
     *
     * @since 1.0.0
     * @param string $pluginSlug The plugin slug
     * @return bool True if backup was created successfully
     * @throws \RuntimeException If backup creation fails
     */
    private function createPluginBackup(string $pluginSlug): bool
    {
        $pluginFile = $this->getPluginFileBySlug($pluginSlug);
        if (empty($pluginFile)) {
            return false;
        }

        $pluginPath = WP_PLUGIN_DIR . '/' . $pluginFile;
        if (!file_exists($pluginPath)) {
            return false;
        }

        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $data = get_plugin_data($pluginPath);
        $version = $data['Version'] ?? 'unknown';
        $pluginDir = dirname($pluginPath);

        return $this->createBackupZip($pluginDir, $pluginSlug, $version, 'plugin');
    }

    /**
     * Create a backup of a theme.
     *
     * @since 1.0.0
     * @param string $themeSlug The theme slug
     * @return bool True if backup was created successfully
     * @throws \RuntimeException If backup creation fails
     */
    private function createThemeBackup(string $themeSlug): bool
    {
        $themePath = get_theme_root() . '/' . $themeSlug;
        if (!is_dir($themePath)) {
            return false;
        }

        $styleFile = trailingslashit($themePath) . 'style.css';
        $version = 'unknown';

        if (file_exists($styleFile)) {
            if (!function_exists('get_file_data')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }

            $themeData = get_file_data($styleFile, [
                'Version' => 'Version',
            ], 'theme');

            $version = $themeData['Version'] ?? 'unknown';
        }

        // If we still don't have a version, try using WP_Theme
        if ('unknown' === $version) {
            $theme = wp_get_theme($themeSlug);
            if ($theme->exists()) {
                $version = $theme->get('Version') ?? 'unknown';
            }
        }

        return $this->createBackupZip($themePath, $themeSlug, $version, 'theme');
    }

    /**
     * Create a ZIP backup of an asset.
     *
     * @since 1.0.0
     * @param string $assetPath The path to the asset directory
     * @param string $slug The asset slug
     * @param string $version The asset version
     * @param string $type The asset type ('plugin' or 'theme')
     * @return bool True if ZIP was created successfully
     * @throws \RuntimeException If ZIP creation fails
     */
    private function createBackupZip(string $assetPath, string $slug, string $version, string $type): bool
    {
        $zipFilename = sprintf('%s-%s.zip', $slug, $version);
        $zipPath = wp_normalize_path($this->rollbackDir . '/' . $zipFilename);

        // Skip if backup already exists
        if (file_exists($zipPath)) {
            return true;
        }

        // Rotate backups to maintain maximum of 25 per asset
        $this->rotateBackups($slug);

        // Create ZIP archive
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Failed to create ZIP archive.');
        }

        $assetDir = trailingslashit($assetPath);

        try {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($assetDir),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $file) {
                if ($file->isDir()) {
                    continue;
                }

                $filePath = wp_normalize_path($file->getRealPath());

                if ('theme' === $type) {
                    // For themes, WordPress expects files to be inside a directory with the theme's slug
                    $relativePath = $slug . '/' . str_replace($assetDir, '', $filePath);
                } else {
                    // For plugins, we need to keep the plugin directory structure
                    $relativePath = substr($filePath, strlen(dirname($assetDir)) + 1);
                }

                if ($zip->addFile($filePath, $relativePath) === false) {
                    $zip->close();
                    throw new \RuntimeException('Failed to add file to ZIP archive: ' . esc_html($relativePath));
                }
            }

            $zip->close();
            return true;
        } catch (\Exception $e) {
            if ($zip) {
                $zip->close();
            }
            throw new \RuntimeException('Error creating backup: ' . esc_html($e->getMessage()));
        }
    }

    /**
     * Initialize WordPress filesystem.
     *
     * @since 1.0.0
     * @throws \RuntimeException If filesystem initialization fails
     */
    private function initializeFilesystem(): void
    {
        if (null !== $this->filesystem) {
            return;
        }

        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        WP_Filesystem();
        /* phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase */
        global $wp_filesystem;

        // WordPress variable naming exception - this is a global WordPress var
        if (!($wp_filesystem instanceof WP_Filesystem_Direct)) {
            throw new \RuntimeException('WordPress filesystem not initialized properly.');
        }

        $this->filesystem = $wp_filesystem;
    }

    /**
     * Rotate backups to maintain maximum of 25 backups per asset.
     *
     * @since 1.0.0
     * @param string $slug The asset slug
     */
    private function rotateBackups(string $slug): void
    {
        $pattern = sprintf('%s/%s-*.zip', $this->rollbackDir, $slug);
        $backupFiles = glob($pattern);
        
        if (!$backupFiles || count($backupFiles) < 25) {
            return;
        }

        // Sort files by modification time (oldest first)
        usort($backupFiles, function ($a, $b) {
            return filemtime($a) - filemtime($b);
        });

        // Remove oldest backups until we have room for one more (24 remaining)
        $filesToRemove = array_slice($backupFiles, 0, count($backupFiles) - 24);
        
        foreach ($filesToRemove as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
    }

    /**
     * Get plugin file by slug.
     *
     * @since 1.0.0
     * @param string $pluginSlug The plugin slug
     * @return string The plugin file path relative to plugins directory
     */
    private function getPluginFileBySlug(string $pluginSlug): string
    {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugins = get_plugins();
        foreach (array_keys($plugins) as $file) {
            if (0 === strpos($file, $pluginSlug . '/')) {
                return $file;
            }
        }

        return '';
    }
} 