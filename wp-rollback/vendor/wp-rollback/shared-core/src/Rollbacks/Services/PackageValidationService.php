<?php

/**
 * Package validation service for verifying plugin and theme integrity using WordPress Core methods.
 *
 * @package WpRollback\SharedCore\Rollbacks\Services
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Rollbacks\Services;

use PclZip;
use WP_Error;
use ZipArchive;

/**
 * Service for validating packages using WordPress Core validation methods
 *
 */
class PackageValidationService
{

    /**
     * Validate a downloaded package using WordPress Core methods
     *
     * @param string $packagePath Path to the downloaded ZIP package
     * @param string $assetType   Type of asset ('plugin' or 'theme')
     * @param string $assetSlug   Asset slug
     * @param string $version     Asset version
     * @return array{success: bool, message: string, details?: array}
     */
    public function validatePackage(
        string $packagePath,
        string $assetType,
        string $assetSlug,
        string $version
    ): array {
        // Validate input parameters
        if (!file_exists($packagePath)) {
            return [
                'success' => false,
                'message' => __('Package file does not exist for validation.', 'wp-rollback'),
            ];
        }

        if (!in_array($assetType, ['plugin', 'theme'], true)) {
            return [
                'success' => false,
                'message' => __('Invalid asset type for package validation.', 'wp-rollback'),
            ];
        }

        $validationResults = [];

        // Read the archive's entry list once; every check below works from it.
        $entries = $this->readZipEntries($packagePath);

        // 1. Validate using WordPress Core file functions first
        $coreValidation = $this->validateWithWordPressCore($packagePath, $entries);
        if (is_wp_error($coreValidation)) {
            return [
                'success' => false,
                'message' => sprintf(
                    /* translators: %s: Error message */
                    __('WordPress Core validation failed: %s', 'wp-rollback'),
                    $coreValidation->get_error_message()
                ),
            ];
        }
        $validationResults['wordpress_core'] = $coreValidation;

        // 2. Validate ZIP integrity
        $zipValidation = $this->validateZipIntegrity($packagePath, $entries);
        if (is_wp_error($zipValidation)) {
            return [
                'success' => false,
                'message' => sprintf(
                    /* translators: %s: Error message */
                    __('ZIP validation failed: %s', 'wp-rollback'),
                    $zipValidation->get_error_message()
                ),
            ];
        }
        $validationResults['zip_integrity'] = $zipValidation;

        // 3. Validate package structure
        $structureValidation = $this->validatePackageStructure($entries, $assetType, $assetSlug);
        if (is_wp_error($structureValidation)) {
            return [
                'success' => false,
                'message' => sprintf(
                    /* translators: %s: Error message */
                    __('Package structure validation failed: %s', 'wp-rollback'),
                    $structureValidation->get_error_message()
                ),
            ];
        }
        $validationResults['structure'] = $structureValidation;

        // 4. Validate file security (custom patterns WordPress doesn't provide)
        $securityValidation = $this->validateFileSecurity($entries);
        $validationResults['security'] = $securityValidation;

        return [
            'success' => true,
            'message' => sprintf(
                /* translators: %1$s: Asset type, %2$s: Number of files validated */
                __('Package validation successful: %1$s package validated with %2$d files checked.', 'wp-rollback'),
                $assetType,
                $validationResults['security']['files_checked'] ?? 0
            ),
            'details' => $validationResults,
        ];
    }

