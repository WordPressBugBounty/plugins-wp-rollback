<?php

/**
 * AddPluginRollbackLinks
 *
 * This file is responsible for adding the rollback link to the plugin actions.
 * It provides unified handling for both free and pro plugins, with intelligent
 * detection of premium assets and Pro plugin status.
 *
 * @package WpRollback\SharedCore\Rollbacks\PluginRollback\Actions
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Rollbacks\PluginRollback\Actions;

use WpRollback\SharedCore\Rollbacks\Traits\PluginHelpers;

/**
 * Class AddPluginRollbackLinks
 *
 */
class AddPluginRollbackLinks
{
    use PluginHelpers;
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

        if (!$this->shouldAddRollbackLink($pluginFile, $pluginData)) {
            return $actions;
        }

        // Always use the regular rollback URL
        $rollbackURL = $this->buildRollbackUrl($pluginFile);
        $actions['rollback'] = $this->generateRollbackLink($rollbackURL);

        return apply_filters('wpr_plugin_action_link', $actions);
    }

    /**
     * Check if rollback link should be added
     *
     * @param string $pluginFile Plugin file path
     * @param array $pluginData Plugin data
     * @return bool Whether to add rollback link
     */
    protected function shouldAddRollbackLink(string $pluginFile, array $pluginData): bool
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
        return $this->isWpOrgPlugin($pluginFile) || $this->isPremiumAsset($pluginFile, $pluginData);
    }

    /**
     * Check whether a plugin is hosted on WordPress.org.
     *
     * Resolves via WP core's `update_plugins` transient, which records the
     * canonical wp.org slug for every plugin identified by the update API —
     * even when the local directory has been renamed (e.g. to disable the
     * plugin during a conflict). This is strictly more reliable than checking
     * `$pluginData['package']`, which is only populated when an update is
     * pending and thus misses up-to-date wp.org plugins entirely.
     *
     * @param string $pluginFile Plugin file path as keyed in get_plugins().
     * @return bool
     */
    protected function isWpOrgPlugin(string $pluginFile): bool
    {
        return $this->resolveWpOrgSlug($pluginFile) !== null;
    }

    /**
     * Check if plugin is a premium asset (not from wp.org)
     *
     * @param string $pluginFile Plugin file path
     * @param array $pluginData Plugin data
     * @return bool Whether this is a premium asset
     */
    protected function isPremiumAsset(string $pluginFile, array $pluginData): bool
    {
        // If wp.org recognises it, it's not premium.
        if ($this->isWpOrgPlugin($pluginFile)) {
            return false;
        }

        // If it has version data but isn't on wp.org, it's likely premium.
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
     * Build the rollback URL
     *
     * @param string $pluginFile Plugin file path
     * @return string Rollback URL
     */
    protected function buildRollbackUrl(string $pluginFile): string
    {
        $baseUrl = $this->getBaseAdminUrl();
        // Prefer the canonical wp.org slug so renamed directories still
        // resolve to the correct WordPress.org listing; fall back to the
        // directory name for premium / unrecognised plugins.
        $pluginSlug = $this->resolveWpOrgSlug($pluginFile) ?? dirname($pluginFile);

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
        $page = is_network_admin() ? 'settings.php' : 'tools.php';
        return $this->getContextualAdminUrl($page);
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
                '<a href="%1$s" class="wpr-plugin-rollback-link">%2$s</a>',
                esc_url($rollbackURL),
                esc_html__('Rollback', 'wp-rollback')
            )
        );
    }
} 