<?php

/**
 * Backup service for creating and managing asset backups.
 *
 * @package WpRollback\SharedCore\Rollbacks\Services
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Rollbacks\Services;

use WP_Filesystem_Base;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use WpRollback\SharedCore\Rollbacks\Traits\PluginHelpers;
use WpRollback\SharedCore\Core\SharedCore;
use WpRollback\SharedCore\PluginSetup\PluginManager;


/**
 * Service for creating and managing asset backups
 *
 */
class BackupService
{
    use PluginHelpers;

    /**
     * Files written into every backup folder to block listing and direct downloads.
     *
     * @var string[]
     */
    public const PROTECTION_FILES = ['.htaccess', 'index.php', 'index.html', 'web.config'];

    /**
     * Ending of the temporary copy a rollback uses (`{slug}-{version}-temp.zip`).
     * parseArchiveFilename() rejects it, so copies never show up as backups.
     */
    private const STAGED_COPY_SUFFIX = '-temp.zip';

    /**
     * Modification time PclZip stores for every backup entry: 1980-01-01 00:00:00 UTC.
     *
     * PclZip converts it to a ZIP date in PHP's timezone, which WordPress sets
     * to UTC. ZIP dates start in 1980, so the epoch 0 that ZipArchive is given
     * would wrap round to 2098. ZipArchive stores 0 as this same date on UTC servers.
     */
    private const PCLZIP_ENTRY_MTIME = 315532800;

    /**
     * @var string Directory path for rollback files
     */
    private string $rollbackDir;

    /**
     * @var WP_Filesystem_Base|null WordPress filesystem
     */
    private ?WP_Filesystem_Base $filesystem = null;

    /**
     * Constructor.
     *
     */
    public function __construct()
    {
        // Only basedir is needed, so don't create this month's uploads folder.
        $uploadDir = wp_upload_dir(null, false);
        $hash      = PluginManager::ensureDirectoryHash();

        // Normalised so paths built from it match the archive paths stored in the
        // activity log, which are normalised too. On Windows basedir is a mixed
        // path like `C:\xampp\htdocs/wp-content/uploads`, which never matched.
        $this->rollbackDir = wp_normalize_path(trailingslashit($uploadDir['basedir']) . 'wp-rollback-' . $hash);
    }

    /**
     * Create the backup folder, with its protection files, if it doesn't exist yet.
     *
     * @throws \RuntimeException If the folder can't be created
     */
    private function setupRollbackDirectory(): void
    {
        $this->initializeFilesystem();

        if (!$this->filesystem->is_dir($this->rollbackDir)) {
            if (!$this->filesystem->mkdir($this->rollbackDir)) {
                throw new \RuntimeException('Failed to create rollback directory.');
            }

            $this->writeProtectionFiles($this->rollbackDir);
        }
    }

    /**
     * Write the files that stop a backup folder being listed or downloaded directly.
     *
     * .htaccess covers Apache 2.4 and 2.2 (and LiteSpeed), web.config covers IIS,
     * and the index files stop directory listings. Nginx ignores all of these,
     * which is why the folder name itself is unguessable.
     *
     * @param string $dir Folder to protect
     */
    public function writeProtectionFiles(string $dir): void
    {
        $this->initializeFilesystem();

        $dir   = trailingslashit($dir);
        $files = [
            'index.php'  => "<?php // Silence is golden\n",
            'index.html' => '',
            '.htaccess'  => "# Block direct downloads of WP Rollback backups.\n"
                . "<IfModule mod_authz_core.c>\n"
                . "\tRequire all denied\n"
                . "</IfModule>\n"
                . "<IfModule !mod_authz_core.c>\n"
                . "\tOrder deny,allow\n"
                . "\tDeny from all\n"
                . "</IfModule>\n",
            'web.config' => "<configuration>\n"
                . "\t<system.webServer>\n"
                . "\t\t<authorization>\n"
                . "\t\t\t<deny users=\"*\" />\n"
                . "\t\t</authorization>\n"
                . "\t</system.webServer>\n"
                . "</configuration>\n",
        ];

        foreach ($files as $name => $contents) {
            $this->filesystem->put_contents($dir . $name, $contents);
        }
    }

