<?php

/**
 * Checks whether other sites on a multisite network use a plugin or theme.
 *
 * @package WpRollback\SharedCore\Rollbacks\Services
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Rollbacks\Services;

use WpRollback\SharedCore\Core\Utilities\PluginUtility;

/**
 * Service for checking whether other sites on a network that show WP Rollback's
 * maintenance page use a plugin or theme
 *
 * Every site on a network loads its plugins and themes from the same folders, so
 * replacing one during a rollback affects every site that uses it, not just the
 * site the rollback runs from.
 *
 * WP Rollback shows its maintenance page from its own hook, so a site only shows it
 * when WP Rollback is network-activated or active on that site. Other sites don't
 * count: turning maintenance mode on for them would only take down the sites that
 * do show the page, while the site using the asset stays up.
 *
 * Each site's options are read straight from its options table. get_blog_option()
 * would switch to every site and load all of its autoloaded options just to read
 * three.
 *
 * Archived, spam and deactivated sites are checked too, because WordPress loads a
 * site's plugins and theme before it checks the site's status.
 */
class NetworkAssetUsageService
{
    /**
     * Most other sites on a network to check before assuming one of them uses the asset
     *
     * @var int
     */
    private const MAX_SITES_TO_CHECK = 100;

    /**
     * The WP Rollback plugin's file, like 'wp-rollback/wp-rollback.php'
     *
     * @var string
     */
    private string $wpRollbackPluginFile;

    /**
     * Constructor
     *
     * @param string $wpRollbackPluginFile The WP Rollback plugin's file, like 'wp-rollback/wp-rollback.php'
     */
    public function __construct(string $wpRollbackPluginFile)
    {
        $this->wpRollbackPluginFile = $wpRollbackPluginFile;
    }

    /**
     * Check if any other site that shows WP Rollback's maintenance page has the plugin active
     *
     * This reads each site's own 'active_plugins' option. Network-activated plugins
     * are in the network's 'active_sitewide_plugins' option instead.
     *
     * @param string $pluginSlug The plugin's folder, or its file for a single-file plugin
     * @return bool True if another site has the plugin active, or there are too many sites to check
     */
    public function isPluginActiveOnOtherSites(string $pluginSlug): bool
    {
        return $this->isUsedOnOtherSites(
            static fn (array $siteOptions): bool => PluginUtility::listIncludesPlugin($siteOptions['active_plugins'], $pluginSlug)
        );
    }

    /**
     * Check if any other site that shows WP Rollback's maintenance page uses the theme,
     * either as its active theme or as the parent of its active theme
     *
     * This isn't limited to network-enabled themes: a site can use a theme enabled
     * for just that site, and a parent theme never needs to be enabled at all.
     *
     * @param string $stylesheet The theme's folder name
     * @return bool True if another site uses the theme, or there are too many sites to check
     */
    public function isThemeUsedOnOtherSites(string $stylesheet): bool
    {
        return $this->isUsedOnOtherSites(
            static fn (array $siteOptions): bool => in_array($stylesheet, [$siteOptions['stylesheet'], $siteOptions['template']], true)
        );
    }

    /**
     * Check the other sites that show WP Rollback's maintenance page one at a time
     * until one uses the asset
     *
     * @param callable(array{active_plugins: array<int|string, mixed>, stylesheet: ?string, template: ?string}): bool $isUsedOnSite Checks one site, given its options
     * @return bool True if another site uses the asset, or there are too many sites to check
     */
    private function isUsedOnOtherSites(callable $isUsedOnSite): bool
    {
        global $wpdb;

        $siteIds = get_sites([
            'fields' => 'ids',
            'number' => self::MAX_SITES_TO_CHECK + 1,
            'site__not_in' => [get_current_blog_id()],
        ]);

        // Too many sites to check during a rollback, so assume one of them uses the asset
        if (count($siteIds) > self::MAX_SITES_TO_CHECK) {
            return true;
        }

        $networkPlugins = (array) get_site_option('active_sitewide_plugins', []);
        $isNetworkActivated = array_key_exists($this->wpRollbackPluginFile, $networkPlugins);

        foreach ($siteIds as $siteId) {
            $siteOptions = $this->getSiteOptions($wpdb->get_blog_prefix($siteId) . 'options');

            if (!$isNetworkActivated && !in_array($this->wpRollbackPluginFile, $siteOptions['active_plugins'], true)) {
                continue;
            }

            if ($isUsedOnSite($siteOptions)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Read a site's active plugins and theme straight from its options table
     *
     * @param string $optionsTable The site's options table
     * @return array{active_plugins: array<int|string, mixed>, stylesheet: ?string, template: ?string}
     */
    private function getSiteOptions(string $optionsTable): array
    {
        global $wpdb;

        $siteOptions = [
            'active_plugins' => [],
            'stylesheet' => null,
            'template' => null,
        ];

        $rows = $wpdb->get_results(
            "SELECT option_name, option_value FROM {$optionsTable} WHERE option_name IN ('active_plugins', 'stylesheet', 'template')"
        );

        foreach ((array) $rows as $row) {
            if ('active_plugins' === $row->option_name) {
                // 'active_plugins' is a serialized list, so plugins are matched here rather than in SQL
                $activePlugins = maybe_unserialize($row->option_value);
                $siteOptions['active_plugins'] = is_array($activePlugins) ? $activePlugins : [];
                continue;
            }

            $siteOptions[$row->option_name] = $row->option_value;
        }

        return $siteOptions;
    }
}
