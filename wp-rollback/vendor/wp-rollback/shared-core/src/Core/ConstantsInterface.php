<?php

/**
 * @package WpRollback\SharedCore\Core
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Core;

/**
 * Interface for plugin constants.
 *
 */
interface ConstantsInterface
{
    /**
     * Get the plugin text domain.
     *
     *
     * @return string
     */
    public function getTextDomain(): string;
    
    /**
     * Get the plugin version.
     *
     *
     * @return string
     */
    public function getVersion(): string;
    
    /**
     * Get the plugin slug.
     *
     *
     * @return string
     */
    public function getSlug(): string;
    
    /**
     * Get the nonce name.
     *
     *
     * @return string
     */
    public function getNonce(): string;

    /**
     * Get the plugin base name.
     *
     *
     * @return string
     */
    public function getBasename(): string;

    /**
     * Get the plugin directory path.
     *
     *
     * @return string
     */
    public function getPluginDir(): string;

    /**
     * Get the plugin URL.
     *
     *
     * @return string
     */
    public function getPluginUrl(): string;

    /**
     * Get the plugin assets URL.
     *
     *
     * @return string
     */
    public function getAssetsUrl(): string;
} 