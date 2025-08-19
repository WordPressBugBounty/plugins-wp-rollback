<?php

/**
 * Maintenance mode rollback step.
 *
 * @package WpRollback\SharedCore\Rollbacks\RollbackSteps
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Rollbacks\RollbackSteps;

use WpRollback\SharedCore\Rollbacks\Services\MaintenanceService;
use WpRollback\SharedCore\Rollbacks\DTO\RollbackApiRequestDTO;
use WpRollback\SharedCore\Rollbacks\Contract\RollbackStep;
use WpRollback\SharedCore\Rollbacks\Contract\RollbackStepResult;

/**
 * Rollback step for enabling maintenance mode during rollback process
 *
 * @since 1.0.0
 */
class MaintenanceMode implements RollbackStep
{
    /**
     * Maintenance service instance
     *
     * @since 1.0.0
     * @var MaintenanceService
     */
    private MaintenanceService $maintenanceService;

    /**
     * Constructor
     *
     * @since 1.0.0
     * @param MaintenanceService $maintenanceService The maintenance service
     */
    public function __construct(MaintenanceService $maintenanceService)
    {
        $this->maintenanceService = $maintenanceService;
    }

    /**
     * @inheritdoc
     * @since 1.0.0
     */
    public static function id(): string
    {
        return 'maintenance-mode';
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

        // Check if maintenance mode is already active
        if ($this->maintenanceService->isMaintenanceModeActive()) {
            return new RollbackStepResult(
                true,
                $rollbackApiRequestDTO,
                __('Maintenance mode is already active.', 'wp-rollback'),
                null,
                [
                    'maintenance_status' => 'already_active',
                    'asset_type' => $assetType,
                    'asset_slug' => $assetSlug,
                    'asset_version' => $assetVersion
                ]
            );
        }

        // Enable maintenance mode
        $enabled = $this->maintenanceService->enableMaintenanceMode();

        if (!$enabled) {
            // Log the failure but don't stop the rollback process
            // Maintenance mode is helpful but not critical
            error_log(sprintf(
                'WP Rollback: Failed to enable maintenance mode for %s rollback of %s to version %s',
                $assetType,
                $assetSlug,
                $assetVersion
            ));

            return new RollbackStepResult(
                true, // Continue with rollback even if maintenance mode fails
                $rollbackApiRequestDTO,
                __('Could not enable maintenance mode, but continuing with rollback.', 'wp-rollback'),
                null,
                [
                    'maintenance_status' => 'failed_non_critical',
                    'asset_type' => $assetType,
                    'asset_slug' => $assetSlug,
                    'asset_version' => $assetVersion
                ]
            );
        }

        // Store maintenance mode state in transient for cleanup tracking
        set_transient(
            "wpr_maintenance_mode_{$assetType}_{$assetSlug}",
            [
                'enabled_at' => time(),
                'version' => $assetVersion,
                'process_id' => uniqid('wpr_', true)
            ],
            600 // 10 minutes expiration
        );

        return new RollbackStepResult(
            true,
            $rollbackApiRequestDTO,
            __('Maintenance mode enabled successfully.', 'wp-rollback'),
            null,
            [
                'maintenance_status' => 'enabled',
                'asset_type' => $assetType,
                'asset_slug' => $assetSlug,
                'asset_version' => $assetVersion,
                'enabled_at' => time()
            ]
        );
    }

    /**
     * @inheritdoc
     * @since 1.0.0
     */
    public static function rollbackProcessingMessage(): string
    {
        return esc_html__('Enabling maintenance mode…', 'wp-rollback');
    }
}
