<?php

/**
 * @package WpRollback\SharedCore\Rollbacks\Contract
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Rollbacks\Contract;

use WpRollback\SharedCore\Rollbacks\DTO\RollbackApiRequestDTO;

/**
 */
interface RollbackStep
{
    /**
     */
    public static function id(): string;

    /**
     */
    public function execute(RollbackApiRequestDTO $rollbackApiRequestDTO): RollbackStepResult;

    /**
     */
    public static function rollbackProcessingMessage(): string;
} 