<?php

declare(strict_types=1);

namespace WpRollback\SharedCore\Core\Utilities;

/**
 * Plugin utility class
 *
 * @package WpRollback\SharedCore\Core\Utilities
 * @since 1.0.0
 */
class PluginUtility
{
    /**
     * Check if a plugin version is valid for rollback
     *
     * @param string $version Version to check
     * @return bool Whether the version is valid
     */
    public static function isValidVersion(string $version): bool 
    {
        return (bool) preg_match('/^\d+\.\d+(\.\d+)?(-[a-zA-Z0-9.]+)?$/', $version);
    }

    /**
     * Get the plugin directory name from a plugin file
     *
     * @param string $plugin_file Plugin file path
     * @return string Plugin directory name
     */
    public static function getPluginDirname(string $plugin_file): string 
    {
        $plugin_dir = dirname($plugin_file);
        return basename($plugin_dir);
    }

    /**
     * Check if the current user can perform rollbacks
     *
     * @return bool Whether the current user can perform rollbacks
     */
    public static function currentUserCanRollback(): bool 
    {
        return current_user_can('update_plugins');
    }
} 