<?php

/**
 * Plugin Helper Traits
 * 
 * @package WpRollback\SharedCore\Rollbacks\Traits
 * @since 1.0.0
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
     * @since 1.0.0
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

    /**
     * Check if the plugin is network activated
     * 
     * This method is primarily used to determine if operations should be skipped
     * on individual subsites when the plugin is network activated. It returns false
     * in network admin context to ensure network-level operations proceed normally.
     *
     * @since 1.0.0
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
     * @since 1.0.0
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
     * @since 1.0.0
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
     * @since 1.0.0
     * @param string $page The admin page (e.g., 'tools.php', 'settings.php')
     * @return string The admin URL
     */
    protected function getContextualAdminUrl(string $page): string
    {
        return is_network_admin() ? network_admin_url($page) : admin_url($page);
    }
} 