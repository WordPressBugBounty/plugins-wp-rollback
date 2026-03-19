<?php

/**
 * @package WpRollback\SharedCore\Core\Traits
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Core\Traits;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Trait HandleMultipleApiRoutes
 *
 *
 * @method string getNamespace()
 * @method string getEndpoint(string $route)
 * @method array getRoutes()
 */
trait HandleMultipleApiRoutes
{
    /**
     * Register REST routes.
     *
     */
    public function registerAllRoutes(): void
    {
        $routes = $this->getRoutes();

        foreach ($routes as $route => $details) {
            register_rest_route(
                $this->getNamespace(),
                $this->getEndpoint($route),
                [
                    'methods' => $details['method'],
                    'callback' => empty($details['callback']) ? [ $this, 'processRequest'] : [$this, $details['callback']] ,
                    'permission_callback' => [$this, 'permissionCheck'],
                    'args' => $details['args'] ?? [],
                ]
            );
        }
    }

    /**
     *
     * @return WP_Error|WP_REST_Response
     */
    public function processRequest(WP_REST_Request $request)
    {
        $routes = $this->getRoutes();

        $route = $request->get_route();
        $routeParts = explode('/', $route);
        $routeName = end($routeParts);

        if (! array_key_exists($routeName, $routes)) {
            return new WP_Error(
                'route_not_found',
                esc_html__('Route not found.', 'wp-rollback')
            );
        }

        $callbackName = 'get' . ucfirst($routeName);

        return empty($this->getRoutes()[$routeName]['callback'])
            ? call_user_func([$this, $callbackName], $request)
            : call_user_func([$this, $this->getRoutes()[$routeName]['callback']], $request);
    }
} 