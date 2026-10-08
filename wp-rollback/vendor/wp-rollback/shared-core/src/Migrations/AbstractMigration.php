<?php

/**
 * Abstract Migration Base Class
 *
 * @package WpRollback\SharedCore\Migrations
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Migrations;

/**
 * Class AbstractMigration
 */
abstract class AbstractMigration implements MigrationInterface
{
    /**
     * @var string Unique migration identifier
     */
    protected string $id = '';

    /**
     * @var int Unix timestamp or YYYYMMDDHHIISS integer for chronological sorting
     */
    protected int $timestamp = 0;

    /**
     * @var string User-friendly translatable label
     */
    protected string $label = '';

    /**
     * @var string Detailed description
     */
    protected string $description = '';

    /**
     * @var bool Whether this migration can be reverted
     */
    protected bool $canRevert = true;

    /**
     * @var bool Whether this migration should run on fresh installations (e.g. table creation)
     */
    protected bool $runsOnFreshInstall = false;

    /**
     * @inheritDoc
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @inheritDoc
     */
    public function canRevert(): bool
    {
        return $this->canRevert;
    }

    /**
     * @inheritDoc
     */
    public function runsOnFreshInstall(): bool
    {
        return $this->runsOnFreshInstall;
    }

    /**
     * @inheritDoc
     */
    public function revert(): bool
    {
        return false;
    }
}
