<?php

/**
 * Plugin Helper Traits
 * 
 * @package WpRollback\SharedCore\Rollbacks\Traits
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Rollbacks\Traits;

use WpRollback\SharedCore\Core\BaseConstants;
use WpRollback\SharedCore\Core\Exceptions\BindingResolutionException;
use WpRollback\SharedCore\Core\SharedCore;

trait PluginHelpers
{
    /**
     * Common plugin paths for WP Rollback
     * 
     * @var array
     */
    protected static $pluginPaths = [
        'free' => [
            'wp-rollback/wp-rollback.php',
            'wp-rollback-monorepo/packages/free-plugin/wp-rollback.php',
        ],
        'pro' => [
            'wp-rollback-pro/wp-rollback-pro.php',
            'wp-rollback-monorepo/packages/pro-plugin/wp-rollback-pro.php',
        ],
    ];

    /**
     * Ensure WordPress plugin functions are loaded
     *
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

    /**
     * Find an installed plugin from the slug a rollback request names it by.
     *
     * That's the plugin's folder, or its file name for a single-file plugin. A
     * WordPress.org plugin whose folder was renamed also answers to its
     * WordPress.org slug. getPluginFileBySlug() only matches the folder, which
     * is enough for the rollback steps but not for a request's slug.
     *
     * @param string $slug The slug from the rollback request
     * @return string The plugin file as keyed in get_plugins(), or '' if no installed plugin matches
     */
    protected function findPluginFileForRollbackSlug(string $slug): string
    {
        $this->loadPluginFunctions();

        $renamedMatch = '';

        foreach (array_keys(get_plugins()) as $pluginFile) {
            $pluginFile = (string) $pluginFile;
            $folder     = strstr($pluginFile, '/', true);

            if (($folder ?: $pluginFile) === $slug) {
                return $pluginFile;
            }

            if ('' === $renamedMatch && $this->resolveWpOrgSlug($pluginFile) === $slug) {
                $renamedMatch = $pluginFile;
            }
        }

        return $renamedMatch;
    }

    /**
     * Check if the plugin is network activated
     * 
     * This method is primarily used to determine if operations should be skipped
     * on individual subsites when the plugin is network activated. It returns false
     * in network admin context to ensure network-level operations proceed normally.
     *
     * @return bool True if network activated (and not in network admin), false otherwise
     */
    protected function isNetworkActivated(): bool
    {
        // Only check in multisite environments
        if (!is_multisite()) {
            return false;
        }
        
        // In network admin context, operations should proceed normally
        // regardless of network activation status
        if (is_network_admin()) {
            return false;
        }
        
        // Load plugin functions if not already loaded
        if (!function_exists('is_plugin_active_for_network')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        try {
            // Try to get constants from container to determine the actual plugin file
            $constants = SharedCore::container()->make(BaseConstants::class);
            if ($constants && method_exists($constants, 'getBasename')) {
                $pluginBasename = $constants->getBasename();
                if (is_plugin_active_for_network($pluginBasename)) {
                    return true;
                }
            }
        } catch (BindingResolutionException $e) {
            // Fall through to check common plugin paths
        }
        
        // Fallback: Check common plugin paths
        $allPlugins = array_merge(self::$pluginPaths['free'], self::$pluginPaths['pro']);
        
        foreach ($allPlugins as $plugin) {
            if (is_plugin_active_for_network($plugin)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if Pro plugin is active
     *
     * @return bool Whether Pro plugin is active
     */
    protected function isProPluginActive(): bool
    {
        // Load plugin functions if not already loaded
        if (!function_exists('is_plugin_active') || !function_exists('is_plugin_active_for_network')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        // Check if instance has isProVersion property set
        if (property_exists($this, 'isProVersion') && $this->isProVersion) {
            return true;
        }
        
        // Check common pro plugin paths
        foreach (self::$pluginPaths['pro'] as $plugin) {
            if (is_plugin_active($plugin) || is_plugin_active_for_network($plugin)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if Free plugin is active
     *
     * @return bool Whether Free plugin is active
     */
    protected function isFreePluginActive(): bool
    {
        // Load plugin functions if not already loaded
        if (!function_exists('is_plugin_active') || !function_exists('is_plugin_active_for_network')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        // Check common free plugin paths
        foreach (self::$pluginPaths['free'] as $plugin) {
            if (is_plugin_active($plugin) || is_plugin_active_for_network($plugin)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get the appropriate admin URL based on context
     *
     * @param string $page The admin page (e.g., 'tools.php', 'settings.php')
     * @return string The admin URL
     */
    protected function getContextualAdminUrl(string $page): string
    {
        return is_network_admin() ? network_admin_url($page) : admin_url($page);
    }

    /**
     * Resolve the canonical WordPress.org slug for an installed plugin.
     *
     * Reads the `update_plugins` site transient that WP core maintains via
     * its twice-daily update check. WordPress.org's update API identifies
     * plugins server-side by header fingerprint (Name, TextDomain, PluginURI,
     * Author, UpdateURI) rather than by directory name, so the canonical
     * slug is available even when a user has renamed the local directory
     * (e.g. wp-rollback/ -> wp-rollback-disabled/ to disable during a conflict).
     *
     * Returns null when the plugin is not present in either bucket of the
     * transient — typically a premium / non-wp.org plugin, or a site whose
     * update check has never run.
     *
     * @param string $pluginFile Plugin file path as keyed in get_plugins()
     *                           (e.g. 'wp-rollback-disabled/wp-rollback.php').
     * @return string|null Canonical wp.org slug or null when not a wp.org plugin.
     */
    protected function resolveWpOrgSlug(string $pluginFile): ?string
    {
        $updates = get_site_transient('update_plugins');

        if (!is_object($updates)) {
            return null;
        }

        foreach (['response', 'no_update'] as $bucket) {
            if (
                isset($updates->{$bucket}[$pluginFile]->slug)
                && is_string($updates->{$bucket}[$pluginFile]->slug)
                && $updates->{$bucket}[$pluginFile]->slug !== ''
            ) {
                return $updates->{$bucket}[$pluginFile]->slug;
            }
        }

        return null;
    }
}