    /**
     * Validate using WordPress Core functions
     *
     * @param string $packagePath Path to ZIP package
     * @param array<int, array{name: string, size: int}>|WP_Error $entries ZIP entries, or the error from reading them
     * @return array|WP_Error Validation results or error
     */
    private function validateWithWordPressCore(string $packagePath, $entries)
    {
        // Initialize WordPress filesystem if needed
        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        // Check if file modifications are allowed
        if (!wp_is_file_mod_allowed('unzip_file')) {
            return new WP_Error(
                'file_mod_not_allowed',
                __('File modifications are not allowed on this installation.', 'wp-rollback')
            );
        }

        // First, verify this is actually a ZIP file: its entry list could be read
        // This is more reliable than wp_check_filetype_and_ext() for temporary files
        if (is_wp_error($entries)) {
            return new WP_Error(
                'invalid_zip_format',
                __('Package is not a valid ZIP file format.', 'wp-rollback')
            );
        }

        // Use WordPress file type validation
        // Note: This may fail for temporary files from download_url() in multisite
        $fileType = wp_check_filetype_and_ext($packagePath, basename($packagePath));
        
        // For temporary files (like those from download_url()), we may not get an extension
        // The archive was read successfully above, so we can be more lenient here
        if (!$fileType['ext']) {
            // Force ZIP type for valid ZIP files that WordPress can't detect
            $fileType['ext'] = 'zip';
            $fileType['type'] = 'application/zip';
        } elseif ($fileType['ext'] !== 'zip') {
            return new WP_Error(
                'invalid_file_type',
                __('Package is not a valid ZIP file according to WordPress.', 'wp-rollback')
            );
        }

        // Check against WordPress allowed MIME types
        $allowedMimes = get_allowed_mime_types();
        
        // In multisite, 'application/zip' might not be in allowed MIME types
        // Add it temporarily for our validation
        if (is_multisite() && !in_array('application/zip', $allowedMimes, true)) {
            $allowedMimes['zip'] = 'application/zip';
        }
        
        if (!in_array($fileType['type'], $allowedMimes, true)) {
            return new WP_Error(
                'disallowed_mime_type',
                sprintf(
                    /* translators: %s: MIME type */
                    __('Package MIME type "%s" is not allowed by WordPress.', 'wp-rollback'),
                    $fileType['type']
                )
            );
        }

        // Use WordPress file size validation
        $maxSize = wp_max_upload_size();
        $fileSize = filesize($packagePath);
        
        // For rollback operations, use a more reasonable size limit (100MB)
        // Multisite often has a restrictive 1MB limit which is too small for plugins/themes
        if (defined('WPR_ROLLBACK_ACTIVE') || did_action('wp_ajax_wpr_process_rollback')) {
            $maxSize = max($maxSize, 104857600); // 100MB
        }
        
        if ($fileSize > $maxSize) {
            return new WP_Error(
                'file_exceeds_limit',
                sprintf(
                    /* translators: %s: Maximum file size */
                    __('Package exceeds WordPress maximum upload size of %s.', 'wp-rollback'),
                    size_format($maxSize)
                )
            );
        }

        // Validate file path using WordPress function
        $validateResult = validate_file(basename($packagePath));
        if ($validateResult !== 0) {
            return new WP_Error(
                'invalid_file_path',
                __('Package file path contains invalid characters.', 'wp-rollback')
            );
        }

        return [
            'file_type_valid' => true,
            'mime_type' => $fileType['type'],
            'file_extension' => $fileType['ext'],
            'size_valid' => true,
            'max_upload_size' => $maxSize,
            'file_size' => $fileSize,
        ];
    }

    /**
     * Validate ZIP file integrity
     *
     * @param string $packagePath Path to ZIP package
     * @param array<int, array{name: string, size: int}> $entries ZIP entries
     * @return array|WP_Error Validation results or error
     */
    private function validateZipIntegrity(string $packagePath, array $entries)
    {
        // Check file size is reasonable (100MB limit)
        $fileSize = filesize($packagePath);
        if ($fileSize === false || $fileSize > 104857600) {
            return new WP_Error(
                'file_too_large',
                __('Package file is too large for processing.', 'wp-rollback')
            );
        }

        return [
            'file_count' => count($entries),
            'file_size' => $fileSize,
            'format_valid' => true,
        ];
    }

