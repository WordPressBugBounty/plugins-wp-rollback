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
 * @since 1.0.0
 */
class ContainerHelper
{
    /**
     * Get the container instance.
     *
     * @since 1.0.0
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
     * @since 1.0.0
     *
     * @param string $constantsClass The Constants class to get
     * @return ConstantsInterface
     */
    public static function getConstants(string $constantsClass): ConstantsInterface
    {
        return self::container()->make($constantsClass);
    }
} 