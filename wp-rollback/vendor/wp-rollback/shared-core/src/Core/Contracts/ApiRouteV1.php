<?php

/**
 * This contract class is used to create api routes.
 *
 * @package WpRollback\SharedCore\Core\Contracts
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Core\Contracts;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WpRollback\SharedCore\Core\Exceptions\BindingResolutionException;

/**
 * @since 1.0.0
 */
class ApiRouteV1 implements ApiRoute
{
    /**
     * @since 1.0.0
     */
    protected string $namespace = 'wp-rollback/v1';

    /**
     * @throws BindingResolutionException
     */
    public function register(): void
    {
        throw new BindingResolutionException('Not implemented');
    }

    /**
     * @return bool|WP_Error
     */
    public function permissionValidation(WP_REST_Request $request)
    {
        // Verify the nonce
        $nonce = $request->get_header('X-WP-Nonce');

        if (! wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('wp_rollback_rest_forbidden', 'Invalid Access.', ['status' => 403]);
        }

        return true;
    }

    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     * @return WP_REST_Response|WP_Error
     */
    public function processRequest(WP_REST_Request $request)
    {
        throw new BindingResolutionException('Not implemented');
    }
} 