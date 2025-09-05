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

/**
 * @since 1.0.0
 */
class DownloadAsset implements RollbackStep
{
    /**
     * @inheritdoc
     * @since 1.0.0
     */
    public static function id(): string
    {
        return 'download-asset';
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

        // Prepare download URL.
        $url = $this->getDownloadUrl($assetType, $assetSlug, $assetVersion);

        // Store the asset download url in transient.
        set_transient("wpr_{$assetType}_{$assetSlug}_download_url", $url, HOUR_IN_SECONDS);
        include_once ABSPATH . 'wp-admin/includes/file.php';

        // In multisite, temporarily allow ZIP downloads for super admins
        $filterAdded = false;
        if (is_multisite() && is_super_admin()) {
            add_filter('wp_check_filetype_and_ext', [$this, 'allowZipDownload'], 10, 5);
            $filterAdded = true;
        }

        // Download asset temporarily.
        $package = download_url($url);
        
        // Remove the filter after download
        if ($filterAdded) {
            remove_filter('wp_check_filetype_and_ext', [$this, 'allowZipDownload'], 10);
        }
        
        set_transient("wpr_{$assetType}_{$assetSlug}_package", $package, HOUR_IN_SECONDS);

        return new RollbackStepResult(true, $rollbackApiRequestDTO);
    }

    /**
     * @since 1.0.0
     */
    public static function rollbackProcessingMessage(): string
    {
        return esc_html__('Download requested version…', 'wp-rollback');
    }

    /**
     * @since 1.0.0
     */
    protected function getDownloadUrl($assetType, $assetSlug, $assetVersion): string
    {
        return sprintf(
            'https://downloads.wordpress.org/%1$s/%2$s.%3$s.zip',
            $assetType,
            $assetSlug,
            $assetVersion
        );
    }
    
    /**
     * Allow ZIP files to be downloaded in multisite
     *
     * @since 1.0.0
     * @param array  $checked File data array
     * @param string $file    Full path to the file
     * @param string $filename The name of the file
     * @param string[] $mimes Array of allowed mime types
     * @param string|false $real_mime The actual mime type or false if the type cannot be determined
     * @return array Modified file data array
     */
    public function allowZipDownload($checked, $file, $filename, $mimes, $real_mime)
    {
        // If WordPress couldn't determine the file type, but it's from WordPress.org downloads
        if (!$checked['type'] && strpos($file, 'wordpress.org') !== false) {
            $checked['ext'] = 'zip';
            $checked['type'] = 'application/zip';
        }
        
        return $checked;
    }
} 