    /**
     * Check if a backup already exists for a specific version.
     *
     * @param string $assetSlug The asset slug
     * @param string $assetType The asset type ('plugin' or 'theme')
     * @return bool|string False if no backup exists, version string if backup exists
     */
    public function getExistingBackupVersion(string $assetSlug, string $assetType)
    {
        try {
            // Get current version
            $version = $this->getCurrentAssetVersion($assetSlug, $assetType);
            if (empty($version)) {
                return false;
            }

            // Check if backup file exists
            $zipFilename = sprintf('%s-%s.zip', $assetSlug, $version);
            $zipPath = wp_normalize_path($this->rollbackDir . '/' . $zipFilename);

            return file_exists($zipPath) ? $version : false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Create a backup of a plugin or theme.
     *
     * @param string $assetSlug The asset slug
     * @param string $assetType The asset type ('plugin' or 'theme')
     * @return bool|string True if backup was created successfully, 'exists' if backup already exists, false otherwise
     */
    public function createAssetBackup(string $assetSlug, string $assetType)
    {
        try {
            $this->setupRollbackDirectory();

            // Check if backup already exists for current version
            if ($this->getExistingBackupVersion($assetSlug, $assetType)) {
                return 'exists';
            }

            if ('plugin' === $assetType) {
                return $this->createPluginBackup($assetSlug);
            } elseif ('theme' === $assetType) {
                return $this->createThemeBackup($assetSlug);
            }

            return false;
        } catch (\Throwable $e) {
            // Silently fail if backup creation fails - catches both Exceptions and Errors
            // Log error if debug mode is enabled
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log(sprintf('[WP Rollback] Backup creation failed for %s (%s): %s', $assetSlug, $assetType, $e->getMessage()));
            }
            return false;
        }
    }

    /**
     * Intercept plugin/theme upgrade to store a backup.
     *
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
            } catch (\Throwable $e) {
                // Silently continue if backup fails - catches both Exceptions and Errors
                // This ensures plugin updates are never blocked by backup failures
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                    error_log(sprintf('[WP Rollback] Backup creation failed during plugin update: %s', $e->getMessage()));
                }
            }
        } // Handle theme updates
        elseif (isset($options['destination'], $options['hook_extra']['theme'])) {
            $themeSlug = $options['hook_extra']['theme'];

            try {
                $this->createAssetBackup($themeSlug, 'theme');
            } catch (\Throwable $e) {
                // Silently continue if backup fails - catches both Exceptions and Errors
                // This ensures theme updates are never blocked by backup failures
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                    error_log(sprintf('[WP Rollback] Backup creation failed during theme update: %s', $e->getMessage()));
                }
            }
        }

        return $options;
    }

    /**
     * Get available versions for a plugin/theme from backup files.
     *
     * @param array  $versions Current versions array
     * @param string $slug     Plugin/theme slug
     * @return array Modified versions array
     */
    public function getAvailableVersions(array $versions, string $slug): array
    {
        foreach ($this->getArchivesForSlug($slug) as $file => $version) {
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
     * @param bool   $isPro Current pro status
     * @param string $slug  Plugin/theme slug
     * @return bool Modified pro status
     */
    public function hasBackupVersions(bool $isPro, string $slug): bool
    {
        if ($isPro) {
            return true;
        }

        return !empty($this->getArchivesForSlug($slug));
    }

    /**
     * Get the backup archives that belong to a single asset.
     *
     * A `{slug}-*.zip` glob alone also matches assets whose slug shares the
     * prefix (e.g. `foo` would pick up `foo-bar-2.0.zip`), so only files that
     * parse back to exactly this slug are returned.
     *
     * @param string $slug Plugin/theme slug
     * @return array<string, string> Archive versions keyed by absolute file path
     */
    public function getArchivesForSlug(string $slug): array
    {
        $archives = [];
        $pattern = sprintf('%s/%s-*.zip', $this->rollbackDir, $slug);

        foreach (glob($pattern) ?: [] as $file) {
            $parsed = $this->parseArchiveFilename(basename($file));

            if (null !== $parsed && $parsed['slug'] === $slug) {
                $archives[$file] = $parsed['version'];
            }
        }

        return $archives;
    }

    /**
     * Get every backup archive, grouped by the asset it belongs to.
     *
     * @return array<string, string[]> Absolute archive paths keyed by asset slug
     */
    public function getArchivesBySlug(): array
    {
        $archivesBySlug = [];
        $pattern = sprintf('%s/*-*.zip', $this->rollbackDir);

        foreach (glob($pattern) ?: [] as $file) {
            $parsed = $this->parseArchiveFilename(basename($file));

            if (null !== $parsed) {
                $archivesBySlug[$parsed['slug']][] = $file;
            }
        }

        return $archivesBySlug;
    }

    /**
     * Use this site's backup of a version as the rollback package, if there is one.
     *
     * Copies the backup to a temporary file and points the rollback's package
     * transient at it, so the rollback steps use it and the original backup is
     * never touched. The cleanup step deletes the copy when the rollback ends,
     * including after a failure.
     *
     * The download step calls this once per rollback. The admin screen runs
     * each step in its own request, so the copy lasts until cleanup.
     *
     * @param string $type    Asset type ('plugin' or 'theme').
     * @param string $slug    Asset slug.
     * @param string $version Version to roll back to.
     * @return string|null Path to the temporary copy, or null if there's no usable backup.
     */
    public function stageBackupPackage(string $type, string $slug, string $version): ?string
    {
        $originalZipPath = sprintf('%s/%s-%s.zip', $this->rollbackDir, $slug, $version);
        $tempZipPath = sprintf('%s/%s-%s%s', $this->rollbackDir, $slug, $version, self::STAGED_COPY_SUFFIX);

        if (!file_exists($originalZipPath)) {
            return null;
        }

        $this->deleteAbandonedCopies();

        if (!@copy($originalZipPath, $tempZipPath)) {
            return null;
        }

        set_transient("wpr_{$type}_{$slug}_package", $tempZipPath, HOUR_IN_SECONDS);

        return $tempZipPath;
    }

    /**
     * Delete staged copies left by rollbacks that stopped more than a day ago.
     *
     * A rollback takes minutes, so an older copy was abandoned before cleanup.
     */
    private function deleteAbandonedCopies(): void
    {
        foreach (glob($this->rollbackDir . '/*' . self::STAGED_COPY_SUFFIX) ?: [] as $copy) {
            if (filemtime($copy) < time() - DAY_IN_SECONDS) {
                @unlink($copy);
            }
        }
    }

    /**
     * Create a backup of a plugin.
     *
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
        $version = !empty($data['Version']) ? $data['Version'] : 'unknown';
        $pluginDir = dirname($pluginPath);

        return $this->createBackupZip($pluginDir, $pluginSlug, $version, 'plugin');
    }

    /**
     * Create a backup of a theme.
     *
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

            $version = isset($themeData['Version']) ? $themeData['Version'] : 'unknown';
        }

        // If we still don't have a version, try using WP_Theme
        if ('unknown' === $version) {
            $theme = wp_get_theme($themeSlug);
            if ($theme->exists()) {
                $versionValue = $theme->get('Version');
                $version = $versionValue ? $versionValue : 'unknown';
            }
        }

        return $this->createBackupZip($themePath, $themeSlug, $version, 'theme');
    }

    /**
     * Create a ZIP backup of an asset.
     *
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

        // Try ZipArchive first (faster), fallback to PclZip (WordPress Core library)
        if (class_exists('ZipArchive')) {
            return $this->createBackupZipWithZipArchive($assetPath, $slug, $type, $zipPath);
        } else {
            return $this->createBackupZipWithPclZip($assetPath, $slug, $type, $zipPath);
        }
    }

    /**
     * List the files to back up and the name each one is stored under in the ZIP.
     *
     * Both ZIP writers use this list, so a backup has the same entries in the
     * same order whichever one made it: `{slug}/...` for plugins and themes, so
     * it unzips into the plugins or themes folder.
     *
     * @param string $assetPath The path to the asset directory
     * @param string $slug The asset slug
     * @param string $type The asset type ('plugin' or 'theme')
     * @return array<string, string> ZIP entry names keyed by absolute file path, sorted by path
     */
    private function getBackupEntries(string $assetPath, string $slug, string $type): array
    {
        // Each file path is normalised below, so the folder cut off the front of
        // them has to be too. On Windows, get_theme_root() returns a mixed path
        // like `C:\xampp\htdocs/wp-content/themes`, which never matched, so theme
        // files were stored under their full server path.
        $assetDir = trailingslashit(wp_normalize_path($assetPath));

        // Collect all file paths before adding them to the archive.
        //
        // RecursiveDirectoryIterator returns entries in filesystem order,
        // which differs between ext4, APFS, NTFS, and other filesystems.
        // Because ZIP SHA-256 is sensitive to entry order, two servers
        // creating a backup of the same plugin version would produce
        // different checksums if files are added in a different sequence.
        //
        // Sorting alphabetically makes the entry order deterministic on
        // every platform, so the Plugin Vault's SHA-256 quorum check
        // produces the same result across all contributing sites.
        $filePaths = [];
        $iterator  = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($assetDir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }
            // Use getPathname() rather than getRealPath() so the path is
            // never symlink-resolved. getRealPath() would resolve /var to
            // /private/var on macOS, making the path inconsistent with
            // $assetDir and corrupting all relative path calculations.
            $absolutePath = wp_normalize_path($file->getPathname());
            // Skip OS-specific artefacts that are absent on some servers
            // (e.g. .DS_Store on macOS, Thumbs.db on Windows) and VCS
            // metadata that should never ship in a plugin package. These
            // files cause SHA-256 differences between Mac-deployed and
            // Linux-deployed servers even when the plugin code is identical.
            if ($this->isOsArtefact($absolutePath)) {
                continue;
            }
            $filePaths[] = $absolutePath;
        }

        sort($filePaths); // Stable, platform-independent entry order

        $entries = [];
        foreach ($filePaths as $filePath) {
            if ('theme' === $type) {
                // For themes, WordPress expects files to be inside a directory with the theme's slug
                $entries[$filePath] = $slug . '/' . str_replace($assetDir, '', $filePath);
            } else {
                // For plugins, we need to keep the plugin directory structure
                $entries[$filePath] = substr($filePath, strlen(dirname($assetDir)) + 1);
            }
        }

        return $entries;
    }

    /**
     * Create a ZIP backup using ZipArchive (preferred method).
     *
     * @param string $assetPath The path to the asset directory
     * @param string $slug The asset slug
     * @param string $type The asset type ('plugin' or 'theme')
     * @param string $zipPath The path where the ZIP file will be created
     * @return bool True if ZIP was created successfully
     * @throws \RuntimeException If ZIP creation fails
     */
    private function createBackupZipWithZipArchive(string $assetPath, string $slug, string $type, string $zipPath): bool
    {
        // Create ZIP archive
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Failed to create ZIP archive.');
        }

        try {
            foreach ($this->getBackupEntries($assetPath, $slug, $type) as $filePath => $relativePath) {
                if ($zip->addFile($filePath, $relativePath) === false) {
                    $zip->close();
                    throw new \RuntimeException('Failed to add file to ZIP archive: ' . esc_html($relativePath));
                }

                // Normalise the stored mtime to epoch 0 (PHP 8.0+).
                // Without this, the ZIP entry timestamp reflects the file's
                // mtime on the host filesystem, which can vary if PHP or the OS
                // reset it during extraction or deployment. Setting a fixed
                // value ensures the ZIP bytes — and therefore the SHA-256 —
                // are identical across all sites backing up the same version.
                if (method_exists($zip, 'setMtimeName')) {
                    $zip->setMtimeName($relativePath, 0);
                }
            }

            $zip->close();

            // Extract version from zip path for the action hook
            $version = basename($zipPath, '.zip');
            $version = str_replace($slug . '-', '', $version);

            // Trigger action for archive creation (Pro plugin can hook into this)
            do_action('wpr_archive_created', $slug, $version, $type, $zipPath);

            return true;
        } catch (\Exception $e) {
            if ($zip instanceof \ZipArchive) {
                $zip->close();
            }
            throw new \RuntimeException('Error creating backup: ' . esc_html($e->getMessage()));
        }
    }

    /**
     * Create a ZIP backup using PclZip (WordPress Core fallback).
     *
     * @param string $assetPath The path to the asset directory
     * @param string $slug The asset slug
     * @param string $type The asset type ('plugin' or 'theme')
     * @param string $zipPath The path where the ZIP file will be created
     * @return bool True if ZIP was created successfully
     * @throws \RuntimeException If ZIP creation fails
     */
    private function createBackupZipWithPclZip(string $assetPath, string $slug, string $type, string $zipPath): bool
    {
        // Load PclZip library from WordPress Core
        if (!class_exists('PclZip')) {
            require_once ABSPATH . 'wp-admin/includes/class-pclzip.php';
        }

        try {
            // Add each file by name rather than letting PclZip walk the folder.
            // Its walk follows filesystem order, adds folder entries and OS
            // artefacts, and keeps each file's own mtime, so the same plugin
            // version would get a different SHA-256 on every site.
            $files = [];
            foreach ($this->getBackupEntries($assetPath, $slug, $type) as $filePath => $entryName) {
                $files[] = [
                    PCLZIP_ATT_FILE_NAME => $filePath, // @phpstan-ignore-line - WordPress Core constant
                    PCLZIP_ATT_FILE_NEW_FULL_NAME => $entryName, // @phpstan-ignore-line - WordPress Core constant
                    PCLZIP_ATT_FILE_MTIME => self::PCLZIP_ENTRY_MTIME, // @phpstan-ignore-line - WordPress Core constant
                ];
            }

            // phpcs:ignore PHPCompatibility.Classes.NewClasses.pclzipFound
            $archive = new \PclZip($zipPath);
            $result = $archive->create($files);

            if (0 === $result) {
                throw new \RuntimeException('PclZip error: ' . esc_html($archive->errorInfo(true)));
            }

            // Extract version from zip path for the action hook
            $version = basename($zipPath, '.zip');
            $version = str_replace($slug . '-', '', $version);

            // Trigger action for archive creation (Pro plugin can hook into this)
            do_action('wpr_archive_created', $slug, $version, $type, $zipPath);

            return true;
        } catch (\Exception $e) {
            throw new \RuntimeException('Error creating backup with PclZip: ' . esc_html($e->getMessage()));
        }
    }

    /**
     * Get the absolute path to the rollback backup directory.
     *
     */
    public function getRollbackDirectory(): string
    {
        return $this->rollbackDir;
    }

    /**
     * Set up the filesystem object used for the backup folder.
     *
     * Allows relaxed file ownership, so it works where PHP's user isn't the
     * files' owner but can still write to them.
     *
     * When WordPress would still ask for FTP or SSH credentials, WP_Filesystem()
     * returns false and leaves behind an object that isn't connected: it can't
     * see or write anything. The backup folder lives in uploads, which PHP
     * writes to itself, so direct file access is used instead.
     */
    private function initializeFilesystem(): void
    {
        if (null !== $this->filesystem) {
            return;
        }

        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        // phpcs:disable Squiz.NamingConventions.ValidVariableName.NotCamelCaps -- $wp_filesystem is a WordPress global
        global $wp_filesystem;

        if (WP_Filesystem(false, false, true) && $wp_filesystem instanceof WP_Filesystem_Base) {
            $this->filesystem = $wp_filesystem;
            return;
        }
        // phpcs:enable Squiz.NamingConventions.ValidVariableName.NotCamelCaps

        if (!class_exists('WP_Filesystem_Direct')) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
            require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
        }

        // WP_Filesystem() only sets these once it has connected, and the direct
        // class needs them to create folders and copy files.
        if (!defined('FS_CHMOD_DIR')) {
            define('FS_CHMOD_DIR', (fileperms(ABSPATH) & 0777 | 0755));
        }
        if (!defined('FS_CHMOD_FILE')) {
            define('FS_CHMOD_FILE', (fileperms(ABSPATH . 'index.php') & 0777 | 0644));
        }

        $this->filesystem = new \WP_Filesystem_Direct(null);
    }

