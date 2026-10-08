<?php

/**
 * Migration Repository
 *
 * Handles storage and retrieval of migration state in wp_options.
 *
 * @package WpRollback\SharedCore\Migrations
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Migrations;

use WpRollback\SharedCore\Migrations\DTO\MigrationLogDTO;

/**
 * Class MigrationRepository
 */
class MigrationRepository
{
    /**
     * @var string Option name in wp_options
     */
    public const OPTION_NAME = 'wp_rollback_migrations_log';

    /**
     * Status constants
     */
    public const STATUS_PENDING  = 'pending';
    public const STATUS_RUNNING  = 'running';
    public const STATUS_SUCCESS  = 'success';
    public const STATUS_FAILED   = 'failed';
    public const STATUS_REVERTED = 'reverted';

    /**
     * Get all recorded migration logs.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getLogs(): array
    {
        $logs = get_option(self::OPTION_NAME, []);
        return is_array($logs) ? $logs : [];
    }

    /**
     * Get log for a specific migration.
     *
     * @param string $id Migration ID
     * @return MigrationLogDTO|null
     */
    public function getMigrationLog(string $id): ?MigrationLogDTO
    {
        $logs = $this->getLogs();
        $rawLog = $logs[$id] ?? null;

        return MigrationLogDTO::fromArray($rawLog, $id);
    }

    /**
     * Check if a migration has completed successfully.
     *
     * @param string $id Migration ID
     * @return bool
     */
    public function isCompleted(string $id): bool
    {
        $log = $this->getMigrationLog($id);
        return $log !== null && $log->isCompleted();
    }

    /**
     * Record the start of a migration.
     *
     * @param string $id Migration ID
     * @param int    $timestamp Migration timestamp
     * @param string $label Migration label
     * @param string $description Migration description
     * @return void
     */
    public function recordStart(string $id, int $timestamp, string $label, string $description): void
    {
        $logs = $this->getLogs();

        $existing = MigrationLogDTO::fromArray($logs[$id] ?? null, $id);
        $attempts = $existing ? $existing->getAttempts() + 1 : 1;

        $dto = new MigrationLogDTO(
            $id,
            $timestamp,
            $label,
            $description,
            self::STATUS_RUNNING,
            time(),
            null,
            null,
            null,
            $attempts
        );

        $logs[$id] = $dto->toArray();

        update_option(self::OPTION_NAME, $logs, false);
    }

    /**
     * Record successful completion of a migration.
     *
     * @param string $id Migration ID
     * @return void
     */
    public function recordSuccess(string $id): void
    {
        $logs = $this->getLogs();

        if (isset($logs[$id])) {
            $logs[$id]['status']       = self::STATUS_SUCCESS;
            $logs[$id]['completed_at'] = time();
            $logs[$id]['error']        = null;

            update_option(self::OPTION_NAME, $logs, false);
        }
    }

    /**
     * Record failure of a migration.
     *
     * @param string     $id Migration ID
     * @param \Throwable $error Exception/Error object
     * @return void
     */
    public function recordFailure(string $id, \Throwable $error): void
    {
        $logs = $this->getLogs();

        if (isset($logs[$id])) {
            $logs[$id]['status']       = self::STATUS_FAILED;
            $logs[$id]['completed_at'] = time();
            $logs[$id]['error']        = [
                'message' => $error->getMessage(),
                'code'    => $error->getCode(),
                'file'    => $error->getFile(),
                'line'    => $error->getLine(),
                'trace'   => $error->getTraceAsString(),
            ];

            update_option(self::OPTION_NAME, $logs, false);
        }
    }

    /**
     * Record successful revert of a migration.
     *
     * @param string $id Migration ID
     * @return void
     */
    public function recordRevertSuccess(string $id): void
    {
        $logs = $this->getLogs();

        if (isset($logs[$id])) {
            $logs[$id]['status']      = self::STATUS_REVERTED;
            $logs[$id]['reverted_at'] = time();
            $logs[$id]['error']       = null;

            update_option(self::OPTION_NAME, $logs, false);
        }
    }

    /**
     * Record failure during migration revert.
     *
     * @param string     $id Migration ID
     * @param \Throwable $error Exception/Error object
     * @return void
     */
    public function recordRevertFailure(string $id, \Throwable $error): void
    {
        $logs = $this->getLogs();

        if (isset($logs[$id])) {
            $logs[$id]['error'] = [
                'message' => 'Revert failed: ' . $error->getMessage(),
                'code'    => $error->getCode(),
                'file'    => $error->getFile(),
                'line'    => $error->getLine(),
                'trace'   => $error->getTraceAsString(),
            ];

            update_option(self::OPTION_NAME, $logs, false);
        }
    }

    /**
     * Clear log entry for a migration to allow retrying or re-running.
     *
     * @param string $id Migration ID
     * @return void
     */
    public function clearLog(string $id): void
    {
        $logs = $this->getLogs();

        if (isset($logs[$id])) {
            unset($logs[$id]);
            update_option(self::OPTION_NAME, $logs, false);
        }
    }
}
