<?php

/**
 * Maintenance mode service for managing WordPress .maintenance file.
 *
 * @package WpRollback\SharedCore\Rollbacks\Services
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Rollbacks\Services;

/**
 * Service for managing WordPress maintenance mode
 *
 * @since 1.0.0
 */
class MaintenanceService
{
    /**
     * Custom maintenance file name to avoid conflicts with WordPress core
     * 
     * @since 1.0.0
     * @var string
     */
    private const MAINTENANCE_FILE = '.wpr-maintenance';

    /**
     * Get the path to the maintenance file
     *
     * @since 1.0.0
     * @return string Path to maintenance file
     */
    private function getMaintenanceFilePath(): string
    {
        return ABSPATH . self::MAINTENANCE_FILE;
    }

    /**
     * Enable maintenance mode by creating maintenance file
     * Uses a custom file to avoid blocking admin/API requests
     *
     * @since 1.0.0
     * @return bool True if maintenance mode was enabled, false on failure
     */
    public function enableMaintenanceMode(): bool
    {
        $maintenanceFile = $this->getMaintenanceFilePath();
        
        // Create maintenance file with timestamp
        $content = json_encode([
            'time' => time(),
            'message' => __('Site is temporarily unavailable for scheduled maintenance. Please check back in a minute.', 'wp-rollback'),
            'rollback_active' => true
        ]);
        
        // Attempt to write the maintenance file
        $result = file_put_contents($maintenanceFile, $content);
        
        if ($result === false) {
            return false;
        }
        
        // Hook into template_redirect to show maintenance for frontend only
        add_action('template_redirect', [$this, 'showMaintenanceMessage'], 1);
        
        // Verify the file was created
        return file_exists($maintenanceFile);
    }

