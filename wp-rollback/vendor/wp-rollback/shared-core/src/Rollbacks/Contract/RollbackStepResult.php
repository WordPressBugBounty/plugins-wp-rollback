<?php

/**
 * @package WpRollback\SharedCore\Rollbacks\Contract
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Rollbacks\Contract;

use Throwable;
use WpRollback\SharedCore\Rollbacks\DTO\RollbackApiRequestDTO;

/**
 * Class representing the result of a rollback step
 * 
 */
class RollbackStepResult
{
    /**
     * @var bool
     */
    private bool $success;

    /**
     * @var RollbackApiRequestDTO
     */
    private RollbackApiRequestDTO $rollbackApiRequestDTO;

    /**
     * @var string
     */
    private string $message;

    /**
     * @var Throwable|null
     */
    private ?Throwable $exception;

    /**
     * @var array
     */
    private array $data;

    /**
     * @param bool $success Whether the step was successful
     * @param RollbackApiRequestDTO $rollbackApiRequestDTO The request data
     * @param string $message A message describing the result
     * @param Throwable|null $exception An exception if one occurred
     * @param array $data Additional data from the step
     */
    public function __construct(
        bool $success, 
        RollbackApiRequestDTO $rollbackApiRequestDTO, 
        string $message = '',
        ?Throwable $exception = null,
        array $data = []
    ) {
        $this->success = $success;
        $this->rollbackApiRequestDTO = $rollbackApiRequestDTO;
        $this->message = $message;
        $this->exception = $exception;
        $this->data = $data;
    }

    /**
     * Check if the step was successful
     * 
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Get the rollback API request DTO
     * 
     * @return RollbackApiRequestDTO
     */
    public function getRollbackApiRequestDTO(): RollbackApiRequestDTO
    {
        return $this->rollbackApiRequestDTO;
    }

    /**
     * Get the result message
     * 
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Get the exception if one occurred
     * 
     * @return Throwable|null
     */
    public function getException(): ?Throwable
    {
        return $this->exception;
    }

    /**
     * Get the step result data
     * 
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Create a successful result
     * 
     * @param RollbackApiRequestDTO $dto The request data
     * @param string $message A success message
     * @return self
     */
    public static function success(RollbackApiRequestDTO $dto, string $message = ''): self
    {
        return new self(true, $dto, $message);
    }

    /**
     * Create a failed result
     * 
     * @param RollbackApiRequestDTO $dto The request data
     * @param string $message An error message
     * @param Throwable|null $exception An exception if one occurred
     * @return self
     */
    public static function failure(
        RollbackApiRequestDTO $dto, 
        string $message = '',
        ?Throwable $exception = null
    ): self {
        return new self(false, $dto, $message, $exception);
    }

    /**
     * Create a failure result from an exception
     * 
     * @param RollbackApiRequestDTO $dto The request data
     * @param Throwable $exception The exception that occurred
     * @return self
     */
    public static function fromException(RollbackApiRequestDTO $dto, Throwable $exception): self
    {
        return new self(false, $dto, $exception->getMessage(), $exception);
    }
} 