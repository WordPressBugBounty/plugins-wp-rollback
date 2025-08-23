<?php

/**
 * This endpoint is used to process rollback of plugins or themes from the WordPress repository.
 *
 * @package WpRollback\SharedCore\RestAPI
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\RestAPI;

use WP_REST_Request;
use WP_REST_Server;
use WpRollback\SharedCore\Core\Contracts\ApiRouteV1;
use WpRollback\SharedCore\Core\SharedCore;
use WpRollback\SharedCore\Core\Exceptions\Primitives\Exception;
use WpRollback\SharedCore\Rollbacks\DTO\RollbackApiRequestDTO;
use WpRollback\SharedCore\Rollbacks\Registry\RollbackStepRegisterer;
use WpRollback\SharedCore\Rollbacks\Services\MaintenanceService;

/**
 * @since 1.0.0
 */
class ProcessRollbackApiRoute extends ApiRouteV1
{
    /**
     * @since 1.0.0
     */
    private RollbackStepRegisterer $rollbackStepRegisterer;

    /**
     * @since 1.0.0
     */
    public function __construct(RollbackStepRegisterer $rollbackStepRegisterer)
    {
        $this->rollbackStepRegisterer = $rollbackStepRegisterer;
    }

    /**
     * @unreleased
     */
    public function register(): void
    {
        $defaultArgs = [
            'type' => [
                'required' => true,
                'type' => 'string',
                'enum' => ['plugin', 'theme'],
            ],
            'version' => [
                'required' => true,
                'type' => 'string',
            ],
            'step' => [
                'required' => true,
                'type' => 'string',
                'enum' => $this->rollbackStepRegisterer->getAllRollbackStepsIds(),
            ],
            'slug' => [
                'required' => true,
                'type' => 'string',
                'enum' => array_merge($this->getPluginsSlug(), $this->getThemesSlug()),
            ],
        ];

        /**
         * Filter the rollback API route arguments.
         * 
         * @since 1.0.0
         * @param array $args The default route arguments
         */
        $args = apply_filters('wpr_rollback_api_route_args', $defaultArgs);

        register_rest_route(
            $this->namespace,
            '/process-rollback/',
            [
                'methods' => 'POST',
                'callback' => [$this, 'processRequest'],
                'permission_callback' => [$this, 'permissionValidation'],
                'args' => $args,
            ]
        );
    }

    /**
     * This function returns an array of route arguments.
     *
     * @since 1.0.0
     * @return array<string, array<string, mixed>> Array of route arguments
     */
    public function getRoutes(): array
    {
        return [
            'start' => [
                'method' => WP_REST_Server::READABLE,
                'callback' => 'getOption',
            ],
            'progress' => [
                'method' => WP_REST_Server::CREATABLE,
                'callback' => 'setOption',
            ]
        ];
    }

    /**
     * @inheritdoc
     * @since 1.0.0
     */
    public function permissionValidation(WP_REST_Request $request)
    {
        $type = $request->get_param('type');
        $hasPermission = false;

        if ('plugin' === $type) {
            $hasPermission = current_user_can('update_plugins');
        } elseif ('theme' === $type) {
            $hasPermission = current_user_can('update_themes');
        }

        return parent::permissionValidation($request) && $hasPermission;
    }

