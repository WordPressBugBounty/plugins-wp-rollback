<?php

/**
 * This contact uses to create new api routes.
 *
 * @package WpRollback\SharedCore\Core\Contracts
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Core\Contracts;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * @since 1.0.0
 */
interface ApiRoute
{
    /**
     * @since 1.0.0
     */
    public function register(): void;

    /**
     * @since 1.0.0
     * @return bool|WP_Error
     */
    public function permissionValidation(WP_REST_Request $request);

    /**
     * @since 1.0.0
     *
     * @return WP_REST_Response|WP_Error
     */
    public function processRequest(WP_REST_Request $request);
} 