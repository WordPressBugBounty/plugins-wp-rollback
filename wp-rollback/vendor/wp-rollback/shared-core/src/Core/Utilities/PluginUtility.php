<?php

declare(strict_types=1);

namespace WpRollback\SharedCore\Core\Utilities;

/**
 * Plugin utility class
 *
 * @package WpRollback\SharedCore\Core\Utilities
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
     * @param string $pluginFile Plugin file path
     * @return string Plugin directory name
     */
    public static function getPluginDirname(string $pluginFile): string 
    {
        $pluginDir = dirname($pluginFile);
        return basename($pluginDir);
    }

    /**
     * Check if a list of plugin files includes the plugin a rollback slug names
     *
     * The slug is the plugin's folder, or its file for a single-file plugin, so a
     * plugin file matches when it's the slug itself or sits in the slug's folder.
     *
     * @param array<int|string, mixed> $pluginFiles Plugin files, like the 'active_plugins' option
     * @param string                   $pluginSlug  The plugin's folder, or its file for a single-file plugin
     * @return bool Whether one of the files is the plugin
     */
    public static function listIncludesPlugin(array $pluginFiles, string $pluginSlug): bool
    {
        foreach ($pluginFiles as $pluginFile) {
            if (!is_string($pluginFile)) {
                continue;
            }

            if ($pluginFile === $pluginSlug || 0 === strpos($pluginFile, $pluginSlug . '/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the current user can perform rollbacks
     *
     * @param string|null $type Asset type ('plugin', 'theme', or null for either)
     * @return bool Whether the current user can perform rollbacks
     */
    public static function currentUserCanRollback(?string $type = null): bool 
    {
        if ('theme' === $type) {
            return current_user_can('update_themes');
        }

        if ('plugin' === $type) {
            return current_user_can('update_plugins');
        }

        return current_user_can('update_plugins') || current_user_can('update_themes');
    }
} 