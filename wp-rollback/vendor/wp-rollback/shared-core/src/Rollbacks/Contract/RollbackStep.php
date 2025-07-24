<?php

/**
 * @package WpRollback\SharedCore\Rollbacks\Contract
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Rollbacks\Contract;

use WpRollback\SharedCore\Rollbacks\DTO\RollbackApiRequestDTO;

/**
 * @since 1.0.0
 */
interface RollbackStep
{
    /**
     * @since 1.0.0
     */
    public static function id(): string;

    /**
     * @since 1.0.0
     */
    public function execute(RollbackApiRequestDTO $rollbackApiRequestDTO): RollbackStepResult;

    /**
     * @since 1.0.0
     */
    public static function rollbackProcessingMessage(): string;
} 