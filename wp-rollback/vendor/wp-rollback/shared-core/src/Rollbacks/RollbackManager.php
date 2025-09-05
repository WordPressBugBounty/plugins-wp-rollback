<?php

/**
 * @package WpRollback\SharedCore\Rollbacks
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Rollbacks;

/**
 * Base class for rollback managers.
 * 
 * Provides shared functionality for managing rollbacks in both free and pro plugins.
 *
 * @since 1.0.0
 */
abstract class RollbackManager
{
    /**
     * Asset types supported for rollback
     */
    public const PLUGIN_ASSET = 'plugin';
    public const THEME_ASSET = 'theme';

    /**
     * Get the available versions for an asset.
     * 
     * @since 1.0.0
     * 
     * @param string $slug The asset slug
     * @param string $type The asset type (plugin or theme)
     * @return array<string, mixed> Array of available versions
     */
    abstract public function getVersions(string $slug, string $type): array;

    /**
     * Perform a rollback for an asset.
     * 
     * @since 1.0.0
     * 
     * @param string $slug The asset slug
     * @param string $version The version to rollback to
     * @param string $type The asset type (plugin or theme)
     * @return bool True if rollback was successful, false otherwise
     */
    abstract public function performRollback(string $slug, string $version, string $type): bool;

    /**
     * Validate that a rollback can be performed.
     * 
     * @since 1.0.0
     * 
     * @param string $slug The asset slug
     * @param string $version The version to rollback to
     * @param string $type The asset type (plugin or theme)
     * @return bool|string True if valid, error message if not
     */
    public function validateRollback(string $slug, string $version, string $type)
    {
        if (empty($slug)) {
            return 'Invalid slug provided.';
        }

        if (empty($version)) {
            return 'Invalid version provided.';
        }

        if (!in_array($type, [self::PLUGIN_ASSET, self::THEME_ASSET], true)) {
            return 'Invalid asset type. Must be either plugin or theme.';
        }

        return true;
    }

    /**
     * Format version information for display.
     * 
     * @since 1.0.0
     * 
     * @param string $version The version number
     * @param array<string, mixed> $versionInfo Additional version info
     * @return array<string, mixed> Formatted version info
     */
    protected function formatVersionInfo(string $version, array $versionInfo = []): array
    {
        return array_merge(
            [
                'version' => $version,
                'timestamp' => null,
                'url' => '',
                'package' => '',
            ],
            $versionInfo
        );
    }

    /**
     * Get rollback URL for UI display.
     * 
     * @since 1.0.0
     * 
     * @param string $slug The asset slug
     * @param string $version The version to rollback to
     * @param string $type The asset type (plugin or theme)
     * @return string The rollback URL
     */
    abstract public function getRollbackUrl(string $slug, string $version, string $type): string;
} 