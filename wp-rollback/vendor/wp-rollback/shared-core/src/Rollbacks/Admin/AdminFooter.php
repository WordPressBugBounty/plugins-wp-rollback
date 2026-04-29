<?php

/**
 * AdminFooter
 *
 * Replaces the default WordPress wp-admin footer text and version string
 * on all WP Rollback admin pages with subtle WP Rollback branding.
 *
 * @package WpRollback\SharedCore\Rollbacks\Admin
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Rollbacks\Admin;

use WpRollback\SharedCore\Core\BaseConstants;

/**
 * Class AdminFooter
 *
 */
class AdminFooter
{
    /**
     * @var BaseConstants
     */
    private BaseConstants $constants;

    /**
     * @param BaseConstants $constants Plugin constants for version info.
     */
    public function __construct(BaseConstants $constants)
    {
        $this->constants = $constants;
    }

    /**
     * Register footer filter hooks.
     *
     * @return void
     */
    public function initialize(): void
    {
        add_filter('admin_footer_text', [$this, 'footerText']);
        add_filter('update_footer', [$this, 'footerVersion'], 999);
    }

    /**
     * Replace the left-side footer text on WP Rollback pages.
     *
     * The parameter is intentionally untyped: other plugins/themes may filter
     * `admin_footer_text` and return null or non-string values, which would
     * trigger a TypeError under strict_types. We normalize defensively.
     *
     * @param mixed $text Existing footer text.
     * @return string
     */
    public function footerText($text): string
    {
        $text = is_string($text) ? $text : '';

        if (!$this->isWpRollbackPage()) {
            return $text;
        }

        return sprintf(
            /* translators: %s: WP Rollback website URL */
            __('Thank you for using <a href="%s" target="_blank" rel="noopener noreferrer">WP Rollback</a>.', 'wp-rollback'),
            'https://wprollback.com'
        );
    }

    /**
     * Replace the right-side version string with the plugin version on WP Rollback pages.
     *
     * The parameter is intentionally untyped: other plugins/themes may filter
     * `update_footer` and return null or non-string values, which would
     * trigger a TypeError under strict_types. We normalize defensively.
     *
     * @param mixed $text Existing version text.
     * @return string
     */
    public function footerVersion($text): string
    {
        $text = is_string($text) ? $text : '';

        if (!$this->isWpRollbackPage()) {
            return $text;
        }

        return sprintf('v%s', esc_html($this->constants->getVersion()));
    }

    /**
     * Determine whether the current admin screen belongs to WP Rollback.
     *
     * @return bool
     */
    private function isWpRollbackPage(): bool
    {
        $screen = get_current_screen();

        if (!$screen) {
            return false;
        }

        return strpos($screen->id, 'wp-rollback') !== false;
    }
}