    /**
     * Validate package directory structure
     *
     * @param array<int, array{name: string, size: int}> $entries ZIP entries
     * @param string $assetType Asset type
     * @param string $assetSlug Asset slug
     * @return array|WP_Error Validation results or error
     */
    private function validatePackageStructure(array $entries, string $assetType, string $assetSlug)
    {
        $rootDir = null;
        $hasValidStructure = false;
        $phpFilesFound = 0;
        $cssFilesFound = 0;

        // Find root directory and validate basic structure
        foreach ($entries as $entry) {
            $filename = $entry['name'];
            
            // Skip __MACOSX and other meta directories
            if (strpos($filename, '__MACOSX/') === 0) {
                continue;
            }

            // Stop here, before the current version is moved or deleted, rather
            // than let unzip_file() fail or write files outside the asset's folder.
            if (!$this->isInsideAssetFolder($filename, $assetSlug)) {
                return new WP_Error(
                    'file_outside_asset_folder',
                    sprintf(
                        /* translators: 1: Path of a file in the package, 2: Plugin or theme folder name */
                        __('The package can\'t be installed because "%1$s" isn\'t a valid path inside the %2$s/ folder. Your installed version hasn\'t been changed.', 'wp-rollback'),
                        sanitize_text_field($filename),
                        $assetSlug
                    )
                );
            }

            // Find root directory
            if ($rootDir === null && strpos($filename, '/') !== false) {
                $parts = explode('/', $filename);
                $rootDir = $parts[0];
            }

            // Count PHP and CSS files to ensure we have a valid package
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if ($extension === 'php') {
                $phpFilesFound++;
                $hasValidStructure = true;
            } elseif ($extension === 'css') {
                $cssFilesFound++;
                if ($assetType === 'theme') {
                    $hasValidStructure = true;
                }
            }
        }

        // Basic validation: ensure package has relevant files
        if (!$hasValidStructure) {
            return new WP_Error(
                'invalid_package_structure',
                sprintf(
                    /* translators: %s: Asset type */
                    __('Package does not appear to contain valid %s files.', 'wp-rollback'),
                    $assetType
                )
            );
        }

        return [
            'root_directory' => $rootDir,
            'structure_valid' => $hasValidStructure,
            'php_files_found' => $phpFilesFound,
            'css_files_found' => $cssFilesFound,
        ];
    }

    /**
     * Whether a ZIP entry unzips inside the plugin or theme's own folder.
     *
     * unzip_file() writes each entry into the plugins or themes folder exactly as
     * it's named, so the entry must start with "{slug}/" and the rest must be a
     * plain relative path. Theme backups made on Windows by older versions store
     * a full server path after the folder name, e.g.
     * "acme-theme/C:/xampp/htdocs/wp-content/themes/acme-theme/style.css".
     *
     * @param string $entryName ZIP entry name.
     * @param string $assetSlug Asset slug, which is also its folder name.
     * @return bool
     */
    private function isInsideAssetFolder(string $entryName, string $assetSlug): bool
    {
        $folder = $assetSlug . '/';

        // ZIP entries always use "/". A "\" is a separator on Windows but part of
        // the file name elsewhere, so the files wouldn't land in the same place.
        if (strpos($entryName, $folder) !== 0 || strpos($entryName, '\\') !== false) {
            return false;
        }

        $relativePath = substr($entryName, strlen($folder));
        if ('' === $relativePath) {
            return true;
        }

        $segments = explode('/', $relativePath);

        // A directory entry ends with "/", which leaves an empty last segment.
        if ('' === end($segments)) {
            array_pop($segments);
        }

        foreach ($segments as $segment) {
            // "" (from "//"), ".", ".." and a drive letter like "C:" all mean a
            // server path or a path that climbs out of the folder.
            if (in_array($segment, ['', '.', '..'], true) || 1 === preg_match('/^[A-Za-z]:$/', $segment)) {
                return false;
            }
        }

        return true;
    }



        /**
     * Validate file security using basic file monitoring
     * 
     * Follows WordPress core approach: no file extension restrictions,
     * no pattern-based scanning (too prone to false positives).
     * Focuses on structural validation and file size monitoring only.
     *
     * @param array<int, array{name: string, size: int}> $entries ZIP entries
     * @return array Validation results
     */
    private function validateFileSecurity(array $entries): array
    {
        $oversizedFiles = [];
        $totalFilesChecked = 0;

        foreach ($entries as $entry) {
            $filename = $entry['name'];
            
            // Skip directories and meta files
            if (substr($filename, -1) === '/' || strpos($filename, '__MACOSX/') === 0) {
                continue;
            }

            $totalFilesChecked++;

            // Check file size (5MB limit for individual files)
            if ($entry['size'] > 5242880) {
                $oversizedFiles[] = [
                    'file' => $filename,
                    'size' => $entry['size']
                ];
            }
        }

        $phpFilesFound = $this->countPhpFiles($entries);

        return [
            'files_checked' => $totalFilesChecked,
            'oversized_files' => count($oversizedFiles),
            'php_files_found' => $phpFilesFound,
            'validation_method' => 'wordpress_core_approach'
        ];
    }

