<?php

/**
 * This class is used to manage the plugin activate, deactivation, and redirection on plugin activation.
 *
 * @package WpRollback\SharedCore\PluginSetup
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\PluginSetup;

use WpRollback\SharedCore\Core\BaseConstants;
use WpRollback\SharedCore\Core\SharedCore;

/**
* Class PluginManager
 *
 * @since 1.0.0
 */
class PluginManager
{
    /**
     * This is used to manage the plugin activation.
     * @since 1.0.0
     */
    public static function activate(): void
    {
        $constants = SharedCore::container()->make(BaseConstants::class);
        $optionPrefix = $constants->getSlug();
        $previousVersionOptionName = $optionPrefix . '_previous_version';
        $currentVersionOptionName = $optionPrefix . '_current_version';
        $currentVersion = get_option($currentVersionOptionName, false);

        update_option($previousVersionOptionName, $currentVersion ?: '', false);
        update_option($currentVersionOptionName, $constants->getVersion(), false);

        // This option is used to trigger redirect to the getting-started page when the plugin is activated.
        update_option($optionPrefix . '_just_activated', $constants->getVersion(), false);

        // This option is used to decide whether flush rewrites permalinks.
        update_option($optionPrefix . '_plugin_permalinks_flushed', 0);
    }

    /**
     * This is used to manage the plugin deactivation.
     * @since 1.0.0
     */
    public static function deactivate(): void
    {
        $constants = SharedCore::container()->make(BaseConstants::class);
        $optionPrefix = $constants->getSlug();
        
        // This option is used to decide whether flush rewrites permalinks.
        update_option($optionPrefix . '_plugin_permalinks_flushed', 0);
    }
} 