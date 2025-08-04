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

        // Download asset temporarily.
        $package = download_url($url);
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
} 