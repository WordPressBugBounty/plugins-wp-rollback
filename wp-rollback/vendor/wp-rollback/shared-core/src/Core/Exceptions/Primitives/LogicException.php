<?php

/**
 * LogicException
 *
 * This class is responsible for handling logical exceptions
 *
 * @package WpRollback\SharedCore\Core\Exceptions\Primitives
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Core\Exceptions\Primitives;

use WpRollback\SharedCore\Core\Contracts\LoggableException;
use WpRollback\SharedCore\Core\Exceptions\Traits\Loggable;

/**
 * Class LogicException
 *
 * @since 1.0.0
 */
class LogicException extends \LogicException implements LoggableException
{
    use Loggable;
} 