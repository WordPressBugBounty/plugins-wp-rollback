<?php

declare(strict_types=1);

namespace WpRollback\SharedCore\Core\Helpers;

use WpRollback\SharedCore\Core\ConstantsInterface;
use WpRollback\SharedCore\Core\Container\ContainerInterface;
use WpRollback\SharedCore\Core\SharedCore;

/**
 * Container helper class
 *
 * @package WpRollback\SharedCore\Core\Helpers
 */
class ContainerHelper
{
    /**
     * Get the container instance.
     *
     *
     * @return ContainerInterface
     */
    public static function container(): ContainerInterface
    {
        return SharedCore::container();
    }

    /**
     * Get a Constants instance from the container.
     *
     *
     * @param string $constantsClass The Constants class to get
     * @return ConstantsInterface
     */
    public static function getConstants(string $constantsClass): ConstantsInterface
    {
        return self::container()->make($constantsClass);
    }
} 