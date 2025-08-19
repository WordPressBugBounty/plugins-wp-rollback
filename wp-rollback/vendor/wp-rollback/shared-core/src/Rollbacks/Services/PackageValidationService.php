<?php

/**
 * Package validation service for verifying plugin and theme integrity using WordPress Core methods.
 *
 * @package WpRollback\SharedCore\Rollbacks\Services
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Rollbacks\Services;

use WP_Error;
use ZipArchive;

/**
 * Service for validating packages using WordPress Core validation methods
 *
 * @since 1.0.0
 */
class PackageValidationService
{

    /**
     * Validate a downloaded package using WordPress Core methods
     *
     * @since 1.0.0
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

        // 1. Validate using WordPress Core file functions first
        $coreValidation = $this->validateWithWordPressCore($packagePath);
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
        $zipValidation = $this->validateZipIntegrity($packagePath);
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
        $structureValidation = $this->validatePackageStructure($packagePath, $assetType, $assetSlug);
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
        $securityValidation = $this->validateFileSecurity($packagePath);
        if (is_wp_error($securityValidation)) {
            return [
                'success' => false,
                'message' => sprintf(
                    /* translators: %s: Error message */
                    __('Security validation failed: %s', 'wp-rollback'),
                    $securityValidation->get_error_message()
                ),
            ];
        }
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
     * @since 1.0.0
     * @param string $packagePath Path to ZIP package
     * @return array|WP_Error Validation results or error
     */
    private function validateWithWordPressCore(string $packagePath)
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

        // First, verify this is actually a ZIP file using ZipArchive
        // This is more reliable than wp_check_filetype_and_ext() for temporary files
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            $zipCheck = $zip->open($packagePath, ZipArchive::CHECKCONS);
            if ($zipCheck !== true) {
                return new WP_Error(
                    'invalid_zip_format',
                    __('Package is not a valid ZIP file format.', 'wp-rollback')
                );
            }
            $zip->close();
        }

        // Use WordPress file type validation
        // Note: This may fail for temporary files from download_url() in multisite
        $fileType = wp_check_filetype_and_ext($packagePath, basename($packagePath));
        
        // For temporary files (like those from download_url()), we may not get an extension
        // If ZipArchive confirmed it's a valid ZIP, we can be more lenient here
        if (!$fileType['ext'] && class_exists('ZipArchive')) {
            // Force ZIP type for valid ZIP files that WordPress can't detect
            $fileType['ext'] = 'zip';
            $fileType['type'] = 'application/zip';
        } elseif (!$fileType['ext'] || $fileType['ext'] !== 'zip') {
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
     * @since 1.0.0
     * @param string $packagePath Path to ZIP package
     * @return array|WP_Error Validation results or error
     */
    private function validateZipIntegrity(string $packagePath)
    {
        // Check file size is reasonable (100MB limit)
        $fileSize = filesize($packagePath);
        if ($fileSize === false || $fileSize > 104857600) {
            return new WP_Error(
                'file_too_large',
                __('Package file is too large for processing.', 'wp-rollback')
            );
        }

        // Validate ZIP using ZipArchive (WordPress dependency)
        if (!class_exists('ZipArchive')) {
            return new WP_Error(
                'zip_not_available',
                __('ZIP functionality is not available for package validation.', 'wp-rollback')
            );
        }

        $zip = new ZipArchive();
        $result = $zip->open($packagePath, ZipArchive::CHECKCONS);

        if ($result !== true) {
            return new WP_Error(
                'zip_corrupt',
                sprintf(
                    /* translators: %d: ZipArchive error code */
                    __('ZIP file appears to be corrupted or invalid. Error code: %d', 'wp-rollback'),
                    $result
                )
            );
        }

        $fileCount = $zip->numFiles;
        $zip->close();

        return [
            'file_count' => $fileCount,
            'file_size' => $fileSize,
            'format_valid' => true,
        ];
    }

    /**
     * Validate package directory structure
     *
     * @since 1.0.0
     * @param string $packagePath Path to ZIP package
     * @param string $assetType   Asset type
     * @param string $assetSlug   Asset slug
     * @return array|WP_Error Validation results or error
     */
    private function validatePackageStructure(string $packagePath, string $assetType, string $assetSlug)
    {
        $zip = new ZipArchive();
        $zip->open($packagePath);

        $rootDir = null;
        $hasValidStructure = false;
        $phpFilesFound = 0;
        $cssFilesFound = 0;

        // Find root directory and validate basic structure
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            
            // Skip __MACOSX and other meta directories
            if (strpos($filename, '__MACOSX/') === 0) {
                continue;
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

        $zip->close();

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
     * Validate file security using basic file monitoring
     * 
     * Follows WordPress core approach: no file extension restrictions,
     * no pattern-based scanning (too prone to false positives).
     * Focuses on structural validation and file size monitoring only.
     *
     * @since 1.0.0
     * @param string $packagePath Path to ZIP package
     * @return array|WP_Error Validation results or error
     */
    private function validateFileSecurity(string $packagePath)
    {
        $zip = new ZipArchive();
        $zip->open($packagePath);

        $oversizedFiles = [];
        $totalFilesChecked = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            
            // Skip directories and meta files
            if (substr($filename, -1) === '/' || strpos($filename, '__MACOSX/') === 0) {
                continue;
            }

            $totalFilesChecked++;

            // Check file size (5MB limit for individual files)
            $stat = $zip->statIndex($i);
            if ($stat && $stat['size'] > 5242880) {
                $oversizedFiles[] = [
                    'file' => $filename,
                    'size' => $stat['size']
                ];
            }
        }

        // Count PHP files before closing
        $phpFilesFound = $this->countPhpFiles($zip);
        
        $zip->close();

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
     * @since 1.0.0
     * @param ZipArchive $zip The ZIP archive
     * @return int Number of PHP files found
     */
    private function countPhpFiles(ZipArchive $zip): int
    {
        $phpCount = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (in_array($extension, ['php', 'inc', 'phtml'], true)) {
                $phpCount++;
            }
        }
        return $phpCount;
    }
} 