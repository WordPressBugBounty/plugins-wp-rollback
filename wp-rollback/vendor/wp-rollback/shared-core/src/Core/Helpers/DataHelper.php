<?php

declare(strict_types=1);

namespace WpRollback\SharedCore\Core\Helpers;

use WpRollback\SharedCore\Core\ConstantsInterface;

/**
 * Data helper class for sanitization and URL handling
 *
 * @package WpRollback\SharedCore\Core\Helpers
 * @since 1.0.0
 */
class DataHelper
{
    /**
     * This function is used to sanitize data.
     *
     * @since 1.0.0
     *
     * @param string $data The text to sanitize
     *
     * @return string
     */
    public static function sanitizeTextField(string $data): string
    {
        return sanitize_text_field(wp_unslash($data));
    }

    /**
     * This function is used to generate a nonce URL.
     *
     * Note: This function is used to generate a nonce URL from a given plain URL.
     *
     * @since 1.0.0
     *
     * @param ConstantsInterface $constants The Constants instance
     * @param string $action The action to perform
     * @param string $url The URL to generate a nonce for
     *
     * @return string The generated nonce URL
     */
    public static function getNonceUrl(ConstantsInterface $constants, string $action, string $url): string
    {
        return add_query_arg(
            [ $constants->getNonce() => wp_create_nonce($action)],
            $url
        );
    }

    /**
     * This function is used to generate a nonce action name.
     *
     * @since 1.0.0
     *
     * @param ConstantsInterface $constants The Constants instance
     * @param string $action The action to generate a nonce action name for
     *
     * @return string The generated nonce action name
     */
    public static function getNonceActionName(ConstantsInterface $constants, string $action): string
    {
        return $constants->getSlug() . "-$action";
    }
} 