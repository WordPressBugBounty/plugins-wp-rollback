<?php

/**
 * @package WpRollback\SharedCore\Contracts
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Contracts;

use WP_REST_Request;
use WpRollback\SharedCore\Core\Utilities\PluginUtility;

/**
 * Interface for API routes.
 *
 */
abstract class ApiRouteV1
{
    /**
     * Rest API namespace.
     *
     * @var string
     */
    protected string $namespace = 'wp-rollback/v1';

    /**
     * Register the route.
     *
     *
     * @return void
     */
    abstract public function register(): void;

    /**
     * Permission validation callback.
     *
     *
     * @param WP_REST_Request $request
     * @return bool
     */
    public function permissionValidation(WP_REST_Request $request): bool
    {
        return PluginUtility::currentUserCanRollback();
    }
} 