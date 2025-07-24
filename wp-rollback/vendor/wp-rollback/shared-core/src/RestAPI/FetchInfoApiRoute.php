<?php

/**
 * Fetch Info API Route
 *
 * This file is responsible for fetching plugin or theme information from both WordPress.org 
 * and local premium assets.
 * 
 * @package WpRollback\SharedCore\RestAPI
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\RestAPI;

use WP_REST_Request;
use WP_REST_Response;
use WpRollback\SharedCore\Core\Contracts\ApiRouteV1;
use WpRollback\SharedCore\Core\Exceptions\Primitives\Exception;
use WpRollback\SharedCore\Rollbacks\DTO\RollbackItemDTO;

/**
 * Class FetchInfoApiRoute
 *
 * @since 1.0.0
 */
class FetchInfoApiRoute extends ApiRouteV1
{
    /**
     * @unreleased
     */
    public function register(): void
    {
        register_rest_route(
            $this->namespace,
            '/fetch-info/',
            [
                'methods' => 'GET',
                'callback' => [$this, 'processRequest'],
                'permission_callback' => [$this, 'permissionValidation'],
                'args' => [
                    'type' => [
                        'required' => true,
                        'type' => 'string',
                        'enum' => ['plugin', 'theme'],
                    ],
                    'slug' => [
                        'required' => true,
                        'type' => 'string',
                    ],
                ],
            ]
        );
    }

    /**
     * @inheritdoc
     * @since 1.0.0
     */
    public function permissionValidation(WP_REST_Request $request)
    {
        return parent::permissionValidation($request) && current_user_can('update_plugins');
    }