    /**
     * Process the rollback request
     *
     * @inheritdoc
     * @since 1.0.0
     * @param WP_REST_Request $request The request object
     * @return \WP_REST_Response|\WP_Error The response
     */
    public function processRequest(WP_REST_Request $request)
    {
        // Define constant to indicate rollback is active (for multisite file type filters)
        if (!defined('WPR_ROLLBACK_ACTIVE')) {
            define('WPR_ROLLBACK_ACTIVE', true);
        }
        
        $slug = $request->get_param('slug');
        $type = $request->get_param('type');
        $version = $request->get_param('version');
        $step = $request->get_param('step');

        if (! $type || ! $slug || ! $version || ! $step) {
            return rest_ensure_response([
                'success' => false,
                'message' => esc_html__('Invalid request. Required parameters are missing.', 'wp-rollback'),
            ]);
        }

        try {
            
            $rollbackStep = $this->rollbackStepRegisterer->getRollbackStepById($step);

            if (null === $rollbackStep) {
                return rest_ensure_response([
                    'success' => false,
                    'message' => 'Unknown step provided.',
                ]);
            }

            // Build the base request data
            $requestData = [
                'assetType' => $type,
                'assetSlug' => $slug,
                'assetVersion' => $version,
                'meta' => $request->get_param('meta') ?? [],
            ];

            /**
             * Filter the rollback API request data before creating the DTO.
             * 
             * @since 1.0.0
             * @param array $requestData The base request data
             * @param WP_REST_Request $request The full request object
             */
            $requestData = apply_filters('wpr_rollback_api_request_data', $requestData, $request);

            $rollbackApiRequestDTO = RollbackApiRequestDTO::fromApiRequestData($requestData);
            $result = $rollbackStep->execute($rollbackApiRequestDTO);

            // If this is the final step (replace-asset) and it succeeded, trigger success actions
            if ($result->isSuccess() && $step === 'replace-asset') {
                $resultData = $result->getData();
                
                if (isset($resultData['asset_path'], $resultData['current_version'])) {
                    do_action(
                        "wpr_{$type}_rollback_success",
                        $resultData['asset_path'],
                        $resultData['current_version'],
                        $rollbackApiRequestDTO->getMeta()
                    );
                }
            } elseif (!$result->isSuccess()) {
                // Handle failure - ensure maintenance mode is disabled
                $this->cleanupMaintenanceMode();
                
                do_action(
                    "wpr_{$type}_rollback_failed",
                    $slug,
                    $version,
                    $result->getMessage()
                );
            }

            // Return the response
            return rest_ensure_response([
                'success' => $result->isSuccess(),
                'data'    => $result->getRollbackApiRequestDTO()->getData(),
                'message' => $result->getMessage(),
            ]);
        } catch (Exception $e) {
            // Ensure maintenance mode is disabled on any exception
            $this->cleanupMaintenanceMode();
            
            do_action(
                "wpr_{$type}_rollback_failed",
                $slug,
                $version,
                $e->getMessage()
            );
            
            return rest_ensure_response([
                'success' => false,
                'data' => null,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get array of plugin slugs
     *
     * @since 1.0.0
     * @return array<string> Array of plugin slugs
     */
    private function getPluginsSlug(): array
    {
        if (! function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugins = get_plugins();

        $pluginSlugs = [];
        foreach ($plugins as $pluginPath => $pluginData) {
            $result = strstr((string) $pluginPath, '/', true);
            $pluginSlugs[] = $result ?: $pluginPath;
        }

        return $pluginSlugs;
    }

    /**
     * Get array of theme slugs
     *
     * @since 1.0.0
     * @return array<string> Array of theme slugs
     */
    private function getThemesSlug(): array
    {
        return array_keys(wp_get_themes());
    }

    /**
     * Cleanup maintenance mode as a failsafe
     * This ensures the site doesn't get stuck in maintenance mode if rollback fails
     *
     * @since 1.0.0
     * @return void
     */
    private function cleanupMaintenanceMode(): void
    {
        try {
            /** @var MaintenanceService $maintenanceService */
            $maintenanceService = SharedCore::container()->make(MaintenanceService::class);
            
            // Force disable maintenance mode to ensure site is accessible
            $maintenanceService->forceDisableMaintenanceMode();
            
        } catch (Exception $e) {
            // Even if cleanup fails, don't throw - we're already in error handling
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log(sprintf('[WP Rollback] Failed to cleanup maintenance mode: %s', $e->getMessage()));
            }
        }
    }
} 