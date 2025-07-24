<?php

/**
 * BindingResolutionException
 *
 * This class is responsible for handling binding resolution exceptions.
 *
 * @package WpRollback\SharedCore\Core\Exceptions
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Core\Exceptions;

use Exception;
use WpRollback\SharedCore\Core\Contracts\LoggableException;
use WpRollback\SharedCore\Core\Exceptions\Traits\Loggable;

/**
 * Class BindingResolutionException.
 *
 * @since 1.0.0
 */
class BindingResolutionException extends Exception implements LoggableException
{
    use Loggable;
} 