    /**
     * Whether a file is inside the backup folder, once symlinks and `..` are resolved.
     *
     * @param string $path Path to check
     * @return bool False for anything outside the folder, the folder itself, and paths that don't exist
     */
    public function isInRollbackDirectory(string $path): bool
    {
        if ('' === $path) {
            return false;
        }

        $realPath = realpath($path);
        $realDir  = realpath($this->rollbackDir);
        if (false === $realPath || false === $realDir) {
            return false;
        }

        return 0 === strpos(wp_normalize_path($realPath), trailingslashit(wp_normalize_path($realDir)));
    }

    /**
     * The filesystem object for working in the backup folder.
     */
    public function getFilesystem(): WP_Filesystem_Base
    {
        $this->initializeFilesystem();

        return $this->filesystem;
    }

    /**
     * Get the archive limit based on Pro/Free version
     *
     * @return int The archive limit
     */
    public function getArchiveLimit(): int
    {
        if ($this->isProPluginActive()) {
            try {
                $proSettings = SharedCore::container()->make(\WpRollback\Pro\Settings\ProSettings::class);
                return $proSettings->getArchiveLimit();
            } catch (\Exception $e) {
                // Fall back to default Pro limit
                return 25;
            }
        }

        // Free version limit
        return 5;
    }

    /**
     * Rotate backups to maintain maximum backups per asset.
     *
     * @param string $slug The asset slug
     */
    private function rotateBackups(string $slug): void
    {
        $maxBackups = $this->getArchiveLimit();
        $backupFiles = array_keys($this->getArchivesForSlug($slug));

        if (count($backupFiles) < $maxBackups) {
            return;
        }

        // Sort files by modification time (oldest first)
        usort($backupFiles, function ($a, $b) {
            return filemtime($a) - filemtime($b);
        });

        // Remove oldest backups until we have room for one more
        $filesToRemove = array_slice($backupFiles, 0, count($backupFiles) - ($maxBackups - 1));

        foreach ($filesToRemove as $file) {
            $this->deleteArchiveFile($file);
        }
    }

