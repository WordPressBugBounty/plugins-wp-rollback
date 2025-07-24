<?php

/**
 * @package WpRollback\SharedCore\Rollbacks
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Rollbacks;

/**
 * Base class for rollback functionality.
 *
 * @since 1.0.0
 */
abstract class RollbackBase
{
    /**
     * The asset type.
     *
     * @since 1.0.0
     * @var string
     */
    protected string $type = '';

    /**
     * The asset slug.
     *
     * @since 1.0.0
     * @var string
     */
    protected string $slug = '';

    /**
     * The version to rollback to.
     *
     * @since 1.0.0
     * @var string
     */
    protected string $version = '';

    /**
     * Set up the rollback.
     *
     * @since 1.0.0
     *
     * @param string $type    The asset type.
     * @param string $slug    The asset slug.
     * @param string $version The version to rollback to.
     * @return self
     */
    public function setup(string $type, string $slug, string $version): self
    {
        $this->type    = $type;
        $this->slug    = $slug;
        $this->version = $version;

        return $this;
    }

    /**
     * Execute the rollback process.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    abstract public function execute(): bool;
} 