    /**
     * Show maintenance message for frontend requests only
     *
     * @since 1.0.0
     * @return void
     */
    public function showMaintenanceMessage(): void
    {
        // Skip for requests that shouldn't see maintenance mode
        if (!$this->shouldShowMaintenanceMessage()) {
            return;
        }

        $maintenanceFile = $this->getMaintenanceFilePath();
        
        if (!file_exists($maintenanceFile)) {
            return;
        }

        $data = json_decode(file_get_contents($maintenanceFile), true);
        
        // Check if maintenance is still valid (10 minutes)
        if (!$data || (time() - $data['time']) >= 600) {
            $this->disableMaintenanceMode();
            return;
        }

        // Send 503 status
        status_header(503);
        nocache_headers();

        // Use custom maintenance template if available
        if (file_exists(WP_CONTENT_DIR . '/maintenance.php')) {
            require_once WP_CONTENT_DIR . '/maintenance.php';
            die();
        }

        // Default maintenance message
        $message = $data['message'] ?? __('Briefly unavailable for scheduled maintenance. Check back in a minute.', 'wp-rollback');
        
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>" />
            <meta name="viewport" content="width=device-width">
            <title><?php esc_html_e('Maintenance', 'wp-rollback'); ?></title>
            <style type="text/css">
                body { 
                    background: #f1f1f1; 
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                    margin: 2em auto;
                    padding: 1em 2em;
                    max-width: 400px;
                }
                h1 { 
                    border-bottom: 1px solid #dadada;
                    clear: both;
                    color: #666;
                    font-size: 24px;
                    margin: 30px 0 0 0;
                    padding: 0 0 7px 0;
                }
                body { 
                    text-align: center;
                }
                #error-page {
                    margin-top: 50px;
                }
                #error-page p {
                    font-size: 14px;
                    line-height: 1.5;
                    margin: 25px 0 20px;
                }
            </style>
        </head>
        <body>
            <div id="error-page">
                <h1><?php esc_html_e('Maintenance Mode', 'wp-rollback'); ?></h1>
                <p><?php echo esc_html($message); ?></p>
            </div>
        </body>
        </html>
        <?php
        die();
    }

    /**
     * Check if current request is a REST API request
     *
     * @since 1.0.0
     * @return bool
     */
    private function isRestRequest(): bool
    {
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return true;
        }

        $prefix = rest_get_url_prefix();
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Used for route matching only, not stored or displayed
        $requestUri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
        if (strpos($requestUri, '/' . $prefix . '/') !== false) {
            return true;
        }

        return false;
    }

    /**
     * Disable maintenance mode by removing maintenance file
     *
     * @since 1.0.0
     * @return bool True if maintenance mode was disabled or wasn't active, false on failure
     */
    public function disableMaintenanceMode(): bool
    {
        $maintenanceFile = $this->getMaintenanceFilePath();
        
        // If file doesn't exist, maintenance mode is already disabled
        if (!file_exists($maintenanceFile)) {
            return true;
        }
        
        // Attempt to remove the maintenance file
        $result = @unlink($maintenanceFile);
        
        // Verify the file was removed
        return !file_exists($maintenanceFile);
    }

    /**
     * Check if maintenance mode is currently active
     *
     * @since 1.0.0
     * @return bool True if maintenance mode is active, false otherwise
     */
    public function isMaintenanceModeActive(): bool
    {
        $maintenanceFile = $this->getMaintenanceFilePath();
        
        if (!file_exists($maintenanceFile)) {
            return false;
        }
        
        $data = json_decode(file_get_contents($maintenanceFile), true);
        
        if (!$data || !isset($data['time'])) {
            return false;
        }
        
        // Consider maintenance mode inactive if file is older than 10 minutes
        if (time() - $data['time'] >= 600) {
            return false;
        }
        
        return true;
    }

    /**
     * Force disable maintenance mode regardless of file permissions or state
     * This is a more aggressive cleanup method for error recovery
     *
     * @since 1.0.0
     * @return void
     */
    public function forceDisableMaintenanceMode(): void
    {
        $maintenanceFile = $this->getMaintenanceFilePath();
        
        // Multiple attempts to ensure file is removed
        if (file_exists($maintenanceFile)) {
            // Try standard unlink
            @unlink($maintenanceFile);
            
            // If still exists, try with clearstatcache
            if (file_exists($maintenanceFile)) {
                clearstatcache(true, $maintenanceFile);
                @unlink($maintenanceFile);
            }
            
            // If still exists, try to empty the file first
            if (file_exists($maintenanceFile)) {
                @file_put_contents($maintenanceFile, '');
                @unlink($maintenanceFile);
            }
        }
    }

    /**
     * Initialize maintenance mode checks
     * This should be called early in WordPress load
     *
     * @since 1.0.0
     * @return void
     */
    public function init(): void
    {
        // Always check if maintenance mode is active
        // The filtering of who sees it happens in showMaintenanceMessage
        if ($this->isMaintenanceModeActive()) {
            add_action('template_redirect', [$this, 'showMaintenanceMessage'], 1);
        }
    }

    /**
     * Determine if we should show the maintenance message
     * Filters out admin, AJAX, REST, CLI, update processes, and monitoring tools
     *
     * @since 1.0.0
     * @return bool
     */
    private function shouldShowMaintenanceMessage(): bool
    {
        // Skip for admin, AJAX, REST API, and cron requests
        if (is_admin() || wp_doing_ajax() || wp_doing_cron() || $this->isRestRequest()) {
            return false;
        }

        // Skip for logged-in users with update capabilities
        if (is_user_logged_in() && (current_user_can('update_plugins') || current_user_can('update_themes'))) {
            return false;
        }

        // Skip for CLI requests
        if (defined('WP_CLI') && constant('WP_CLI')) {
            return false;
        }

        // Skip for WordPress update processes
        if (defined('WP_INSTALLING') && constant('WP_INSTALLING')) {
            return false;
        }

        // Skip if running update check functions
        if (did_action('wp_version_check') || did_action('wp_update_plugins') || did_action('wp_update_themes')) {
            return false;
        }

        // Skip for system cron or external monitoring
        if (!isset($_SERVER['REQUEST_URI']) || empty($_SERVER['HTTP_HOST'])) {
            return false;
        }

        // Skip for common monitoring tools and bots
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Used for bot detection only, not stored
            $userAgent = strtolower(wp_unslash($_SERVER['HTTP_USER_AGENT']));
            $monitoringAgents = [
                'nagios',
                'monitoring',
                'uptimerobot',
                'pingdom',
                'newrelic',
                'statuspage',
                'curl',
                'wget',
                'wp-cli'
            ];
            
            foreach ($monitoringAgents as $agent) {
                if (strpos($userAgent, $agent) !== false) {
                    return false;
                }
            }
        }

        /**
         * Filter to allow custom logic for showing maintenance mode
         *
         * @since 1.0.0
         * @param bool $should_show Whether to show maintenance mode
         */
        return apply_filters('wpr_should_show_maintenance_mode', true);
    }
}