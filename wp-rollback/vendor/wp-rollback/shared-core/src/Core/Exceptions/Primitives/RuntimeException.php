<?php

/**
 * RuntimeException
 *
 * This class is responsible for handling runtime exceptions
 *
 * @package WpRollback\SharedCore\Core\Exceptions\Primitives
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Core\Exceptions\Primitives;

use WpRollback\SharedCore\Core\Contracts\LoggableException;
use WpRollback\SharedCore\Core\Exceptions\Traits\Loggable;

/**
 * Class RuntimeException
 *
 */
class RuntimeException extends \RuntimeException implements LoggableException
{
    use Loggable;
} 