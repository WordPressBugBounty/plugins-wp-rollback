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
class Cleanup implements RollbackStep
{
    /**
     * @inheritdoc
     * @since 1.0.0
     */
    public static function id(): string
    {
        return 'cleanup';
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

        $package = get_transient("wpr_{$assetType}_{$assetSlug}_package");

        // Check if the package exists and delete a package file.
        if ($package) {
            unlink($package); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink
        }

        // Delete the package transient.
        delete_transient("wpr_{$assetType}_{$assetSlug}_package");

        return new RollbackStepResult(true, $rollbackApiRequestDTO);
    }

    /**
     * @inheritdoc
     * @since 1.0.0
     */
    public static function rollbackProcessingMessage(): string
    {
        return esc_html__('Cleaning up temporary files…', 'wp-rollback');
    }
} 