<?php

/**
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
 * @since 1.0.0
 */
class Cleanup implements RollbackStep
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

        $errors = [];

        // CRITICAL: Always disable maintenance mode first
        // This ensures site is accessible even if other cleanup fails
        $maintenanceDisabled = $this->maintenanceService->disableMaintenanceMode();
        if (!$maintenanceDisabled) {
            // Force disable if standard method fails
            $this->maintenanceService->forceDisableMaintenanceMode();
            $errors[] = __('Maintenance mode required forced cleanup.', 'wp-rollback');
        }

        // Clean up maintenance mode transient
        delete_transient("wpr_maintenance_mode_{$assetType}_{$assetSlug}");

        // Clean up package file
        $package = get_transient("wpr_{$assetType}_{$assetSlug}_package");
        if ($package && is_string($package) && file_exists($package)) {
            $deleted = @unlink($package); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink
            if (!$deleted) {
                $errors[] = sprintf(
                    /* translators: %s: Package file path */
                    __('Could not delete package file: %s', 'wp-rollback'),
                    basename($package)
                );
            }
        }

        // Delete the package transient
        delete_transient("wpr_{$assetType}_{$assetSlug}_package");

        // Prepare cleanup summary
        $message = empty($errors) 
            ? __('Cleanup completed successfully.', 'wp-rollback')
            : __('Cleanup completed with warnings.', 'wp-rollback');

        return new RollbackStepResult(
            true, // Always return success to not block the process
            $rollbackApiRequestDTO,
            $message,
            null,
            [
                'maintenance_disabled' => $maintenanceDisabled,
                'warnings' => $errors,
                'cleaned_items' => [
                    'maintenance_file' => !$this->maintenanceService->isMaintenanceModeActive(),
                    'package_file' => !($package && file_exists($package)),
                    'transients' => true
                ]
            ]
        );
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