    /**
     * Delete a backup archive and announce it.
     *
     * Pro keeps a record of each archive for its Archives screen, so every
     * deletion goes through here and fires `wpr_archive_deleted`. Only files
     * inside the backup folder are deleted.
     *
     * @param string $file Absolute path of the archive
     * @return bool True if the file was deleted
     */
    public function deleteArchiveFile(string $file): bool
    {
        if (!$this->isInRollbackDirectory($file) || !@unlink($file)) {
            return false;
        }

        // Trigger action for archive deletion (Pro plugin can hook into this)
        do_action('wpr_archive_deleted', $file);

        return true;
    }

    /**
     * Split a backup archive filename into its asset slug and version.
     *
     * Versions are numeric (e.g. `2.0.1`) and never contain a hyphen, so the
     * last hyphen always separates slug from version: `foo-bar-2.0.zip` is
     * slug `foo-bar`, version `2.0`.
     *
     * @param string $filename Archive filename without directory
     * @return array{slug: string, version: string}|null Null if not a backup archive
     */
    private function parseArchiveFilename(string $filename): ?array
    {
        if (!preg_match('/^(.+)-(\d+(?:\.\d+)*)\.zip$/', $filename, $matches)) {
            return null;
        }

        return ['slug' => $matches[1], 'version' => $matches[2]];
    }

