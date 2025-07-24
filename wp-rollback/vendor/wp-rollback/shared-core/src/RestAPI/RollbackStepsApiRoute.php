<?php

/**
 * API route for retrieving rollback steps.
 *
 * @package WpRollback\SharedCore\RestAPI
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\RestAPI;

use WP_REST_Server;
use WpRollback\SharedCore\Core\Contracts\ApiRouteV1;
use WpRollback\SharedCore\Rollbacks\Registry\RollbackStepRegisterer;

/**
 * API route for retrieving rollback steps
 * 
 * @since 1.0.0
 */
class RollbackStepsApiRoute extends ApiRouteV1
{
    /**
     * @var RollbackStepRegisterer
     */
    private RollbackStepRegisterer $stepRegisterer;

    /**
     * Constructor
     * 
     * @param RollbackStepRegisterer $stepRegisterer The rollback step registerer
     */
    public function __construct(RollbackStepRegisterer $stepRegisterer)
    {
        $this->stepRegisterer = $stepRegisterer;
    }

    /**
     * Register the REST route
     * 
     * @since 1.0.0
     */
    public function register(): void
    {
        register_rest_route(
            $this->namespace,
            '/rollback-steps/',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'getSteps'],
                'permission_callback' => [$this, 'permissionValidation'],
            ]
        );
    }

    /**
     * Get all registered rollback steps
     * 
     * @since 1.0.0
     * @return \WP_REST_Response Response containing the rollback steps
     */
    public function getSteps()
    {
        $rollbackSteps = $this->stepRegisterer->getAllRollbackSteps();
        $stepsData = [];

        foreach ($rollbackSteps as $rollbackStepClass) {
            $stepsData[] = [
                'id' => $rollbackStepClass::id(),
                'rollbackProcessingMessage' => $rollbackStepClass::rollbackProcessingMessage(),
            ];
        }

        return rest_ensure_response([
            'success' => true,
            'steps' => $stepsData,
        ]);
    }

    /**
     * Get the routes that this API handler registers
     * 
     * @since 1.0.0
     * @return array Array of routes
     */
    public function getRoutes(): array
    {
        return [
            'rollbackSteps' => [
                'method' => WP_REST_Server::READABLE,
                'callback' => 'getSteps',
            ]
        ];
    }
} 