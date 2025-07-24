<?php

/**
 * Loggable Traits
 *
 * This class is responsible for providing loggable traits to exceptions.
 *
 * @package WpRollback\SharedCore\Core\Exceptions\Traits
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Core\Exceptions\Traits;

trait Loggable
{
    /**
     * Gets the Exception::getMessage() method
     *
     * @since 1.0.0
     */
    abstract public function getMessage();

    /**
     * Returns the human-readable log message
     *
     * @since 1.0.0
     */
    public function getLogMessage(): string
    {
        return $this->getMessage();
    }

    /**
     * Returns an array with the basic context details
     *
     * @since 1.0.0
     *
     * @return array
     */
    public function getLogContext(): array
    {
        return [
            'category'  => 'Uncaught Exception',
            'exception' => [
                'File'    => basename($this->getFile()),
                'Line'    => $this->getLine(),
                'Message' => $this->getMessage(),
                'Code'    => $this->getCode(),
            ],
        ];
    }
} 