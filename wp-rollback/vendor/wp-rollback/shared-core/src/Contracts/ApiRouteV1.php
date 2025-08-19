<?php

/**
 * @package WpRollback\SharedCore\Contracts
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Contracts;

use WP_REST_Request;
use WpRollback\SharedCore\Core\Utilities\PluginUtility;

/**
 * Interface for API routes.
 *
 * @since 1.0.0
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
} 