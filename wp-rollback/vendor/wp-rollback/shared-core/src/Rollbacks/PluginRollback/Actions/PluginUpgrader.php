<?php

/**
 * WP Rollback Plugin Upgrader
 *
 * Class that extends the WP Core Plugin_Upgrader found in core to do rollbacks.
 *
 * @package WpRollback\SharedCore\Rollbacks\PluginRollback\Actions
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Rollbacks\PluginRollback\Actions;

use Plugin_Upgrader;
use WP_Error;

/**
 * Extends WordPress Plugin Upgrader to provide rollback functionality
 * 
 * @since 1.0.0
 */
class PluginUpgrader extends Plugin_Upgrader
{
    /**
     * Plugin rollback.
     *
     * @param string $plugin Plugin file path
     * @param array $args Optional arguments
     *
     * @return array|bool|WP_Error
     */
    public function rollback($plugin, array $args = [])
    {
        $defaults = [
            'clear_update_cache' => true,
        ];

        $parsedArgs = wp_parse_args($args, $defaults);

        $this->init();
        $this->upgrade_strings();

        $pluginSlug = $this->skin->plugin; // @phpstan-ignore-line
        $pluginVersion = $this->skin->options['version'];
        $downloadEndpoint = 'https://downloads.wordpress.org/plugin/';
        $url = $downloadEndpoint . $pluginSlug . '.' . $pluginVersion . '.zip';

        $this->skin->plugin_action = is_plugin_active($plugin); // @phpstan-ignore-line

        add_filter('upgrader_pre_install', [$this, 'deactivate_plugin_before_upgrade'], 10, 2);
        add_filter('upgrader_pre_install', [$this, 'active_before'], 10, 2);
        add_filter('upgrader_clear_destination', [$this, 'delete_old_plugin'], 10, 4);
        add_filter('upgrader_post_install', [$this, 'active_after'], 10, 2);

        $this->run([
            'package' => $url,
            'destination' => WP_PLUGIN_DIR,
            'clear_destination' => true,
            'clear_working' => true,
            'hook_extra' => [
                'plugin' => $plugin,
                'type' => 'plugin',
                'action' => 'update',
                'bulk' => 'false',
            ],
        ]);

        remove_action('upgrader_process_complete', 'wp_clean_plugins_cache', 9);
        remove_filter('upgrader_pre_install', [$this, 'deactivate_plugin_before_upgrade']);
        remove_filter('upgrader_pre_install', [$this, 'active_before']);
        remove_filter('upgrader_clear_destination', [$this, 'delete_old_plugin']);
        remove_filter('upgrader_post_install', [$this, 'active_after']);

        if (! $this->result || is_wp_error($this->result)) {
            return $this->result;
        }

        // Force refresh of plugin update information.
        wp_clean_plugins_cache($parsedArgs['clear_update_cache']);

        return true;
    }
} 