<?php

/**
 * Base Constants class to be extended by both Free and Pro plugins.
 *
 * @package WpRollback\SharedCore\Core
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Core;

use WpRollback\SharedCore\Core\ConstantsInterface;

/**
 * Base constants implementation with shared functionality.
 * 
 * @since 1.0.0
 */
class BaseConstants implements ConstantsInterface
{
    /**
     * @var string Plugin text domain
     */
    protected string $textDomain;
    
    /**
     * @var string Plugin version
     */
    protected string $version;
    
    /**
     * @var string Plugin slug
     */
    protected string $slug;
    
    /**
     * @var string Plugin nonce
     */
    protected string $nonce;
    
    /**
     * @var string Plugin URL
     */
    protected string $pluginUrl;

    /**
     * @var string Plugin directory path
     */
    protected string $pluginDir;

    /**
     * @var string Plugin relative path
     */
    protected string $pluginBasename;

    /**
     * @var string Plugin root file
     */
    protected string $pluginFile;

    /**
     * Constants constructor.
     *
     * @param string $textDomain  Plugin text domain
     * @param string $version     Plugin version
     * @param string $slug        Plugin slug
     * @param string $nonce       Plugin nonce
     * @param string $pluginFile  Full path to the plugin file
     */
    public function __construct(
        string $textDomain,
        string $version,
        string $slug,
        string $nonce,
        string $pluginFile
    ) {
        $this->textDomain = $textDomain;
        $this->version = $version;
        $this->slug = $slug;
        $this->nonce = $nonce;
        $this->pluginFile = $pluginFile;
        
        // Calculate plugin URL, directory, and basename from the plugin file
        $this->pluginUrl = untrailingslashit(plugins_url('', $pluginFile));
        $this->pluginDir = untrailingslashit(plugin_dir_path($pluginFile));
        $this->pluginBasename = plugin_basename($pluginFile);
    }

    /**
     * Find the plugin file path based on the calling class location.
     * This method can be used by plugin-specific Constants classes to determine their plugin file path.
     *
     * @param string $slug The plugin slug (e.g., 'wp-rollback-pro')
     * @param string $callingFile The __FILE__ constant from the calling Constants class
     * @return string Full path to the plugin file
     */
    public static function findPluginFile(string $slug, string $callingFile): string
    {
        // From the calling Constants.php file (src/Core/Constants.php), the plugin root is always 3 levels up
        // This works consistently for both development and deployed environments
        return dirname($callingFile, 3) . "/{$slug}.php";
    }

    /**
     * Get the plugin text domain.
     *
     * @return string
     */
    public function getTextDomain(): string
    {
        return $this->textDomain;
    }
    
    /**
     * Get the plugin version.
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }
    
    /**
     * Get the plugin slug.
     *
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }
    
    /**
     * Get the nonce name.
     *
     * @return string
     */
    public function getNonce(): string
    {
        return $this->nonce;
    }

    /**
     * Get the plugin base name.
     *
     * @return string
     */
    public function getBasename(): string
    {
        return $this->pluginBasename;
    }

    /**
     * Get the plugin directory path.
     *
     * @return string
     */
    public function getPluginDir(): string
    {
        return $this->pluginDir;
    }

    /**
     * Get the plugin URL.
     *
     * @return string
     */
    public function getPluginUrl(): string
    {
        return $this->pluginUrl;
    }

    /**
     * Get the plugin assets URL.
     *
     * @return string
     */
    public function getAssetsUrl(): string
    {
        return $this->pluginUrl . '/build';
    }
    
    /**
     * Get the plugin file.
     *
     * @return string
     */
    public function getPluginFile(): string
    {
        return $this->pluginFile;
    }
} 