<?php

/**
 * @package WpRollback\SharedCore\Core
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Core;

/**
 * Class to handle debug mode settings
 * 
 */
class DebugMode
{
    /**
     */
    protected bool $isEnabled;

    /**
     * Constructor
     * 
     */
    public function __construct(bool $enabled)
    {
        $this->isEnabled = $enabled;
    }

    /**
     * Factory method to create a debug mode instance based on WP_DEBUG constant
     * 
     * @return self
     */
    public static function makeWithWpDebugConstant(): self
    {
        return new self(defined('WP_DEBUG') && WP_DEBUG);
    }

    /**
     * Check if debug mode is enabled
     * 
     */
    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }
} 