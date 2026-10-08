<?php

/**
 * This class is used to manage plugin activation, deactivation, redirection, and upgrade events.
 *
 * @package WpRollback\SharedCore\PluginSetup
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\PluginSetup;

use WpRollback\SharedCore\Core\BaseConstants;
use WpRollback\SharedCore\Core\SharedCore;

/**
 * Class PluginManager
 */
class PluginManager
{
    /**
     * @var string Option name for directory hash
     */
    public const HASH_OPTION_NAME = 'wp_rollback_dir_hash';

    /**
     * Ensure persistent site directory hash exists in wp_options.
     *
     * @return string The directory hash
     */
    public static function ensureDirectoryHash(): string
    {
        $hash = get_option(self::HASH_OPTION_NAME);
        if (empty($hash) || !is_string($hash)) {
            // Reuse an existing backup folder before making a new one. Otherwise
            // losing the option (a database restore, or an uninstall that cleared
            // it) would hide every archive in the folder already on disk.
            $hash = self::findExistingDirectoryHash() ?? wp_generate_password(32, false, false);
            // Autoloaded: BackupService reads it on every request, so leaving it
            // out would cost a database query on every page load.
            update_option(self::HASH_OPTION_NAME, $hash, true);
        }

        return $hash;
    }

    /**
     * Find the hash of a backup folder already in uploads (uploads/wp-rollback-{hash}).
     *
     * @return string|null The newest matching folder's hash, or null if there isn't one
     */
    private static function findExistingDirectoryHash(): ?string
    {
        $uploadDir = wp_upload_dir(null, false);
        if (empty($uploadDir['basedir'])) {
            return null;
        }

        $dirs = glob(trailingslashit($uploadDir['basedir']) . 'wp-rollback-*', GLOB_ONLYDIR);
        if (empty($dirs)) {
            return null;
        }

        // If there's more than one, prefer the most recently used folder.
        usort($dirs, static function (string $a, string $b): int {
            return (int) filemtime($b) <=> (int) filemtime($a);
        });

        foreach ($dirs as $dir) {
            if (preg_match('/^wp-rollback-([A-Za-z0-9]{32})$/', basename($dir), $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Checks for fresh install or version upgrade, updates version options, and fires corresponding action hooks.
     *
     * @return void
     */
    public static function handleVersionUpdates(): void
    {
        $constants                 = SharedCore::container()->make(BaseConstants::class);
        $optionPrefix              = $constants->getSlug();
        $previousVersionOptionName = $optionPrefix . '_previous_version';
        $currentVersionOptionName  = $optionPrefix . '_current_version';

        // The current version is read on every request to spot upgrades, so it's
        // autoloaded. The previous version is only read during an upgrade.
        $storedVersion  = get_option($currentVersionOptionName, false);
        $currentVersion = $constants->getVersion();

        if (false === $storedVersion || '' === $storedVersion) {
            // Fresh installation
            update_option($previousVersionOptionName, '', false);
            update_option($currentVersionOptionName, $currentVersion, true);

            /**
             * Fires when the plugin is freshly installed.
             *
             * @param string $currentVersion The newly installed version.
             * @param string $slug           The plugin slug.
             */
            do_action('wp_rollback_installed', $currentVersion, $constants->getSlug());
        } elseif ($storedVersion !== $currentVersion) {
            // Upgrade or version change from a previous version
            update_option($previousVersionOptionName, $storedVersion, false);
            update_option($currentVersionOptionName, $currentVersion, true);

            if (version_compare((string) $storedVersion, $currentVersion, '<')) {
                /**
                 * Fires when the plugin is upgraded from one version to another.
                 *
                 * @param string $currentVersion The new version.
                 * @param string $storedVersion  The previous version.
                 * @param string $slug           The plugin slug.
                 */
                do_action('wp_rollback_upgraded', $currentVersion, (string) $storedVersion, $constants->getSlug());
            }

            /**
             * Fires when the plugin version changes.
             *
             * @param string $currentVersion The new version.
             * @param string $storedVersion  The previous version.
             * @param string $slug           The plugin slug.
             */
            do_action('wp_rollback_version_changed', $currentVersion, (string) $storedVersion, $constants->getSlug());
        }
    }

    /**
     * This is used to manage the plugin activation.
     *
     * The activating plugin passes its own constants: Free and Pro share one
     * container, and during activation the other plugin may already have
     * resolved BaseConstants to itself (e.g. Free is still loaded while Pro is
     * being activated), so the container can't be trusted to say who's active.
     *
     * Version tracking (handleVersionUpdates) deliberately does not run here.
     * The activation request only loads the plugin file, not its service
     * providers, so nothing is listening for wp_rollback_installed/_upgraded;
     * recording the version now would make the next request see "no change"
     * and skip fresh-install migrations. The init path handles it on the next
     * request, with the migration listeners attached.
     *
     * @param BaseConstants $constants The activating plugin's constants.
     */
    public static function activate(BaseConstants $constants): void
    {
        self::ensureDirectoryHash();
    }

    /**
     * This is used to manage plugin deactivation.
     *
     * @param BaseConstants $constants The deactivating plugin's constants.
     */
    public static function deactivate(BaseConstants $constants): void
    {
    }
}
