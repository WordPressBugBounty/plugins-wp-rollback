<?php

/**
 * Archives REST Controller
 *
 * @package WpRollback\SharedCore\Rollbacks\REST
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\RestAPI;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Response;
use WP_REST_Request;
use WP_Error;
use WpRollback\Pro\ActivityLog\Repository\ActivityRepository;

/**
 * REST API controller for managing archived assets
 *
 * @since 1.0.0
 */
class ArchivesController extends WP_REST_Controller
{
    /**
     * @var string Namespace
     */
    protected $namespace = 'wp-rollback/v1';

    /**
     * @var string Rest base
     */
    protected $rest_base = 'archives';

    /**
     * @var ActivityRepository|null
     */
    private ?ActivityRepository $_repository = null;

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        if (class_exists('WpRollback\Pro\ActivityLog\Repository\ActivityRepository')) {
            $this->_repository = new ActivityRepository();
        }
    }

    /**
     * Register routes
     *
     * @since 1.0.0
     * @return void
     */
    public function registerRoutes(): void
    {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'getItems'],
                    'permission_callback' => [$this, 'getItemsPermissionsCheck'],
                    'args' => $this->getCollectionParams(),
                ],
            ]
        );
        
        // Download endpoint
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>\d+)/download',
            [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'downloadArchive'],
                    'permission_callback' => [$this, 'downloadArchivePermissionsCheck'],
                    'args' => [
                        'id' => [
                            'description' => __('Archive ID', 'wp-rollback'),
                            'type' => 'integer',
                            'sanitize_callback' => 'absint',
                            'validate_callback' => 'rest_validate_request_arg',
                        ],
                    ],
                ],
            ]
        );
        
        // Delete endpoint
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>\d+)',
            [
                [
                    'methods' => WP_REST_Server::DELETABLE,
                    'callback' => [$this, 'deleteArchive'],
                    'permission_callback' => [$this, 'deleteArchivePermissionsCheck'],
                    'args' => [
                        'id' => [
                            'description' => __('Archive ID', 'wp-rollback'),
                            'type' => 'integer',
                            'sanitize_callback' => 'absint',
                            'validate_callback' => 'rest_validate_request_arg',
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Check permissions for getting items
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error
     */
    public function getItemsPermissionsCheck($request)
    {
        if (!current_user_can('update_plugins') && !current_user_can('update_themes')) {
            return new WP_Error(
                'rest_forbidden',
                __('You do not have permission to view archives.', 'wp-rollback'),
                ['status' => rest_authorization_required_code()]
            );
        }

        return true;
    }

    /**
     * Get archived assets
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function getItems($request)
    {
        $params = $request->get_params();
        
        // Check if Pro is active
        if (null === $this->_repository) {
            return new WP_Error(
                'pro_required',
                __('WP Rollback Pro is required to view archived assets.', 'wp-rollback'),
                ['status' => 400]
            );
        }

        try {
            // Get archives from activity log
            global $wpdb;
            $table = $wpdb->prefix . 'rollback_activity_log';
            $metaTable = $wpdb->prefix . 'rollback_activity_meta';
            
            $perPage = (int) ($params['per_page'] ?? 10);
            $page = (int) ($params['page'] ?? 1);
            $offset = ($page - 1) * $perPage;
            $type = $params['type'] ?? '';
            
            // Build query for archives only
            $whereClause = "WHERE a.status = 'success'";
            $whereParams = [];
            
            if ($type) {
                $whereClause .= " AND a.type = %s";
                $whereParams[] = $type;
            }
            
            // Get total count
            if ($type) {
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names cannot be prepared
                $countQuery = $wpdb->prepare(
                    "SELECT COUNT(DISTINCT a.id) 
                    FROM {$table} a
                    INNER JOIN {$metaTable} m ON a.id = m.rollback_id
                    WHERE m.meta_key = 'archive_action' AND m.meta_value = %s
                    AND a.status = 'success' AND a.type = %s",
                    'backup',
                    $type
                );
            } else {
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names cannot be prepared
                $countQuery = $wpdb->prepare(
                    "SELECT COUNT(DISTINCT a.id) 
                    FROM {$table} a
                    INNER JOIN {$metaTable} m ON a.id = m.rollback_id
                    WHERE m.meta_key = 'archive_action' AND m.meta_value = %s
                    AND a.status = 'success'",
                    'backup'
                );
            }
            
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- Custom table query, $countQuery is prepared above
            $total = (int) $wpdb->get_var($countQuery);
            
            // Get archives with all necessary data
            if ($type) {
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names cannot be prepared
                $query = $wpdb->prepare(
                    "SELECT DISTINCT
                        a.id,
                        a.type,
                        a.name,
                        a.slug,
                        a.version_to as version,
                        a.updated_by,
                        a.created_at,
                        (SELECT meta_value FROM {$metaTable} WHERE rollback_id = a.id AND meta_key = 'archive_file_size' LIMIT 1) as file_size,
                        (SELECT meta_value FROM {$metaTable} WHERE rollback_id = a.id AND meta_key = 'archive_file_path' LIMIT 1) as file_path
                    FROM {$table} a
                    INNER JOIN {$metaTable} m ON a.id = m.rollback_id
                    WHERE m.meta_key = 'archive_action' AND m.meta_value = %s
                    AND a.status = 'success' AND a.type = %s
                    ORDER BY a.created_at DESC
                    LIMIT %d OFFSET %d",
                    'backup',
                    $type,
                    $perPage,
                    $offset
                );
            } else {
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names cannot be prepared
                $query = $wpdb->prepare(
                    "SELECT DISTINCT
                        a.id,
                        a.type,
                        a.name,
                        a.slug,
                        a.version_to as version,
                        a.updated_by,
                        a.created_at,
                        (SELECT meta_value FROM {$metaTable} WHERE rollback_id = a.id AND meta_key = 'archive_file_size' LIMIT 1) as file_size,
                        (SELECT meta_value FROM {$metaTable} WHERE rollback_id = a.id AND meta_key = 'archive_file_path' LIMIT 1) as file_path
                    FROM {$table} a
                    INNER JOIN {$metaTable} m ON a.id = m.rollback_id
                    WHERE m.meta_key = 'archive_action' AND m.meta_value = %s
                    AND a.status = 'success'
                    ORDER BY a.created_at DESC
                    LIMIT %d OFFSET %d",
                    'backup',
                    $perPage,
                    $offset
                );
            }
            
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- Custom table query, $query is prepared above
            $archives = $wpdb->get_results($query, ARRAY_A);
            
            // Process archives to add user data and format
            $processedArchives = array_map(function($archive) {
                // Get user data
                $user = get_userdata((int) $archive['updated_by']);
                
                // Format file size
                $fileSize = (int) ($archive['file_size'] ?? 0);
                
                // Generate download URL if file exists
                $downloadUrl = null;
                if (!empty($archive['file_path']) && file_exists($archive['file_path'])) {
                    $downloadUrl = rest_url($this->namespace . '/' . $this->rest_base . '/' . $archive['id'] . '/download');
                }
                
                return [
                    'id' => (int) $archive['id'],
                    'type' => $archive['type'],
                    'name' => $archive['name'],
                    'slug' => $archive['slug'],
                    'version' => $archive['version'],
                    'user' => [
                        'id' => (int) $archive['updated_by'],
                        'name' => $user ? $user->display_name : __('Unknown', 'wp-rollback'),
                        'avatar_url' => get_avatar_url((int) $archive['updated_by'], ['size' => 48]),
                    ],
                    'created_at' => $archive['created_at'],
                    'file_size' => $fileSize,
                    'file_size_formatted' => size_format($fileSize),
                    'file_exists' => !empty($archive['file_path']) && file_exists($archive['file_path']),
                    'download_url' => $downloadUrl,
                    'asset_name' => $archive['slug'], // Add asset_name for the download filename
                ];
            }, $archives);
            
            return new WP_REST_Response([
                'items' => $processedArchives,
                'total' => $total,
                'pages' => ceil($total / $perPage),
            ], 200);
            
        } catch (\Exception $e) {
            return new WP_Error(
                'fetch_error',
                __('Failed to fetch archives.', 'wp-rollback'),
                ['status' => 500]
            );
        }
    }

    /**
     * Check permissions for downloading archive
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error
     */
    public function downloadArchivePermissionsCheck($request)
    {
        // For download endpoints, we'll use a different authentication method
        // Check if user is logged in and has proper capabilities
        if (!is_user_logged_in()) {
            return new WP_Error(
                'rest_forbidden',
                __('You must be logged in to download archives.', 'wp-rollback'),
                ['status' => rest_authorization_required_code()]
            );
        }
        
        if (!current_user_can('update_plugins') && !current_user_can('update_themes')) {
            return new WP_Error(
                'rest_forbidden',
                __('You do not have permission to download archives.', 'wp-rollback'),
                ['status' => rest_authorization_required_code()]
            );
        }

        return true;
    }
    
    /**
     * Download archive file
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object
     * @return void|WP_Error
     */
    public function downloadArchive($request)
    {
        $id = (int) $request['id'];
        
        // Get archive details from database
        global $wpdb;
        $metaTable = $wpdb->prefix . 'rollback_activity_meta';
        $activityTable = $wpdb->prefix . 'rollback_activity_log';
        
        // Get file path
        $filePath = $wpdb->get_var($wpdb->prepare(
            "SELECT meta_value FROM {$metaTable} WHERE rollback_id = %d AND meta_key = 'archive_file_path' LIMIT 1",
            $id
        ));
        
        if (!$filePath) {
            return new WP_Error(
                'no_file_path',
                __('No file path found for this archive.', 'wp-rollback'),
                ['status' => 404]
            );
        }
        
        // Check if file exists and is readable
        if (!file_exists($filePath)) {
            return new WP_Error(
                'file_not_found',
                sprintf(
                    /* translators: %s: file path */
                    __('Archive file not found at path: %s', 'wp-rollback'),
                    $filePath
                ),
                ['status' => 404]
            );
        }
        
        if (!is_readable($filePath)) {
            return new WP_Error(
                'file_not_readable',
                __('Archive file exists but is not readable.', 'wp-rollback'),
                ['status' => 403]
            );
        }
        
        // Get asset info for filename
        $assetInfo = $wpdb->get_row($wpdb->prepare(
            "SELECT slug, version_to, type FROM {$activityTable} WHERE id = %d",
            $id
        ), ARRAY_A);
        
        if (!$assetInfo) {
            return new WP_Error(
                'archive_not_found',
                __('Archive record not found.', 'wp-rollback'),
                ['status' => 404]
            );
        }
        
        // Clean any output buffers to prevent corruption
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Disable compression to prevent double compression
        if (ini_get('zlib.output_compression')) {
            ini_set('zlib.output_compression', 'Off');
        }
        
        // Set headers for download
        $filename = $assetInfo['slug'] . '-' . $assetInfo['version_to'] . '.zip';
        $fileSize = filesize($filePath);
        
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . $fileSize);
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Output file in chunks for better memory usage
        $chunkSize = 8192; // 8KB chunks
        $handle = fopen($filePath, 'rb');
        
        if ($handle === false) {
            return new WP_Error(
                'file_open_failed',
                __('Failed to open archive file.', 'wp-rollback'),
                ['status' => 500]
            );
        }
        
        while (!feof($handle)) {
            echo fread($handle, $chunkSize);
            flush();
        }
        
        fclose($handle);
        exit;
    }

    /**
     * Check permissions for deleting archive
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error
     */
    public function deleteArchivePermissionsCheck($request)
    {
        if (!current_user_can('update_plugins') && !current_user_can('update_themes')) {
            return new WP_Error(
                'rest_forbidden',
                __('You do not have permission to delete archives.', 'wp-rollback'),
                ['status' => rest_authorization_required_code()]
            );
        }

        return true;
    }
    
    /**
     * Delete archive file and database entries
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function deleteArchive($request)
    {
        $id = (int) $request['id'];
        
        global $wpdb;
        $metaTable = $wpdb->prefix . 'rollback_activity_meta';
        $activityTable = $wpdb->prefix . 'rollback_activity_log';
        
        // Get file path before deletion
        $filePath = $wpdb->get_var($wpdb->prepare(
            "SELECT meta_value FROM {$metaTable} WHERE rollback_id = %d AND meta_key = 'archive_file_path' LIMIT 1",
            $id
        ));
        
        // Verify the archive exists
        $archiveExists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$activityTable} WHERE id = %d",
            $id
        ));
        
        if (!$archiveExists) {
            return new WP_Error(
                'archive_not_found',
                __('Archive not found.', 'wp-rollback'),
                ['status' => 404]
            );
        }
        
        // Delete the physical file if it exists
        if ($filePath && file_exists($filePath)) {
            if (!unlink($filePath)) {
                return new WP_Error(
                    'file_delete_failed',
                    __('Failed to delete archive file.', 'wp-rollback'),
                    ['status' => 500]
                );
            }
        }
        
        // Delete meta entries
        $wpdb->delete($metaTable, ['rollback_id' => $id], ['%d']);
        
        // Delete activity log entry
        $deleted = $wpdb->delete($activityTable, ['id' => $id], ['%d']);
        
        if (false === $deleted) {
            return new WP_Error(
                'database_delete_failed',
                __('Failed to delete archive from database.', 'wp-rollback'),
                ['status' => 500]
            );
        }
        
        return new WP_REST_Response([
            'success' => true,
            'message' => __('Archive deleted successfully.', 'wp-rollback'),
        ], 200);
    }

    /**
     * Get collection parameters
     *
     * @since 1.0.0
     * @return array
     */
    public function getCollectionParams(): array
    {
        return [
            'page' => [
                'description' => __('Current page of the collection.', 'wp-rollback'),
                'type' => 'integer',
                'default' => 1,
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
                'minimum' => 1,
            ],
            'per_page' => [
                'description' => __('Maximum number of items to be returned in result set.', 'wp-rollback'),
                'type' => 'integer',
                'default' => 10,
                'minimum' => 1,
                'maximum' => 100,
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'type' => [
                'description' => __('Filter by asset type.', 'wp-rollback'),
                'type' => 'string',
                'enum' => ['plugin', 'theme'],
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ],
        ];
    }
}