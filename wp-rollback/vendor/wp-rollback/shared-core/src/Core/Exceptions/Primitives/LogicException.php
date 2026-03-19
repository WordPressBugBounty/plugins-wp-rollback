<?php

/**
 * LogicException
 *
 * This class is responsible for handling logical exceptions
 *
 * @package WpRollback\SharedCore\Core\Exceptions\Primitives
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Core\Exceptions\Primitives;

use WpRollback\SharedCore\Core\Contracts\LoggableException;
use WpRollback\SharedCore\Core\Exceptions\Traits\Loggable;

/**
 * Class LogicException
 *
 */
class LogicException extends \LogicException implements LoggableException
{
    use Loggable;
} 