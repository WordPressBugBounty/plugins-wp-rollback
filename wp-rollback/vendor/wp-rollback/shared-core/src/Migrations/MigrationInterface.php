<?php

/**
 * Migration Interface
 *
 * @package WpRollback\SharedCore\Migrations
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Migrations;

/**
 * Interface MigrationInterface
 */
interface MigrationInterface
{
    /**
     * Get unique migration identifier (e.g. 'secure_backup_directory').
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Get migration timestamp for chronological sorting (Unix timestamp or YYYYMMDDHHIISS integer).
     *
     * @return int
     */
    public function getTimestamp(): int;

    /**
     * Get user-friendly translatable label.
     *
     * @return string
     */
    public function getLabel(): string;

    /**
     * Get human-readable description of what this migration does.
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Check if this migration can be safely reverted.
     *
     * @return bool
     */
    public function canRevert(): bool;

    /**
     * Check if this migration should run on fresh installations (e.g. table creation).
     *
     * @return bool
     */
    public function runsOnFreshInstall(): bool;

    /**
     * Run the migration logic.
     *
     * @return bool True if migration succeeded.
     * @throws \Throwable If migration fails.
     */
    public function run(): bool;

    /**
     * Revert the migration logic.
     *
     * @return bool True if revert succeeded.
     * @throws \Throwable If revert fails.
     */
    public function revert(): bool;
}
