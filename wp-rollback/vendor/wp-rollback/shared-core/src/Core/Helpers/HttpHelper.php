<?php

declare(strict_types=1);

namespace WpRollback\SharedCore\Core\Helpers;

use WpRollback\SharedCore\Core\Exceptions\Primitives\Exception;
use WP_Error;

/**
 * HTTP request helper class
 *
 * @package WpRollback\SharedCore\Core\Helpers
 * @since 1.0.0
 */
class HttpHelper
{
    /**
     * This function is used to make a remote GET request.
     *
     * This function internally decides which function to use based on the availability of the function.
     * If the function is available, it uses `vip_safe_wp_remote_get` function.
     * Otherwise, it falls back to using `wp_remote_get` function.
     *
     * @param string $url The URL to make the GET request to
     * @param array $args Optional arguments to pass along with the request
     *
     * @since 1.0.0
     *
     * @return array|WP_Error The response from the remote GET request
     * @throws Exception
     */
    public static function remoteGet(string $url, array $args = [])
    {
        $fn = 'wp_remote_get';
        $vipFn = 'vip_safe_wp_remote_get';

        if (function_exists($vipFn)) {
            $fn = $vipFn;
        }

        return $fn($url, $args);
    }
} 