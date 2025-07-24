<?php

/**
 * Validate package rollback step.
 *
 * @package WpRollback\SharedCore\Rollbacks\RollbackSteps
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Rollbacks\RollbackSteps;

use WpRollback\SharedCore\Rollbacks\Services\PackageValidationService;
use WpRollback\SharedCore\Rollbacks\DTO\RollbackApiRequestDTO;
use WpRollback\SharedCore\Rollbacks\Contract\RollbackStep;
use WpRollback\SharedCore\Rollbacks\Contract\RollbackStepResult;

/**
 * Rollback step for validating package integrity using WordPress Core methods
 *
 * @since 1.0.0
 */
class ValidatePackage implements RollbackStep
{
    /**
     * Package validation service instance
     *
     * @since 1.0.0
     * @var PackageValidationService
     */
    private PackageValidationService $validationService;

    /**
     * Constructor
     *
     * @since 1.0.0
     * @param PackageValidationService $validationService The package validation service
     */
    public function __construct(PackageValidationService $validationService)
    {
        $this->validationService = $validationService;
    }

    /**
     * @inheritdoc
     * @since 1.0.0
     */
    public static function id(): string
    {
        return 'validate-package';
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

        // Get the downloaded package path from transient
        $package = get_transient("wpr_{$assetType}_{$assetSlug}_package");

        // Validate package exists
        if (empty($package) || !is_string($package)) {
            return new RollbackStepResult(
                false,
                $rollbackApiRequestDTO,
                __('No package found for validation.', 'wp-rollback')
            );
        }

        // Handle WP_Error case
        if (is_wp_error($package)) {
            return new RollbackStepResult(
                false,
                $rollbackApiRequestDTO,
                sprintf(
                    /* translators: %s: Error message */
                    __('Package error during validation: %s', 'wp-rollback'),
                    $package->get_error_message()
                )
            );
        }

        // Check if file exists
        if (!file_exists($package)) {
            return new RollbackStepResult(
                false,
                $rollbackApiRequestDTO,
                __('Package file not found for validation.', 'wp-rollback')
            );
        }

        // Perform package validation using WordPress Core methods
        $validationResult = $this->validationService->validatePackage(
            $package,
            $assetType,
            $assetSlug,
            $assetVersion
        );

        if (!$validationResult['success']) {
            // Package validation failed - this is a security/integrity concern
            $errorMessage = $validationResult['message'];

            return new RollbackStepResult(
                false,
                $rollbackApiRequestDTO,
                $errorMessage,
                null,
                [
                    'validation_status' => 'failed',
                    'validation_details' => $validationResult['details'] ?? []
                ]
            );
        }

        // Success - package is valid according to WordPress Core standards
        $details = $validationResult['details'] ?? [];
        $filesChecked = $details['security']['files_checked'] ?? 0;
        
        return new RollbackStepResult(
            true,
            $rollbackApiRequestDTO,
            $validationResult['message'],
            null,
            [
                'validation_status' => 'validated',
                'files_checked' => $filesChecked,
                'validation_details' => $details,
                'validation_type' => 'wordpress_core_methods'
            ]
        );
    }

    /**
     * @inheritdoc
     * @since 1.0.0
     */
    public static function rollbackProcessingMessage(): string
    {
        return esc_html__('Validating package integrity…', 'wp-rollback');
    }
} 