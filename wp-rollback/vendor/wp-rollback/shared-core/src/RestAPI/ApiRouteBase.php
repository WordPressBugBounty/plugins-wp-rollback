<?php

/**
 * @package WpRollback\SharedCore\RestAPI
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\RestAPI;

use WP_REST_Request;
use WP_Error;
use WpRollback\SharedCore\Core\Utilities\PluginUtility;

/**
 * Base class for API routes.
 *
 * @since 1.0.0
 */
abstract class ApiRouteBase
{
    /**
     * REST API namespace.
     *
     * @var string
     */
    protected string $namespace = 'wp-rollback/v1';

    /**
     * Register the route.
     *
     * @since 1.0.0
     *
     * @return void
     */
    abstract public function register(): void;

    /**
     * Permission validation callback.
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request $request
     * @return bool
     */
    public function permissionValidation(WP_REST_Request $request): bool
    {
        return PluginUtility::currentUserCanRollback();
    }

    /**
     * Create a standardized error response.
     *
     * @since 1.0.0
     *
     * @param string $code Error code
     * @param string $message Error message
     * @param int $status HTTP status code
     * @return WP_Error
     */
    protected function createError(string $code, string $message, int $status = 400): WP_Error
    {
        return new WP_Error($code, $message, ['status' => $status]);
    }

    /**
     * Sanitize and validate request data.
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request $request
     * @param array<string, mixed> $fields Fields to validate with their sanitization callbacks
     * @return array<string, mixed>|WP_Error Sanitized data or error
     */
    protected function validateRequestData(WP_REST_Request $request, array $fields)
    {
        $data = [];

        foreach ($fields as $field => $args) {
            $value = $request->get_param($field);

            if (!isset($value) && ($args['required'] ?? false)) {
                return $this->createError(
                    'missing_required_field',
                    sprintf('Missing required field: %s', $field)
                );
            }

            if (isset($value)) {
                if (isset($args['sanitize']) && is_callable($args['sanitize'])) {
                    $value = call_user_func($args['sanitize'], $value);
                }

                if (isset($args['validate']) && is_callable($args['validate'])) {
                    $valid = call_user_func($args['validate'], $value);
                    if (true !== $valid) {
                        return $this->createError(
                            'invalid_field_value',
                            is_string($valid) ? $valid : sprintf('Invalid value for field: %s', $field)
                        );
                    }
                }

                $data[$field] = $value;
            }
        }

        return $data;
    }
} 