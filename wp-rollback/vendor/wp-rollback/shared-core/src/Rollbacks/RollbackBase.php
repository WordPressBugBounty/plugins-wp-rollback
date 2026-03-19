<?php

/**
 * @package WpRollback\SharedCore\Rollbacks
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Rollbacks;

/**
 * Base class for rollback functionality.
 *
 */
abstract class RollbackBase
{
    /**
     * The asset type.
     *
     * @var string
     */
    protected string $type = '';

    /**
     * The asset slug.
     *
     * @var string
     */
    protected string $slug = '';

    /**
     * The version to rollback to.
     *
     * @var string
     */
    protected string $version = '';

    /**
     * Set up the rollback.
     *
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
     *
     * @return bool
     */
    abstract public function execute(): bool;
} 