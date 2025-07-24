<?php

/**
 * Loggable Exception
 *
 * This file is responsible for providing loggable exception contract.
 *
 * @package WpRollback\SharedCore\Core\Contracts
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Core\Contracts;

interface LoggableException
{
    /**
     * Returns the human-readable message for the log
     *
     * @since 1.0.0
     */
    public function getLogMessage(): string;

    /**
     * Returns an associated array with additional context for the log
     *
     * @since 1.0.0
     */
    public function getLogContext(): array;
} 