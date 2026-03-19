<?php

/**
 * AdminPageHeaderLinks
 *
 * Adds page-level "Rollback Plugins" / "Rollback Themes" action links to the
 * native WordPress plugins.php and themes.php admin pages, displayed inline
 * with the existing page title action buttons (e.g. "Add New Plugin").
 *
 * @package WpRollback\SharedCore\Rollbacks\Admin
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Rollbacks\Admin;

use WpRollback\SharedCore\Rollbacks\Traits\PluginHelpers;

/**
 * Class AdminPageHeaderLinks
 *
 */
class AdminPageHeaderLinks
{
    use PluginHelpers;

    /**
     * The plugin slug used to build admin page URLs.
     *
     * @var string
     */
    private string $pluginSlug;

    /**
     * @param string $pluginSlug Plugin page slug (e.g. 'wp-rollback' or 'wp-rollback-pro')
     */
    public function __construct(string $pluginSlug)
    {
        $this->pluginSlug = $pluginSlug;
    }

    /**
     * Register hooks for both plugins.php and themes.php admin pages.
     *
     * @return void
     */
    public function initialize(): void
    {
        add_action('admin_head-plugins.php', [$this, 'injectPluginsPageLink']);
        add_action('admin_head-themes.php', [$this, 'injectThemesPageLink']);
    }

    /**
     * Inject the "Rollback Plugins" link on the plugins.php page.
     * Skipped on multisite individual sites (plugin management is network-only there).
     *
     * @return void
     */
    public function injectPluginsPageLink(): void
    {
        if (is_multisite() && !is_network_admin()) {
            return;
        }

        $adminPage  = is_network_admin() ? 'settings.php' : 'tools.php';
        $url        = $this->getContextualAdminUrl($adminPage);
        $url        = add_query_arg(['page' => $this->pluginSlug], $url) . '#/plugin-list';
        $label      = __('Rollback Plugins', 'wp-rollback');

        $this->outputInjectionScript(esc_url($url), esc_html($label));
    }

    /**
     * Inject the "Rollback Themes" link on the themes.php page.
     * Skipped on network admin (theme action links handle that context).
     *
     * @return void
     */
    public function injectThemesPageLink(): void
    {
        if (is_network_admin()) {
            return;
        }

        $url   = add_query_arg(['page' => $this->pluginSlug], admin_url('tools.php')) . '#/theme-list';
        $label = __('Rollback Themes', 'wp-rollback');

        $this->outputInjectionScript(esc_url($url), esc_html($label));
    }

    /**
     * Output an inline script that inserts a .page-title-action anchor into
     * the admin page header, just before the <hr class="wp-header-end"> separator.
     *
     * @param string $url   The fully-escaped destination URL.
     * @param string $label The fully-escaped link label.
     * @return void
     */
    private function outputInjectionScript(string $url, string $label): void
    {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var headerEnd = document.querySelector('hr.wp-header-end');
            if (!headerEnd) return;

            var link = document.createElement('a');
            link.href = <?php echo wp_json_encode($url); ?>;
            link.className = 'page-title-action';
            link.textContent = <?php echo wp_json_encode($label); ?>;

            headerEnd.insertAdjacentElement('beforebegin', link);
        });
        </script>
        <?php
    }
}
