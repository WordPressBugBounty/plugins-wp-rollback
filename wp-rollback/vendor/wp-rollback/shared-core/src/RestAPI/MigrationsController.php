<?php

/**
 * Migrations REST Controller
 *
 * Provides endpoints for viewing, running, reverting and retrying migration tasks.
 *
 * @package WpRollback\SharedCore\RestAPI
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\RestAPI;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Response;
use WP_REST_Request;
use WP_Error;
use WpRollback\SharedCore\Core\SharedCore;
use WpRollback\SharedCore\Migrations\MigrationManager;

/**
 * Class MigrationsController
 */
class MigrationsController extends WP_REST_Controller
{
    /**
     * @var string Namespace
     */
    protected $namespace = 'wp-rollback/v1';

    /**
     * @var string Rest base
     */
    protected $rest_base = 'migrations';

    /**
     * Register routes
     *
     * @return void
     */
    public function registerRoutes(): void
    {
        // Get list of migrations
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'getItems'],
                    'permission_callback' => [$this, 'getItemsPermissionsCheck'],
                ],
            ]
        );

        // Run single migration
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[a-zA-Z0-9_-]+)/run',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [$this, 'runMigration'],
                    'permission_callback' => [$this, 'updateItemsPermissionsCheck'],
                ],
            ]
        );

        // Revert single migration
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[a-zA-Z0-9_-]+)/revert',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [$this, 'revertMigration'],
                    'permission_callback' => [$this, 'updateItemsPermissionsCheck'],
                ],
            ]
        );

        // Retry single migration
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[a-zA-Z0-9_-]+)/retry',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [$this, 'retryMigration'],
                    'permission_callback' => [$this, 'updateItemsPermissionsCheck'],
                ],
            ]
        );
    }

    /**
     * Check permissions for reading migration items.
     *
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error
     */
    public function getItemsPermissionsCheck($request)
    {
        if (!current_user_can('update_plugins') && !current_user_can('update_themes')) {
            return new WP_Error(
                'rest_forbidden',
                __('You do not have permission to view migrations.', 'wp-rollback'),
                ['status' => rest_authorization_required_code()]
            );
        }

        return true;
    }

    /**
     * Check permissions for updating/running migrations.
     *
     * Running, retrying and reverting change the database and move backups
     * (reverting a table migration drops the table), so this also requires
     * manage_options. A role that can only update themes can't do it.
     *
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error
     */
    public function updateItemsPermissionsCheck($request)
    {
        if (
            !current_user_can('manage_options')
            || (!current_user_can('update_plugins') && !current_user_can('update_themes'))
        ) {
            return new WP_Error(
                'rest_forbidden',
                __('You do not have permission to execute migrations.', 'wp-rollback'),
                ['status' => rest_authorization_required_code()]
            );
        }

        return true;
    }

    /**
     * Get all migration tasks with status.
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function getItems($request)
    {
        try {
            /** @var MigrationManager $manager */
            $manager = SharedCore::container()->make(MigrationManager::class);
            $statusList = $manager->getMigrationsStatus();

            $items = [];
            foreach ($statusList as $id => $data) {
                $items[] = [
                    'id'                   => $data['id'],
                    'timestamp'            => $data['timestamp'],
                    'date_formatted'       => gmdate('Y-m-d H:i:s \U\T\C', $data['timestamp']),
                    'label'                => $data['label'],
                    'description'          => $data['description'],
                    'status'               => $data['status'],
                    'can_revert'           => $data['can_revert'],
                    'started_at'           => $data['started_at'] ? gmdate('Y-m-d H:i:s \U\T\C', $data['started_at']) : null,
                    'completed_at'         => $data['completed_at'] ? gmdate('Y-m-d H:i:s \U\T\C', $data['completed_at']) : null,
                    'reverted_at'          => $data['reverted_at'] ? gmdate('Y-m-d H:i:s \U\T\C', $data['reverted_at']) : null,
                    'attempts'             => $data['attempts'],
                    'error'                => $data['error'],
                ];
            }

            return new WP_REST_Response([
                'items' => $items,
                'total' => count($items),
            ], 200);
        } catch (\Throwable $e) {
            return new WP_Error(
                'migrations_fetch_error',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    /**
     * Run a specific migration task.
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function runMigration($request)
    {
        $id = (string) $request['id'];

        try {
            /** @var MigrationManager $manager */
            $manager = SharedCore::container()->make(MigrationManager::class);
            $success = $manager->runMigration($id);

            if ($success) {
                return new WP_REST_Response([
                    'success' => true,
                    /* translators: %s: Migration ID */
                    'message' => sprintf(__('Migration "%s" executed successfully.', 'wp-rollback'), $id),
                ], 200);
            }

            return new WP_Error(
                'migration_run_failed',
                /* translators: %s: Migration ID */
                sprintf(__('Migration "%s" failed to run.', 'wp-rollback'), $id),
                ['status' => 500]
            );
        } catch (\Throwable $e) {
            return new WP_Error('migration_run_error', $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * Revert a specific migration task.
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function revertMigration($request)
    {
        $id = (string) $request['id'];

        try {
            /** @var MigrationManager $manager */
            $manager = SharedCore::container()->make(MigrationManager::class);
            $success = $manager->revertMigration($id);

            if ($success) {
                return new WP_REST_Response([
                    'success' => true,
                    /* translators: %s: Migration ID */
                    'message' => sprintf(__('Migration "%s" reverted successfully.', 'wp-rollback'), $id),
                ], 200);
            }

            return new WP_Error(
                'migration_revert_failed',
                /* translators: %s: Migration ID */
                sprintf(__('Migration "%s" failed to revert.', 'wp-rollback'), $id),
                ['status' => 500]
            );
        } catch (\Throwable $e) {
            return new WP_Error('migration_revert_error', $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * Retry a failed migration task.
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function retryMigration($request)
    {
        $id = (string) $request['id'];

        try {
            /** @var MigrationManager $manager */
            $manager = SharedCore::container()->make(MigrationManager::class);
            $success = $manager->retryMigration($id);

            if ($success) {
                return new WP_REST_Response([
                    'success' => true,
                    /* translators: %s: Migration ID */
                    'message' => sprintf(__('Migration "%s" retried and completed successfully.', 'wp-rollback'), $id),
                ], 200);
            }

            return new WP_Error(
                'migration_retry_failed',
                /* translators: %s: Migration ID */
                sprintf(__('Migration "%s" retry failed.', 'wp-rollback'), $id),
                ['status' => 500]
            );
        } catch (\Throwable $e) {
            return new WP_Error('migration_retry_error', $e->getMessage(), ['status' => 500]);
        }
    }
}
