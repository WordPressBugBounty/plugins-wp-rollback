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
use WpRollback\SharedCore\Rollbacks\Services\BackupService;

/**
 * @since 1.0.0
 */
class BackupAsset implements RollbackStep
{
    /**
     * @var BackupService
     */
    private BackupService $backupService;

    /**
     * Constructor.
     *
     * @since 1.0.0
     * @param BackupService $backupService The backup service
     */
    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * @inheritdoc
     * @since 1.0.0
     */
    public static function id(): string
    {
        return 'backup-asset';
    }

    /**
     * @inheritdoc
     * @since 1.0.0.
     */
    public function execute(RollbackApiRequestDTO $rollbackApiRequestDTO): RollbackStepResult
    {
        $assetType = $rollbackApiRequestDTO->getType();
        $assetSlug = $rollbackApiRequestDTO->getSlug();

        // Skip backup for WordPress.org assets
        if ($this->isWordPressOrgAsset($assetSlug, $assetType)) {
            return new RollbackStepResult(
                true, 
                $rollbackApiRequestDTO,
                __('Archive creation skipped for WordPress.org asset.', 'wp-rollback')
            );
        }

        // Create backup of the current premium asset
        $backupCreated = $this->backupService->createAssetBackup($assetSlug, $assetType);

        if ($backupCreated) {
            return new RollbackStepResult(
                true, 
                $rollbackApiRequestDTO,
                __('Archive created successfully.', 'wp-rollback')
            );
        }

        // If backup fails, continue anyway (non-critical failure)
        return new RollbackStepResult(
            true, 
            $rollbackApiRequestDTO,
            __('Archive creation skipped.', 'wp-rollback')
        );
    }

    /**
     * @inheritdoc
     * @since 1.0.0
     */
    public static function rollbackProcessingMessage(): string
    {
        return esc_html__('Creating version archive…', 'wp-rollback');
    }

    /**
     * Check if an asset is from WordPress.org repository
     *
     * @param string $slug Asset slug
     * @param string $type Asset type (plugin or theme)
     * @return bool True if asset is from WordPress.org, false otherwise
     * @since 1.0.0
     */
    private function isWordPressOrgAsset(string $slug, string $type): bool
    {
        // Check WordPress.org API to see if asset exists there
        $wpOrgApiURL = 'theme' === $type
            ? 'https://api.wordpress.org/themes/info/1.2'
            : 'https://api.wordpress.org/plugins/info/1.2';

        $url = add_query_arg(
            [
                'action' => "{$type}_information",
                'request[slug]' => $slug,
            ],
            $wpOrgApiURL
        );

        $response = wp_remote_get($url, ['timeout' => 10]);
        
        if (is_wp_error($response)) {
            // If we can't reach WordPress.org, assume it's premium to be safe
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);

        // WordPress.org returns either false or an error object when plugin/theme is not found
        return !empty($data) && !isset($data->error);
    }
} 