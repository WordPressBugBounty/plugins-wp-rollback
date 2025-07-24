<?php

/**
 * @package WpRollback\SharedCore\RestAPI
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\RestAPI;

use WP_REST_Response;
use WP_Error;

/**
 * Response formatter for API responses.
 *
 * @since 1.0.0
 */
class ResponseFormatter
{
    /**
     * Format a successful response.
     *
     * @since 1.0.0
     *
     * @param mixed $data Response data
     * @param string|null $message Optional success message
     * @param int $status HTTP status code
     * @return WP_REST_Response
     */
    public static function success($data = null, ?string $message = null, int $status = 200): WP_REST_Response
    {
        $response = [
            'success' => true,
        ];

        if (!is_null($data)) {
            $response['data'] = $data;
        }

        if (!is_null($message)) {
            $response['message'] = $message;
        }

        return new WP_REST_Response($response, $status);
    }

    /**
     * Format an error response.
     *
     * @since 1.0.0
     *
     * @param string $code Error code
     * @param string $message Error message
     * @param array<string, mixed> $data Additional error data
     * @param int $status HTTP status code
     * @return WP_Error
     */
    public static function error(string $code, string $message, array $data = [], int $status = 400): WP_Error
    {
        $data['status'] = $status;
        return new WP_Error($code, $message, $data);
    }

    /**
     * Convert a WP_Error to a WP_REST_Response.
     *
     * @since 1.0.0
     *
     * @param WP_Error $error The error to convert
     * @return WP_REST_Response
     */
    public static function errorToResponse(WP_Error $error): WP_REST_Response
    {
        $errorData = $error->get_error_data();
        $status = isset($errorData['status']) ? $errorData['status'] : 400;

        $response = [
            'success' => false,
            'code' => $error->get_error_code(),
            'message' => $error->get_error_message(),
        ];

        if (!empty($errorData)) {
            $response['data'] = $errorData;
        }

        return new WP_REST_Response($response, $status);
    }
} 