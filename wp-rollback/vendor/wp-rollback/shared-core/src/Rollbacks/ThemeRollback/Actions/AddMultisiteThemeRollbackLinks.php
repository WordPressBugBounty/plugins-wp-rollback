<?php

/**
 * AddMultisiteThemeRollbackLinks
 *
 * This file is responsible for adding the rollback link to theme actions
 * in WordPress Multisite network admin themes table. This is specifically
 * for multisite installations - single site uses a different UI approach.
 *
 * @package WpRollback\SharedCore\Rollbacks\ThemeRollback\Actions
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Rollbacks\ThemeRollback\Actions;

use WpRollback\SharedCore\Rollbacks\Traits\PluginHelpers;

/**
 * Class AddMultisiteThemeRollbackLinks
 *
 * @since 1.0.0
 */
class AddMultisiteThemeRollbackLinks
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
     * Add rollback link to theme actions
     *
     * @param array $actions Existing theme actions
     * @param \WP_Theme $theme The theme object
     * @param string $context Context (Active, Inactive, Recently Activated, etc.)
     * @return array Modified theme actions
     */
    public function __invoke($actions, $theme, $context): array
    {
        if (!$this->shouldAddRollbackLink($theme)) {
            return $actions;
        }

        $stylesheet = $theme->get_stylesheet();
        $rollbackURL = $this->buildRollbackUrl($stylesheet);
        $actions['rollback'] = $this->generateRollbackLink($rollbackURL);

        return apply_filters('wpr_theme_action_link', $actions);
    }

    /**
     * Check if rollback link should be added
     *
     * @param \WP_Theme $theme The theme object
     * @return bool Whether to add rollback link
     */
    protected function shouldAddRollbackLink(\WP_Theme $theme): bool
    {
        // Only show in network admin for multisite
        if (!is_network_admin()) {
            return false;
        }

        // Don't show for child themes
        if ($theme->parent()) {
            return false;
        }

        // Check if theme has version
        if (!$theme->get('Version')) {
            return false;
        }

        // For Pro plugin, show links for all themes with version data
        if ($this->isProPluginActive()) {
            return true;
        }

        // For free plugin, always show links and let the API determine availability
        // This provides a consistent experience and avoids complex checks
        return true;
    }

    /**
     * Build the rollback URL
     *
     * @param string $stylesheet Theme stylesheet name
     * @return string Rollback URL
     */
    protected function buildRollbackUrl(string $stylesheet): string
    {
        $baseUrl = $this->getContextualAdminUrl('settings.php');
        
        return add_query_arg(
            ['page' => $this->pluginSlug],
            $baseUrl
        ) . '#/rollback/theme/' . $stylesheet;
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
            'wpr_theme_markup',
            sprintf(
                '<a href="%1$s">%2$s</a>',
                esc_url($rollbackURL),
                esc_html__('Rollback', 'wp-rollback')
            )
        );
    }
}
