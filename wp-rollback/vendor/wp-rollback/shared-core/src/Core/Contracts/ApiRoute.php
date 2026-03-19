<?php

/**
 * This contact uses to create new api routes.
 *
 * @package WpRollback\SharedCore\Core\Contracts
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Core\Contracts;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 */
interface ApiRoute
{
    /**
     */
    public function register(): void;

    /**
     * @return bool|WP_Error
     */
    public function permissionValidation(WP_REST_Request $request);

    /**
     *
     * @return WP_REST_Response|WP_Error
     */
    public function processRequest(WP_REST_Request $request);
} 