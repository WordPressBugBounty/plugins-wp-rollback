<?php

declare(strict_types=1);

namespace WpRollback\SharedCore\Core\Assets;

use WpRollback\SharedCore\Core\BaseConstants;

/**
 * Handles script and style registration for WP Rollback.
 *
 * @since 1.0.0
 */
class AssetsManager {
    protected BaseConstants $constants;
    
    public function __construct(BaseConstants $constants) {
        $this->constants = $constants;
    }

    /**
     * Register and enqueue a script with its associated style.
     *
     * @since 1.0.0
     * @param string $handle Script handle
     * @param array|bool $localizeDataOrEnqueueStyle Array of data to localize or boolean to control CSS enqueuing
     * @param bool|null $enqueueStyle Whether to enqueue the associated style. Only used if $localizeDataOrEnqueueStyle is an array.
     * @param string|null $localizeVarName Custom variable name for localization. Defaults to 'wprData'.
     * @return void
     */
    public function enqueueScript(string $handle, $localizeDataOrEnqueueStyle = true, ?bool $enqueueStyle = true, ?string $localizeVarName = null): void {
        $screen = get_current_screen();
        if (!$screen || !$this->isAllowedPage($screen->id, $handle)) {
            return;
        }

        // Get asset file data
        $assetFile = $this->constants->getPluginDir() . "/build/$handle.asset.php";
        $assetData = file_exists($assetFile) ? require $assetFile : ['dependencies' => [], 'version' => $this->constants->getVersion()];

        // Enqueue script
        wp_enqueue_script(
            $this->constants->getSlug() . '-' . $handle,
            $this->constants->getPluginUrl() . "/build/$handle.js",
            $assetData['dependencies'],
            $assetData['version'],
            true
        );

        // Handle localization data if provided
        if (is_array($localizeDataOrEnqueueStyle)) {
            wp_localize_script(
                $this->constants->getSlug() . '-' . $handle,
                $localizeVarName ?? 'wprData',
                $localizeDataOrEnqueueStyle
            );
        }

        // Determine if we should enqueue style
        $shouldEnqueueStyle = is_array($localizeDataOrEnqueueStyle) ? $enqueueStyle : $localizeDataOrEnqueueStyle;

        // Enqueue style if requested and file exists
        if ($shouldEnqueueStyle && file_exists($this->constants->getPluginDir() . "/build/$handle.css")) {
            wp_enqueue_style(
                $this->constants->getSlug() . '-' . $handle,
                $this->constants->getPluginUrl() . "/build/$handle.css",
                ['wp-components'],
                $assetData['version']
            );
        }

        // Add translations
        wp_set_script_translations(
            $this->constants->getSlug() . '-' . $handle,
            $this->constants->getTextDomain(),
            $this->constants->getPluginDir() . '/languages'
        );
    }

    /**
     * Check if we're on an allowed admin page for a specific script.
     *
     * @since 1.0.0
     * @param string $screenId Current screen ID
     * @param string $handle Script handle
     * @return bool
     */
    protected function isAllowedPage(string $screenId, string $handle): bool {
        $scriptRules = [
            'tools' => [
                'tools_page_wp-rollback',
                'tools_page_wp-rollback-pro',
                'settings_page_wp-rollback-network',
                'settings_page_wp-rollback-pro-network',
            ],
            'themesAdmin' => [
                'themes'
            ],
        ];

        // If no specific rules for this script, deny by default
        if (!isset($scriptRules[$handle])) {
            return false;
        }

        return in_array($screenId, apply_filters('wpr_script_allowed_pages_' . $handle, $scriptRules[$handle]));
    }
} 