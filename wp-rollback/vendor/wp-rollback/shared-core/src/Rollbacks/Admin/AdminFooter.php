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
     * @param string $text Existing footer text.
     * @return string
     */
    public function footerText(string $text): string
    {
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
     * @param string $text Existing version text.
     * @return string
     */
    public function footerVersion(string $text): string
    {
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

        return str_contains($screen->id, 'wp-rollback');
    }
}
