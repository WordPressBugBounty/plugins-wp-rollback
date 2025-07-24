<?php

/**
 * Controller Contract.
 *
 * This file provides the abstract base class for all controllers in the application.
 * Controllers handle incoming requests and return appropriate responses, typically
 * used for REST API endpoints, AJAX handlers, or general request processing.
 *
 * All controller classes should extend this abstract class to ensure consistent
 * request handling across the application.
 *
 * @package WpRollback\SharedCore\Core\Contracts
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Core\Contracts;

use WpRollback\SharedCore\Core\Request;

/**
 * Class Controller.
 *
 * Abstract base class for all controllers in the application.
 * Provides common functionality for request handling and data processing.
 *
 * Each controller must be instantiated with a Request object, which will be
 * automatically provided by the Hooks class when used with WordPress hooks.
 *
 * @since 1.0.0
 */
abstract class Controller
{
    /**
     * The request object instance.
     *
     * Contains the current HTTP request information and provides
     * methods for accessing and sanitizing request data.
     *
     * @since 1.0.0
     * @var Request The request object.
     */
    protected Request $request;

    /**
     * Class constructor.
     *
     * Initializes a new controller with the given request instance.
     * This constructor requires a Request object to be passed.
     *
     * @since 1.0.0
     * @param Request $request The request object containing HTTP request data.
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Get sanitized data from the request.
     *
     * This method retrieves all request data and sanitizes it
     * using the sanitize method from the Request class.
     *
     * @since 1.0.0
     * @return array|null The sanitized request data or null if no data is available.
     */
    protected function getRequestData(): ?array
    {
        return $this->request->sanitize($this->request->all());
    }
} 