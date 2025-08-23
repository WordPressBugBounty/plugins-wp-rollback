<?php

/**
 * @package WpRollback\SharedCore\Rollbacks\Registry
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Rollbacks\Registry;

use InvalidArgumentException;
use WpRollback\SharedCore\Core\Exceptions\BindingResolutionException;
use WpRollback\SharedCore\Core\SharedCore;
use WpRollback\SharedCore\Rollbacks\Contract\RollbackStep;

/**
 * @since 1.0.0
 */
class RollbackStepRegisterer
{
    /**
     * @since 1.0.0
     * @var class-string<RollbackStep>[]
     */
    protected array $steps = [];

    /**
     * Get the base rollback steps that should be included in all implementations
     * 
     * These are the core steps that every rollback process needs:
     * 1. MaintenanceMode - Prevents frontend access during rollback
     * 2. DownloadAsset - Downloads the rollback version
     * 3. BackupAsset - Creates a backup of current version
     * 4. ReplaceAsset - Performs the actual file replacement
     * 5. Cleanup - Removes temp files and ALWAYS disables maintenance mode
     * 
     * Note: ValidatePackage is NOT included as it's a pro feature
     *
     * @since 1.0.0
     * @return array<class-string<RollbackStep>> Array of rollback step class names
     */
    public static function getBaseSteps(): array
    {
        return [
            \WpRollback\SharedCore\Rollbacks\RollbackSteps\MaintenanceMode::class,
            \WpRollback\SharedCore\Rollbacks\RollbackSteps\DownloadAsset::class,
            \WpRollback\SharedCore\Rollbacks\RollbackSteps\BackupAsset::class,
            // ValidatePackage is intentionally not included here as it's replaced in free version
            \WpRollback\SharedCore\Rollbacks\RollbackSteps\ReplaceAsset::class,
            \WpRollback\SharedCore\Rollbacks\RollbackSteps\Cleanup::class,
        ];
    }

    /**
     * @since 1.0.0
     *
     * @param class-string<RollbackStep> $rollbackStep
     * @param class-string<RollbackStep> $stepClass
     */
    public function registerAfter(string $rollbackStep, string $stepClass): void
    {
        // Find the key for the target step
        $targetKey = array_search($stepClass, $this->steps, true);

        if (false === $targetKey) {
            throw new InvalidArgumentException('Invalid step class provided.');
        }

        // Create a new array by inserting the new step after the target
        $newSteps = [];
        foreach ($this->steps as $key => $step) {
            $newSteps[] = $step;
            if ($key === $targetKey) {
                $newSteps[] = $rollbackStep;
            }
        }

        $this->steps = $newSteps;
    }

    /**
     * Add a step to the beginning of the steps array
     * 
     * @since 1.0.0
     * @param class-string<RollbackStep> $stepClass
     */
    public function addStep(string $stepClass): void
    {
        $this->steps[] = $stepClass;
    }

    /**
     * @since 1.0.0
     *
     * @param class-string<RollbackStep> $rollbackStep
     * @param class-string<RollbackStep> $stepClass
     */
    public function replaceStep(string $rollbackStep, string $stepClass): void
    {
        $stepIndex = array_search($stepClass, $this->steps, true);

        if (false === $stepIndex) {
            throw new InvalidArgumentException('Invalid step class provided.');
        }

        $this->steps[$stepIndex] = $rollbackStep;
    }

    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function getRollbackStepById(string $id): ?RollbackStep
    {
        $steps = $this->getAllRollbackStepsIds();
        $stepIndex = array_search($id, $steps, true);

        if (false === $stepIndex) {
            return null;
        }

        return SharedCore::container()->make($this->steps[$stepIndex]);
    }

    /**
     * @since 1.0.0
     * @return class-string<RollbackStep>[]
     */
    public function getAllRollbackSteps(): array
    {
        return $this->steps;
    }

    /**
     * @since 1.0.0
     */
    public function getAllRollbackStepsIds(): array
    {
        return array_map(fn($step) => $step::id(), $this->steps);
    }

    /**
     * Register a list of rollback steps in the given order
     * 
     * @since 1.0.0
     * @param class-string<RollbackStep>[] $steps The steps to register, in order
     * @return void
     */
    public function register(array $steps): void
    {
        foreach ($steps as $step) {
            $this->addStep($step);
        }
    }
} 