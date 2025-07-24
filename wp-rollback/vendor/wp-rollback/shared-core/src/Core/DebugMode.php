<?php

/**
 * @package WpRollback\SharedCore\Core
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Core;

/**
 * Class to handle debug mode settings
 * 
 * @since 1.0.0
 */
class DebugMode
{
    /**
     * @since 1.0.0
     */
    protected bool $isEnabled;

    /**
     * Constructor
     * 
     * @since 1.0.0
     */
    public function __construct(bool $enabled)
    {
        $this->isEnabled = $enabled;
    }

    /**
     * Factory method to create a debug mode instance based on WP_DEBUG constant
     * 
     * @since 1.0.0
     * @return self
     */
    public static function makeWithWpDebugConstant(): self
    {
        return new self(defined('WP_DEBUG') && WP_DEBUG);
    }

    /**
     * Check if debug mode is enabled
     * 
     * @since 1.0.0
     */
    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }
} 