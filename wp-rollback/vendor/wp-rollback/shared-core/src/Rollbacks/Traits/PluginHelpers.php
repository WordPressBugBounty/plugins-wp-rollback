<?php

/**
 * Plugin Helper Traits
 * 
 * @package WpRollback\SharedCore\Rollbacks\Traits
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Rollbacks\Traits;

trait PluginHelpers
{
    /**
     * Ensure WordPress plugin functions are loaded
     *
     * @since 1.0.0
     * @return void
     */
    private function loadPluginFunctions(): void
    {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
    }

    /**
     * Get plugin file path by slug
     *
     * @since 1.0.0
     * @param string $pluginSlug The plugin slug
     * @return string The plugin file path
     */
    private function getPluginFileBySlug(string $pluginSlug): string
    {
        $this->loadPluginFunctions();
        $plugins = get_plugins();
        
        foreach (array_keys($plugins) as $path) {
            if (strpos((string) $path, $pluginSlug . '/') === 0) {
                return $path;
            }
        }

        return '';
    }
} 