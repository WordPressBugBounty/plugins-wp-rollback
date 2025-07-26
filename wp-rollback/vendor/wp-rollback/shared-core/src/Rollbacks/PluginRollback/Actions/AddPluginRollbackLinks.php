<?php

/**
 * AddPluginRollbackLinks
 *
 * This file is responsible for adding the rollback link to the plugin actions.
 * It provides unified handling for both free and pro plugins, with intelligent
 * detection of premium assets and Pro plugin status.
 *
 * @package WpRollback\SharedCore\Rollbacks\PluginRollback\Actions
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Rollbacks\PluginRollback\Actions;

/**
 * Class AddPluginRollbackLinks
 *
 * @since 1.0.0
 */
class AddPluginRollbackLinks
{
    /**
     * The plugin slug used in URLs
     * 
     * @var string
     */
    protected string $pluginSlug;

    /**
     * Whether this is the Pro version
     * 
     * @var bool
     */
    protected bool $isProVersion;

    /**
     * Constructor
     * 
     * @param string $pluginSlug The plugin slug used in URLs
     * @param bool $isProVersion Whether this is the Pro version
     */
    public function __construct(string $pluginSlug, bool $isProVersion = false)
    {
        $this->pluginSlug = $pluginSlug;
        $this->isProVersion = $isProVersion;
    }

    /**
     * Add rollback link to plugin actions
     *
     * @param array $actions Existing plugin actions
     * @param string $pluginFile Plugin file path
     * @param array $pluginData Plugin data
     * @param string $context Plugin context
     * @return array Modified plugin actions
     */
    public function __invoke($actions, $pluginFile, $pluginData, $context): array
    {
        // Handle case where $pluginData is null (e.g., from Jetpack sync)
        if (!is_array($pluginData)) {
            return $actions;
        }

        if (!$this->shouldAddRollbackLink($pluginData)) {
            return $actions;
        }

        $isProPluginActive = $this->isProPluginActive();
        $isPremiumAsset = $this->isPremiumAsset($pluginData);

        // Always use the regular rollback URL
        $rollbackURL = $this->buildRollbackUrl($pluginFile);
        
        // Style premium asset links differently when Pro plugin isn't active
        if (!$isProPluginActive && $isPremiumAsset) {
            $actions['rollback'] = $this->generatePremiumRollbackLink($rollbackURL);
        } else {
            $actions['rollback'] = $this->generateRollbackLink($rollbackURL);
        }

        return apply_filters('wpr_plugin_action_link', $actions);
    }

    /**
     * Check if rollback link should be added
     *
     * @param array $pluginData Plugin data
     * @return bool Whether to add rollback link
     */
    protected function shouldAddRollbackLink(array $pluginData): bool
    {
        // Don't show on non-network admin for multisite
        if (is_multisite() && !is_network_admin()) {
            return false;
        }

        // Filter for other devs
        $pluginData = apply_filters('wpr_plugin_data', $pluginData);

        // Check if plugin has version data (required for all rollbacks)
        if (!$this->hasVersionData($pluginData)) {
            return false;
        }

        // For Pro plugin, show links for all plugins with version data
        if ($this->isProPluginActive()) {
            return true;
        }

        // For free plugin, show links for wp.org plugins OR premium plugins (with upsell)
        return $this->hasValidPackageData($pluginData) || $this->isPremiumAsset($pluginData);
    }

    /**
     * Check if plugin has valid package data (wp.org plugin)
     *
     * @param array $pluginData Plugin data
     * @return bool Whether package data is valid
     */
    protected function hasValidPackageData(array $pluginData): bool
    {
        return isset($pluginData['package']) &&
               is_string($pluginData['package']) &&
               strpos($pluginData['package'], 'downloads.wordpress.org') !== false;
    }

    /**
     * Check if plugin is a premium asset (not from wp.org)
     *
     * @param array $pluginData Plugin data
     * @return bool Whether this is a premium asset
     */
    protected function isPremiumAsset(array $pluginData): bool
    {
        // If it has wp.org package data, it's not premium
        if ($this->hasValidPackageData($pluginData)) {
            return false;
        }

        // If it has version data but no wp.org package, it's likely premium
        return $this->hasVersionData($pluginData);
    }

    /**
     * Check if plugin has version data
     *
     * @param array $pluginData Plugin data
     * @return bool Whether version data exists
     */
    protected function hasVersionData(array $pluginData): bool
    {
        return isset($pluginData['Version']);
    }

    /**
     * Check if Pro plugin is active
     *
     * @return bool Whether Pro plugin is active
     */
    protected function isProPluginActive(): bool
    {
        // Check if Pro plugin is active by looking for the Pro plugin file
        return is_plugin_active('wp-rollback-pro/wp-rollback-pro.php') || $this->isProVersion;
    }

    /**
     * Build the rollback URL
     *
     * @param string $pluginFile Plugin file path
     * @return string Rollback URL
     */
    protected function buildRollbackUrl(string $pluginFile): string
    {
        $baseUrl = $this->getBaseAdminUrl();
        $pluginSlug = dirname($pluginFile);

        return add_query_arg(
            ['page' => $this->pluginSlug],
            $baseUrl
        ) . '#/rollback/plugin/' . $pluginSlug;
    }

    /**
     * Get base admin URL based on context
     *
     * @return string Base admin URL
     */
    protected function getBaseAdminUrl(): string
    {
        return is_network_admin()
            ? network_admin_url('tools.php')
            : admin_url('tools.php');
    }

    /**
     * Generate HTML for rollback link
     *
     * @param string $rollbackURL Rollback URL
     * @return string HTML link
     */
    protected function generateRollbackLink(string $rollbackURL): string
    {
        return apply_filters(
            'wpr_plugin_markup',
            sprintf(
                '<a href="%1$s">%2$s</a>',
                esc_url($rollbackURL),
                esc_html__('Rollback', 'wp-rollback')
            )
        );
    }

    /**
     * Generate HTML for premium rollback link (when Pro plugin is not active and asset is premium)
     *
     * @param string $rollbackURL Rollback URL
     * @return string HTML link
     */
    protected function generatePremiumRollbackLink(string $rollbackURL): string
    {
        return apply_filters(
            'wpr_plugin_markup',
            sprintf(
                '<a href="%1$s" style="color: #d63638;">%2$s</a>',
                esc_url($rollbackURL),
                esc_html__('Rollback', 'wp-rollback')
            )
        );
    }
} 