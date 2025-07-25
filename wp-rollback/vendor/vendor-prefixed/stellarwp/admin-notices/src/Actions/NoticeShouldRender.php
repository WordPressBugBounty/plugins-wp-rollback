<?php

declare(strict_types=1);


namespace WpRollback\Free\Dependencies\StellarWP\AdminNotices\Actions;

use DateTimeImmutable;
use DateTimeZone;
use WpRollback\Free\Dependencies\StellarWP\AdminNotices\AdminNotice;
use WpRollback\Free\Dependencies\StellarWP\AdminNotices\Traits\HasNamespace;

/**
 * Checks whether the given notice should be rendered based on the provided conditions.
 *
 * @since 2.0.0
 */
class NoticeShouldRender
{
    use HasNamespace;

    /**
     * @since 2.0.0
     */
    public function __invoke(AdminNotice $notice): bool
    {
        return $this->passesDismissedConditions($notice)
               && $this->passesDateLimits($notice)
               && $this->passesWhenCallback($notice)
               && $this->passesUserCapabilities($notice)
               && $this->passesScreenConditions($notice);
    }

    /**
     * Checks whether the notice should be displayed based on the provided date limits.
     *
     * @since 2.0.0 moved to the NoticeShouldRender class
     * @since 1.0.0
     */
    private function passesDateLimits(AdminNotice $notice): bool
    {
        if (!$notice->getAfterDate() && !$notice->getUntilDate()) {
            return true;
        }

        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        if ($notice->getAfterDate() && $notice->getAfterDate() > $now) {
            return false;
        }

        if ($notice->getUntilDate() && $notice->getUntilDate() < $now) {
            return false;
        }

        return true;
    }

    /**
     * Checks whether the notice should be displayed based on the provided callback.
     *
     * @since 2.0.0 moved to the NoticeShouldRender class
     * @since 1.0.0
     */
    private function passesWhenCallback(AdminNotice $notice): bool
    {
        $callback = $notice->getWhenCallback();

        if ($callback === null) {
            return true;
        }

        return $callback();
    }

    /**
     * Checks whether user limits were provided and they pass. Only one capability is required to pass, allowing for
     * multiple users have visibility.
     *
     * @since 2.0.0 moved to the NoticeShouldRender class
     * @since 1.0.0
     */
    private function passesUserCapabilities(AdminNotice $notice): bool
    {
        $capabilities = $notice->getUserCapabilities();

        if (empty($capabilities)) {
            return true;
        }

        foreach ($capabilities as $capability) {
            if ($capability->currentUserCan()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks whether the notice is limited to specific screens and the current screen matches the conditions. Only one
     * screen condition is required to pass, allowing for the notice to appear on multiple screens.
     *
     * @since 2.0.0 moved to the NoticeShouldRender class
     * @since 1.0.0
     */
    private function passesScreenConditions(AdminNotice $notice): bool
    {
        $screenConditions = $notice->getOnConditions();

        if (empty($screenConditions)) {
            return true;
        }

        $screen = get_current_screen();
        $currentUrl = get_admin_url(null, $_SERVER['REQUEST_URI']);

        foreach ($screenConditions as $screenCondition) {
            $condition = $screenCondition->getCondition();

            if ($screenCondition->isRegex()) {
                // do a regex comparison on the current url
                if (preg_match($condition, $currentUrl) === 1) {
                    return true;
                }
            } elseif (is_string($condition)) {
                // do a string comparison on the current url
                if (strpos($currentUrl, $condition) !== false) {
                    return true;
                }
            } else {
                // compare the condition array against the WP_Screen object
                foreach ($condition as $property => $value) {
                    if ($screen->$property === $value) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Checks whether the notice has been dismissed by the user.
     *
     * @since 2.0.0 moved to the NoticeShouldRender class
     * @since 1.1.0 added namespacing to the preferences key
     * @since 1.0.0
     */
    private function passesDismissedConditions(AdminNotice $notice): bool
    {
        global $wpdb;

        $userPreferences = get_user_meta(
            get_current_user_id(),
            $wpdb->get_blog_prefix() . 'persisted_preferences',
            true
        );

        $key = "stellarwp/admin-notices/$this->namespace";
        if (!is_array($userPreferences) || empty($userPreferences[$key])) {
            return true;
        }

        $dismissedNotices = $userPreferences[$key];

        if (key_exists($notice->getId(), $dismissedNotices)) {
            return false;
        }

        return true;
    }
}