    /**
     * Return true for OS-specific artefacts that should be excluded from
     * vault-contribution ZIPs.
     *
     * These files are absent on some platforms (e.g. .DS_Store only appears
     * on macOS; Thumbs.db / desktop.ini only on Windows). Including them
     * would make the backup ZIP differ between servers even for identical
     * plugin code, breaking the Plugin Vault's SHA-256 quorum check.
     *
     * VCS metadata (.git, .svn) is also excluded — it is occasionally
     * shipped by accident but is never part of a distributed plugin package.
     *
     * NOTE: PclZip (the fallback when ZipArchive is unavailable) does not
     * benefit from this filtering because it receives a directory path and
     * traverses it internally. PclZip is only used on PHP 7.x hosts without
     * the zip extension — an extremely rare configuration.
     *
     * @param string $absolutePath wp_normalize_path()-normalised absolute path.
     */
    private function isOsArtefact(string $absolutePath): bool
    {
        $basename = basename($absolutePath);

        // macOS Finder metadata
        if ('.DS_Store' === $basename) {
            return true;
        }

        // Windows Explorer thumbnail / layout caches
        if ('Thumbs.db' === $basename || 'desktop.ini' === $basename) {
            return true;
        }

        // Files inside macOS ZIP resource-fork directory, VCS directories
        foreach (['/__MACOSX/', '/.git/', '/.svn/'] as $segment) {
            if (false !== strpos($absolutePath, $segment)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get plugin file by slug.
     *
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

    /**
     * Get the current version of an asset.
     *
     * @param string $assetSlug The asset slug
     * @param string $assetType The asset type ('plugin' or 'theme')
     * @return string The asset version or empty string if not found
     */
    private function getCurrentAssetVersion(string $assetSlug, string $assetType): string
    {
        if ('plugin' === $assetType) {
            $pluginFile = $this->getPluginFileBySlug($assetSlug);
            if (empty($pluginFile)) {
                return '';
            }

            $pluginPath = WP_PLUGIN_DIR . '/' . $pluginFile;
            if (!file_exists($pluginPath)) {
                return '';
            }

            if (!function_exists('get_plugin_data')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }

            $data = get_plugin_data($pluginPath);
            return !empty($data['Version']) ? $data['Version'] : '';
        } elseif ('theme' === $assetType) {
            $theme = wp_get_theme($assetSlug);
            if ($theme->exists()) {
                $version = $theme->get('Version');
                return $version ? $version : '';
            }
        }

        return '';
    }

    /**
     * Cleanup excess archives when limit is reduced
     *
     * @param int $newLimit New archive limit
     * @return array Array with 'deleted' files and 'count' of deletions
     */
    public function cleanupExcessArchives(int $newLimit): array
    {
        $deleted = [];
        $backupsBySlug = $this->getArchivesBySlug();

        if (!$backupsBySlug) {
            return ['deleted' => [], 'count' => 0];
        }

        // Process each asset's backups
        foreach ($backupsBySlug as $slug => $backups) {
            if (count($backups) <= $newLimit) {
                continue;
            }

            // Sort by modification time (oldest first)
            usort($backups, function ($a, $b) {
                return filemtime($a) - filemtime($b);
            });

            // Remove excess backups
            $excess = count($backups) - $newLimit;
            $toDelete = array_slice($backups, 0, $excess);

            foreach ($toDelete as $file) {
                if ($this->deleteArchiveFile($file)) {
                    $deleted[] = basename($file);
                }
            }
        }

        return [
            'deleted' => $deleted,
            'count'   => count($deleted),
        ];
    }

    /**
     * Clear all archives
     *
     * @return array Array with 'deleted' files and 'count' of deletions
     */
    public function clearAllArchives(): array
    {
        $deleted = [];
        $pattern = sprintf('%s/*-*.zip', $this->rollbackDir);
        $allBackups = glob($pattern);

        if (!$allBackups) {
            return ['deleted' => [], 'count' => 0];
        }

        // Delete all backup files
        foreach ($allBackups as $file) {
            if ($this->deleteArchiveFile($file)) {
                $deleted[] = basename($file);
            }
        }

        return [
            'deleted' => $deleted,
            'count'   => count($deleted),
        ];
    }
}
