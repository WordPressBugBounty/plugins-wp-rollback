<?php

/**
 * Exception
 *
 * This class is responsible for handling exceptions.
 *
 * @package WpRollback\SharedCore\Core\Exceptions\Primitives
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Core\Exceptions\Primitives;

use WpRollback\SharedCore\Core\Contracts\LoggableException;
use WpRollback\SharedCore\Core\Exceptions\Traits\Loggable;

/**
 * Class Exception
 *
 */
class Exception extends \Exception implements LoggableException
{
    use Loggable;
} 