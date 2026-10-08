<?php

/**
 * Migration Log Data Transfer Object
 *
 * Encapsulates migration execution state and history.
 *
 * @package WpRollback\SharedCore\Migrations\DTO
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Migrations\DTO;

use WpRollback\SharedCore\Migrations\MigrationRepository;

/**
 * Class MigrationLogDTO
 */
class MigrationLogDTO
{
    /**
     * @var string Migration ID
     */
    private string $id;

    /**
     * @var int Migration timestamp
     */
    private int $timestamp;

    /**
     * @var string Human-readable label
     */
    private string $label;

    /**
     * @var string Description of migration task
     */
    private string $description;

    /**
     * @var string Migration status
     */
    private string $status;

    /**
     * @var int|null Execution start timestamp
     */
    private ?int $startedAt;

    /**
     * @var int|null Execution completion timestamp
     */
    private ?int $completedAt;

    /**
     * @var int|null Revert timestamp
     */
    private ?int $revertedAt;

    /**
     * @var array<string, mixed>|null Error details if failed
     */
    private ?array $error;

    /**
     * @var int Execution attempts count
     */
    private int $attempts;

    /**
     * MigrationLogDTO constructor.
     *
     * @param string                    $id          Migration ID
     * @param int                       $timestamp   Migration timestamp
     * @param string                    $label       Human-readable label
     * @param string                    $description Description of migration task
     * @param string                    $status      Migration status
     * @param int|null                  $startedAt   Execution start timestamp
     * @param int|null                  $completedAt Execution completion timestamp
     * @param int|null                  $revertedAt  Revert timestamp
     * @param array<string, mixed>|null $error       Error details if failed
     * @param int                       $attempts    Execution attempts count
     */
    public function __construct(
        string $id,
        int $timestamp = 0,
        string $label = '',
        string $description = '',
        string $status = MigrationRepository::STATUS_PENDING,
        ?int $startedAt = null,
        ?int $completedAt = null,
        ?int $revertedAt = null,
        ?array $error = null,
        int $attempts = 1
    ) {
        $this->id          = $id;
        $this->timestamp   = $timestamp;
        $this->label       = $label;
        $this->description = $description;
        $this->status      = $status;
        $this->startedAt   = $startedAt;
        $this->completedAt = $completedAt;
        $this->revertedAt  = $revertedAt;
        $this->error       = $error;
        $this->attempts    = $attempts;
    }

    /**
     * Get migration ID.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get migration timestamp.
     *
     * @return int
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * Get migration label.
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Get migration description.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Get migration status.
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Get start timestamp.
     *
     * @return int|null
     */
    public function getStartedAt(): ?int
    {
        return $this->startedAt;
    }

    /**
     * Get completion timestamp.
     *
     * @return int|null
     */
    public function getCompletedAt(): ?int
    {
        return $this->completedAt;
    }

    /**
     * Get revert timestamp.
     *
     * @return int|null
     */
    public function getRevertedAt(): ?int
    {
        return $this->revertedAt;
    }

    /**
     * Get error details.
     *
     * @return array<string, mixed>|null
     */
    public function getError(): ?array
    {
        return $this->error;
    }

    /**
     * Get execution attempts count.
     *
     * @return int
     */
    public function getAttempts(): int
    {
        return $this->attempts;
    }

    /**
     * Check if migration has completed successfully.
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->status === MigrationRepository::STATUS_SUCCESS;
    }

    /**
     * Alias for isCompleted.
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->isCompleted();
    }

    /**
     * Check if migration has failed.
     *
     * @return bool
     */
    public function isFailed(): bool
    {
        return $this->status === MigrationRepository::STATUS_FAILED;
    }

    /**
     * Check if migration is running.
     *
     * @return bool
     */
    public function isRunning(): bool
    {
        return $this->status === MigrationRepository::STATUS_RUNNING;
    }

    /**
     * Check if migration is pending.
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === MigrationRepository::STATUS_PENDING;
    }

    /**
     * Check if migration is reverted.
     *
     * @return bool
     */
    public function isReverted(): bool
    {
        return $this->status === MigrationRepository::STATUS_REVERTED;
    }

    /**
     * Create a DTO instance from raw array data.
     *
     * Returns null if data is not an array or has no valid ID.
     *
     * @param mixed  $data Raw data from storage
     * @param string $id   Fallback ID if not in array
     * @return self|null
     */
    public static function fromArray($data, string $id = ''): ?self
    {
        if (!is_array($data)) {
            return null;
        }

        $migrationId = !empty($data['id']) && is_string($data['id']) ? $data['id'] : $id;
        if ($migrationId === '') {
            return null;
        }

        return new self(
            $migrationId,
            isset($data['timestamp']) ? (int) $data['timestamp'] : 0,
            isset($data['label']) && is_string($data['label']) ? $data['label'] : '',
            isset($data['description']) && is_string($data['description']) ? $data['description'] : '',
            isset($data['status']) && is_string($data['status']) ? $data['status'] : MigrationRepository::STATUS_PENDING,
            isset($data['started_at']) && is_numeric($data['started_at']) ? (int) $data['started_at'] : null,
            isset($data['completed_at']) && is_numeric($data['completed_at']) ? (int) $data['completed_at'] : null,
            isset($data['reverted_at']) && is_numeric($data['reverted_at']) ? (int) $data['reverted_at'] : null,
            isset($data['error']) && is_array($data['error']) ? $data['error'] : null,
            isset($data['attempts']) ? (int) $data['attempts'] : 1
        );
    }

    /**
     * Convert DTO to array representation for persistence.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'timestamp'    => $this->timestamp,
            'label'        => $this->label,
            'description'  => $this->description,
            'status'       => $this->status,
            'started_at'   => $this->startedAt,
            'completed_at' => $this->completedAt,
            'reverted_at'  => $this->revertedAt,
            'error'        => $this->error,
            'attempts'     => $this->attempts,
        ];
    }
}