    /**
     * Count PHP files in the package for reporting
     *
     * @param array<int, array{name: string, size: int}> $entries ZIP entries
     * @return int Number of PHP files found
     */
    private function countPhpFiles(array $entries): int
    {
        $phpCount = 0;
        foreach ($entries as $entry) {
            $extension = strtolower(pathinfo($entry['name'], PATHINFO_EXTENSION));
            if (in_array($extension, ['php', 'inc', 'phtml'], true)) {
                $phpCount++;
            }
        }
        return $phpCount;
    }

    /**
     * Read the entry list of a ZIP package
     *
     * Uses ZipArchive when PHP's Zip extension is loaded and WordPress Core's
     * PclZip library otherwise, the same fallback unzip_file() uses, so a
     * package validates on every server WordPress can install it on.
     *
     * @param string $packagePath Path to ZIP package
     * @return array<int, array{name: string, size: int}>|WP_Error Entries or error
     */
    private function readZipEntries(string $packagePath)
    {
        if (class_exists('ZipArchive')) {
            return $this->readZipEntriesWithZipArchive($packagePath);
        }

        return $this->readZipEntriesWithPclZip($packagePath);
    }

    /**
     * Read ZIP entries using ZipArchive (preferred method)
     *
     * @param string $packagePath Path to ZIP package
     * @return array<int, array{name: string, size: int}>|WP_Error Entries or error
     */
    private function readZipEntriesWithZipArchive(string $packagePath)
    {
        $zip = new ZipArchive();
        $result = $zip->open($packagePath, ZipArchive::CHECKCONS);

        if ($result !== true) {
            return new WP_Error(
                'zip_corrupt',
                sprintf(
                    /* translators: %d: ZIP library error code */
                    __('ZIP file appears to be corrupted or invalid. Error code: %d', 'wp-rollback'),
                    $result
                )
            );
        }

        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $entries[] = [
                'name' => (string) $zip->getNameIndex($i),
                'size' => $stat ? (int) $stat['size'] : 0,
            ];
        }
        $zip->close();

        return $entries;
    }

    /**
     * Read ZIP entries using PclZip (WordPress Core fallback)
     *
     * Loads and calls PclZip the way _unzip_file_pclzip() does. listContent()
     * parses the archive's central directory, so a file that isn't a ZIP or
     * was cut short during download is rejected here too. Like PclZip's own
     * extraction, the names it returns have "//", "." and ".." resolved.
     *
     * @param string $packagePath Path to ZIP package
     * @return array<int, array{name: string, size: int}>|WP_Error Entries or error
     */
    private function readZipEntriesWithPclZip(string $packagePath)
    {
        // Load PclZip library from WordPress Core
        if (!class_exists('PclZip')) {
            require_once ABSPATH . 'wp-admin/includes/class-pclzip.php';
        }

        mbstring_binary_safe_encoding();

        $archive = new PclZip($packagePath);
        $archiveFiles = $archive->listContent();

        reset_mbstring_encoding();

        if (!is_array($archiveFiles)) {
            return new WP_Error(
                'zip_corrupt',
                sprintf(
                    /* translators: %d: ZIP library error code */
                    __('ZIP file appears to be corrupted or invalid. Error code: %d', 'wp-rollback'),
                    $archive->errorCode()
                ),
                $archive->errorInfo(true)
            );
        }

        $entries = [];
        foreach ($archiveFiles as $file) {
            $entries[] = [
                'name' => (string) $file['filename'],
                'size' => (int) $file['size'],
            ];
        }

        return $entries;
    }
} 