    /**
     * Process the API request
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     * @throws Exception
     */
    public function processRequest(WP_REST_Request $request): WP_REST_Response
    {
        $slug = $request->get_param('slug');
        $type = $request->get_param('type');

        // Get current installation data first
        $currentData = $this->getCurrentInstallationData($slug, $type);

        // If we can't get current installation data, return error
        if (empty($currentData) || empty($currentData['name'])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Could not find installed ' . esc_html($type) . '.',
                'code' => 'asset_not_found',
            ], 404);
        }

        // Get repository data
        $data = $this->getRepositoryData($slug, $type);

        try {
            if ($data) {
                // Handle WordPress.org asset
                return $this->handleWpOrgAsset($data, $currentData, $type);
            } else {
                // Handle premium asset
                return $this->handlePremiumAsset($currentData, $type, $slug);
            }
        } catch (Exception $e) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'processing_failed',
            ], 400);
        }
    }

    /**
     * Handle WordPress.org asset data
     *
     * @param object $data Repository data
     * @param array $currentData Current installation data
     * @param string $type Asset type
     * @return WP_REST_Response
     * @throws Exception
     */
    private function handleWpOrgAsset(object $data, array $currentData, string $type): WP_REST_Response
    {
        // For WP.org plugins, merge versions from repository data
        if ('plugin' === $type && isset($data->versions)) {
            $versions = [];
            foreach ($data->versions as $version => $downloadUrl) {
                if ('trunk' === $version || strpos($version, '-RC') !== false) {
                    continue;
                }
                
                // Ensure version number is valid
                if (!preg_match('/^\d+(\.\d+)*$/', $version)) {
                    continue;
                }

                $versions[$version] = [
                    'file' => basename($downloadUrl),
                    'downloadUrl' => $downloadUrl,
                    'released' => null,
                ];
            }
            $currentData['versions'] = $versions;
        }

        // Create DTO from WordPress.org data
        $dto = RollbackItemDTO::fromWpOrg($data, $currentData['current_version'] ?? '');

        return rest_ensure_response([
            'success' => true,
            'data' => $dto->toArray()
        ]);
    }

    /**
     * Handle premium asset data
     *
     * @param array $currentData Current installation data
     * @param string $type Asset type
     * @param string $slug Asset slug
     * @return WP_REST_Response
     * @throws Exception
     */
    private function handlePremiumAsset(array $currentData, string $type, string $slug): WP_REST_Response
    {
        // Get available versions from backup directory
        $versions = $this->getAvailableVersions($slug);

        // Add current version if not in versions array
        $currentVersion = $currentData['current_version'];
        if (!isset($versions[$currentVersion])) {
            $versions[$currentVersion] = [
                'file' => $currentData['plugin'] ?? $currentData['stylesheet'] ?? '',
                'downloadUrl' => '',
                'released' => null,
            ];
        }

        // Add versions to current data
        $currentData['versions'] = $versions;
        $currentData['isPro'] = true;

        // Create DTO from premium data
        $dto = RollbackItemDTO::fromPremium($currentData, $currentVersion, $slug);
        
        return rest_ensure_response([
            'success' => true,
            'data' => $dto->toArray()
        ]);
    }

    /**
     * Get available versions from backup directory.
     *
     * @param string $slug Asset slug
     * @return array<string, array{file: string, downloadUrl: string, released: ?int}>
     */
    private function getAvailableVersions(string $slug): array
    {
        $uploadDir = wp_upload_dir();
        $rollbackDir = trailingslashit($uploadDir['basedir']) . 'wp-rollback';
        
        // Try both patterns: {slug}-*.zip and just {slug}*.zip
        $pattern1 = sprintf('%s/%s-*.zip', $rollbackDir, $slug);
        $pattern2 = sprintf('%s/%s*.zip', $rollbackDir, $slug);
        
        $files = array_merge(
            glob($pattern1) ?: [],
            glob($pattern2) ?: []
        );
        
        $versions = [];
        foreach ($files as $file) {
            // Match version number at the end of filename before .zip
            if (preg_match('/-?([0-9.]+)\.zip$/', $file, $matches)) {
                $version = $matches[1];
                // Ensure version number is valid
                if (!preg_match('/^\d+(\.\d+)*$/', $version)) {
                    continue;
                }
                $versions[$version] = [
                    'file' => basename($file),
                    'downloadUrl' => '', // Premium plugins/themes don't have public download URLs
                    'released' => null,
                ];
            }
        }

        return $versions;
    }

    /**
     * Get current installation data
     *
     * @param string $slug
     * @param string $type
     * @return array
     */
    private function getCurrentInstallationData(string $slug, string $type): array
    {
        $data = [];

        if ('plugin' === $type) {
            if (!function_exists('get_plugins')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }

            $allPlugins = get_plugins();
            foreach ($allPlugins as $file => $pluginData) {
                // Check if this is the plugin we're looking for
                $pluginSlug = dirname((string) $file);
                if ($pluginSlug === $slug) {
                    $data = [
                        'plugin' => $file,
                        'current_version' => $pluginData['Version'],
                        'name' => $pluginData['Name'],
                        'author' => wp_strip_all_tags($pluginData['Author']),
                        'description' => $pluginData['Description'],
                        'type' => 'plugin',
                        'slug' => $slug,
                        'versions' => [], // Will be populated later with WP.org or premium versions
                        'pluginURI' => $pluginData['PluginURI'] ?? null,
                        'authorURI' => $pluginData['AuthorURI'] ?? null,
                        'requiresWP' => $pluginData['RequiresWP'] ?? null,
                        'requiresPHP' => $pluginData['RequiresPHP'] ?? null,
                    ];
                    break;
                }
            }
        } else {
            $theme = wp_get_theme($slug);
            if ($theme->exists()) {
                $data = [
                    'current_version' => $theme->get('Version'),
                    'stylesheet' => $theme->get_stylesheet(),
                    'name' => $theme->get('Name'),
                    'author' => wp_strip_all_tags($theme->get('Author')),
                    'description' => $theme->get('Description'),
                    'type' => 'theme',
                    'slug' => $slug,
                    'versions' => [], // Will be populated later with WP.org or premium versions
                    'themeURI' => $theme->get('ThemeURI'),
                    'authorURI' => $theme->get('AuthorURI'),
                    'requiresWP' => $theme->get('RequiresWP'),
                    'requiresPHP' => $theme->get('RequiresPHP'),
                ];
            }
        }

        return $data;
    }

    /**
     * Fetch data from WordPress.org repository
     *
     * @param string $slug
     * @param string $type
     *
     * @return object|false
     * @throws Exception
     */
    private function getRepositoryData(string $slug, string $type)
    {
        $wpOrgApiURL = 'theme' === $type
            ? 'https://api.wordpress.org/themes/info/1.2'
            : 'https://api.wordpress.org/plugins/info/1.2';

        $url = add_query_arg(
            [
                'action' => "{$type}_information",
                'request[slug]' => $slug,
                'request[fields][versions]' => '1'
            ],
            $wpOrgApiURL
        );

        $response = wp_remote_get($url);
        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);

        // WordPress.org returns either false or an error object when plugin/theme is not found
        if (empty($data) || isset($data->error)) {
            return false;
        }

        return $data;
    }
} 