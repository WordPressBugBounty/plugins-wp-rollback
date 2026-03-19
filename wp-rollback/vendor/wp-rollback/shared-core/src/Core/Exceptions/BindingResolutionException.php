<?php

/**
 * BindingResolutionException
 *
 * This class is responsible for handling binding resolution exceptions.
 *
 * @package WpRollback\SharedCore\Core\Exceptions
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Core\Exceptions;

use Exception;
use WpRollback\SharedCore\Core\Contracts\LoggableException;
use WpRollback\SharedCore\Core\Exceptions\Traits\Loggable;

/**
 * Class BindingResolutionException.
 *
 */
class BindingResolutionException extends Exception implements LoggableException
{
    use Loggable;
} 