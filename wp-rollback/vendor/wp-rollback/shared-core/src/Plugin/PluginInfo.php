<?php

declare(strict_types=1);

namespace WpRollback\SharedCore\Plugin;

/**
 * Class PluginInfo
 * 
 * Handles fetching and processing plugin information from WordPress.org
 */
class PluginInfo {
    /**
     * The plugin slug
     *
     * @var string
     */
    private string $slug;

    /**
     * Constructor
     *
     * @param string $slug The plugin slug
     */
    public function __construct(string $slug) {
        $this->slug = $slug;
    }

    /**
     * Get available versions for a plugin
     *
     * @return array<string> List of available versions
     */
    public function getAvailableVersions(): array {
        $url = sprintf('https://api.wordpress.org/plugins/info/1.0/%s.json', $this->slug);
        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!$data || !isset($data['versions'])) {
            return [];
        }

        return array_keys($data['versions']);
    }

    /**
     * Get the current installed version
     *
     * @return string|null The current version or null if not found
     */
    public function getCurrentVersion(): ?string {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugins = get_plugins();
        foreach ($plugins as $pluginFile => $pluginData) {
            if (strpos($pluginFile, $this->slug . '/') === 0) {
                return $pluginData['Version'];
            }
        }

        return null;
    }
} 