<?php

/**
 * Request
 *
 * This class is used to manage the request data.
 * It also provides methods to retrieve, sanitize, and redirect the request.
 *
 * @package WpRollback\SharedCore\Core
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Core;

/**
 * Class Request
 *
 * @since 1.0.0
 */
class Request
{
    /**
     * This function is used to retrieve data from the request.
     *
     * @param string $key The key to retrieve from the request
     * @param mixed $default The default value to return if the key is not found
     *
     * @since 1.0.0
     * @return mixed Sanitized data from the request
     */
    public function get(string $key, $default = null)
    {
        return isset($_GET[$key]) ? $this->sanitize($_GET[$key]) : $default;
    }

    /**
     * This function is used to retrieve data from the POST request.
     * IMPORTANT: Nonce verification should be performed before accessing POST data.
     *
     * @param string $key The key to retrieve from the request
     * @param mixed $default The default value to return if the key is not found
     * @param bool $verify Whether to verify nonce before retrieving data (nonce must be verified separately)
     *
     * @since 1.0.0
     * @return mixed Sanitized data from the request
     */
    public function post(string $key, $default = null, bool $verify = true)
    {
        if ($verify && !$this->isNonceVerified()) {
            return $default;
        }
        
        return isset($_POST[$key]) ? $this->sanitize($_POST[$key]) : $default;
    }

    /**
     * This function is used to retrieve all data from the request.
     * IMPORTANT: Nonce verification should be performed before accessing POST data.
     *
     * @param bool $verify Whether to verify nonce before retrieving data (nonce must be verified separately)
     * 
     * @since 1.0.0
     * @return array Request data from the GET and POST superglobals
     */
    public function all(bool $verify = true): array
    {
        if ($verify && !$this->isNonceVerified()) {
            return $_GET;
        }
        
        return array_map([$this, 'sanitize'], array_merge($_GET, $_POST));
    }

    /**
     * Check if nonce has been verified in the current request.
     *
     * @since 1.0.0
     * @return bool Whether nonce has been verified
     */
    private function isNonceVerified(): bool
    {
        return (bool) apply_filters('wp_rollback_nonce_verified', false);
    }

    /**
     * This function is used to check if a key exists in the request.
     *
     * @param string $key The key to check for in the request
     * @param bool $verify Whether to verify nonce before checking POST data
     *
     * @since 1.0.0
     */
    public function has(string $key, bool $verify = true): bool
    {
        $all = $this->all($verify);
        return $all && array_key_exists($key, $all);
    }

    /**
     * This function is used to sanitize data.
     *
     * @param array|string $data The data to sanitize
     *
     * @since 1.0.0
     * @return array|string
     */
    public function sanitize($data)
    {
        // If the data is a string, sanitize it.
        if (! is_array($data)) {
            return sanitize_text_field(wp_unslash($data));
        }

        return array_map([ $this, __FUNCTION__], $data);
    }

    /**
     * Gets the incoming request headers.
     *
     * Some servers are not using Apache and "getallheaders()" will not work, so we may need to
     * build our own headers.
     *
     * @since 1.0.0
     */
    public function getRequestHeaders(): array
    {
        $headers = [];

        if (function_exists('getallheaders')) {
            $_server = getallheaders();

            foreach ($_server as $name => $value) {
                $headers[strtoupper($name)] = $value;
            }

            return $headers;
        }

        foreach ($_SERVER as $name => $value) {
            if (strpos($name, 'HTTP_') === 0) {
                $formattedName = str_replace('_', ' ', substr($name, 5));
                $arrayKey = str_replace(' ', '-', $formattedName);
                $headers[strtoupper($arrayKey)] = $value;
            }
        }

        return $headers;
    }

    /**
     * This function is used to retrieve the request body.
     *
     * @since 1.0.0
     */
    public function getBody(): string
    {
        $function = 'file_get_contents';

        if (function_exists('wpcom_vip_file_get_contents')) {
            $function = 'wpcom_vip_file_get_contents';
        }

        return $function('php://input');
    }

    /**
     * This function is used to check if the request has a valid nonce.
     *
     * @param string $action The action to check the nonce for
     * @param string $nonceName The name of the nonce field
     *
     * @since 1.0.0
     */
    public function hasValidNonce(string $action, string $nonceName = 'wp-rollback-nonce'): bool
    {
        $requestedData = $this->all();
        return array_key_exists($nonceName, $requestedData)
            && wp_verify_nonce($requestedData[$nonceName], $action);
    }

    /**
     * This function is used to check if the request has a valid capability.
     *
     * @param string $capability The ability to check for
     *
     * @since 1.0.0
     */
    public function hasPermission(string $capability): bool
    {
        return current_user_can($capability);
    }

    /**
     * This function is used to check if the request method is valid.
     *
     * @since 1.0.0
     */
    public function usesHttpMethod(string $type): bool
    {
        return isset($_SERVER['REQUEST_METHOD']) && (strtoupper($type) === $_SERVER['REQUEST_METHOD']);
    }

    /**
     * This function is used to check if the request uses the GET method.
     *
     * @since 1.0.0
     */
    public function usesGetMethod(): bool
    {
        return $this->usesHttpMethod('GET');
    }

    /**
     * This function is used to check if the request uses the POST method.
     *
     * @since 1.0.0
     */
    public function usesPostMethod(): bool
    {
        return $this->usesHttpMethod('POST');
    }
} 