<?php

/**
 * @package WpRollback\SharedCore\Core
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Core;

/**
 * Interface for plugin constants.
 *
 * @since 1.0.0
 */
interface ConstantsInterface
{
    /**
     * Get the plugin text domain.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function getTextDomain(): string;
    
    /**
     * Get the plugin version.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function getVersion(): string;
    
    /**
     * Get the plugin slug.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function getSlug(): string;
    
    /**
     * Get the nonce name.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function getNonce(): string;

    /**
     * Get the plugin base name.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function getBasename(): string;

    /**
     * Get the plugin directory path.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function getPluginDir(): string;

    /**
     * Get the plugin URL.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function getPluginUrl(): string;

    /**
     * Get the plugin assets URL.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function getAssetsUrl(): string;
} 