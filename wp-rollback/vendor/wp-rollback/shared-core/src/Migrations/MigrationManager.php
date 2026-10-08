<?php

/**
 * Migration Manager
 *
 * Discovers, registers, executes, reverts and tracks migrations.
 *
 * @package WpRollback\SharedCore\Migrations
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Migrations;

use WpRollback\SharedCore\Core\SharedCore;

/**
 * Class MigrationManager
 */
class MigrationManager
{
    /**
     * @var MigrationRepository Migration repository instance
     */
    private MigrationRepository $_repository;

    /**
     * @var array<int, class-string<MigrationInterface>|MigrationInterface> Registered migration class names or instances
     */
    private array $_migrations = [];

    /**
     * @var array<string, MigrationInterface> Cache of resolved instances indexed by class name
     */
    private array $_resolvedInstances = [];

    /**
     * Constructor.
     *
     * @param MigrationRepository $repository Migration repository
     */
    public function __construct(MigrationRepository $repository)
    {
        $this->_repository = $repository;
    }

    /**
     * Register a migration by class name or instance.
     *
     * @param class-string<MigrationInterface>|MigrationInterface $migration Class name or migration instance
     * @return void
     */
    public function registerMigration($migration): void
    {
        if (!empty($migration)) {
            $this->_migrations[] = $migration;
        }
    }

    /**
     * Register multiple migrations.
     *
     * @param array<int|string, class-string<MigrationInterface>|MigrationInterface> $migrations Array of migration class names or instances
     * @return void
     */
    public function registerMigrations(array $migrations): void
    {
        foreach ($migrations as $migration) {
            $this->registerMigration($migration);
        }
    }

    /**
     * Get all registered migrations, resolved to instances and sorted by timestamp ascending.
     *
     * @return array<string, MigrationInterface> Array of migration instances indexed by migration ID
     */
    public function getRegisteredMigrations(): array
    {
        $resolved = [];

        foreach ($this->_migrations as $item) {
            $instance                      = $this->resolveMigration($item);
            $resolved[$instance->getId()] = $instance;
        }

        uasort($resolved, function (MigrationInterface $a, MigrationInterface $b) {
            return $a->getTimestamp() <=> $b->getTimestamp();
        });

        return $resolved;
    }

    /**
     * Run all pending migrations in chronological timestamp order.
     *
     * @return void
     */
    public function runPendingMigrations(): void
    {
        $sortedMigrations = $this->getRegisteredMigrations();

        foreach ($sortedMigrations as $id => $migration) {
            $log = $this->_repository->getMigrationLog($id);

            // Skip if completed
            if ($this->_repository->isCompleted($id)) {
                continue;
            }

            // Skip if previously failed to avoid repeating errors on every request.
            // Requires explicit retry via retryMigration().
            if ($log && $log->isFailed()) {
                continue;
            }

            // Skip if an admin reverted it. Running it again here would undo the
            // revert on the next admin page load. Requires an explicit runMigration().
            if ($log && $log->isReverted()) {
                continue;
            }

            $this->executeMigration($migration);
        }
    }

    /**
     * Autocomplete pending migrations on fresh install except those marked to run on fresh install (runsOnFreshInstall = true).
     *
     * @return void
     */
    public function autocompleteMigrationsForFreshInstall(): void
    {
        $sortedMigrations = $this->getRegisteredMigrations();

        foreach ($sortedMigrations as $id => $migration) {
            // Skip if already completed
            if ($this->_repository->isCompleted($id)) {
                continue;
            }

            // Skip migrations marked to run on fresh install (e.g. table creation) so they stay pending and execute normally
            if ($migration->runsOnFreshInstall()) {
                continue;
            }

            $this->_repository->recordStart(
                $id,
                $migration->getTimestamp(),
                $migration->getLabel(),
                $migration->getDescription()
            );
            $this->_repository->recordSuccess($id);
        }
    }

    /**
     * Execute a specific migration by ID.
     *
     * @param string $id Migration ID
     * @return bool True if migration succeeded, false otherwise
     */
    public function runMigration(string $id): bool
    {
        $migrations = $this->getRegisteredMigrations();

        if (!isset($migrations[$id])) {
            return false;
        }

        return $this->executeMigration($migrations[$id]);
    }

    /**
     * Revert a specific migration by ID.
     *
     * @param string $id Migration ID
     * @return bool True if revert succeeded, false otherwise
     */
    public function revertMigration(string $id): bool
    {
        $migrations = $this->getRegisteredMigrations();

        if (!isset($migrations[$id])) {
            return false;
        }

        $migration = $migrations[$id];

        if (!$migration->canRevert()) {
            return false;
        }

        try {
            $result = $migration->revert();

            if ($result) {
                $this->_repository->recordRevertSuccess($id);
                return true;
            } else {
                throw new \RuntimeException(sprintf('Revert for migration %s returned false.', $id));
            }
        } catch (\Throwable $e) {
            $this->_repository->recordRevertFailure($id, $e);

            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log(sprintf('[WP Rollback Migration Revert Error] %s: %s', $id, $e->getMessage()));
            }

            return false;
        }
    }

    /**
     * Retry a specific failed migration.
     *
     * @param string $id Migration ID
     * @return bool True if migration succeeded on retry, false otherwise
     */
    public function retryMigration(string $id): bool
    {
        $migrations = $this->getRegisteredMigrations();

        if (!isset($migrations[$id])) {
            return false;
        }

        $this->_repository->clearLog($id);
        return $this->executeMigration($migrations[$id]);
    }

    /**
     * Resolve a migration class string or return an instance.
     *
     * @param class-string<MigrationInterface>|MigrationInterface $migration Class string or instance
     * @return MigrationInterface
     */
    private function resolveMigration($migration): MigrationInterface
    {
        if ($migration instanceof MigrationInterface) {
            return $migration;
        }

        if (is_string($migration) && class_exists($migration)) {
            if (isset($this->_resolvedInstances[$migration])) {
                return $this->_resolvedInstances[$migration];
            }

            if (class_exists(SharedCore::class) && SharedCore::container()->has($migration)) {
                /** @var MigrationInterface $instance */
                $instance = SharedCore::container()->make($migration);
            } else {
                /** @var MigrationInterface $instance */
                $instance = new $migration();
            }

            if ($instance instanceof MigrationInterface) {
                $this->_resolvedInstances[$migration] = $instance;
                return $instance;
            }
        }

        throw new \InvalidArgumentException(
            sprintf('Invalid migration registered: %s', $migration)
        );
    }

    /**
     * Get summary status of all registered migrations, sorted by timestamp.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getMigrationsStatus(): array
    {
        $statusList = [];
        $sortedMigrations = $this->getRegisteredMigrations();

        foreach ($sortedMigrations as $id => $migration) {
            $log = $this->_repository->getMigrationLog($id);

            $statusList[$id] = [
                'id'          => $id,
                'timestamp'   => $migration->getTimestamp(),
                'label'       => $migration->getLabel(),
                'description' => $migration->getDescription(),
                'can_revert'  => $migration->canRevert(),
                'status'      => $log ? $log->getStatus() : MigrationRepository::STATUS_PENDING,
                'started_at'  => $log ? $log->getStartedAt() : null,
                'completed_at'=> $log ? $log->getCompletedAt() : null,
                'reverted_at' => $log ? $log->getRevertedAt() : null,
                'attempts'    => $log ? $log->getAttempts() : 0,
                'error'       => $log ? $log->getError() : null,
            ];
        }

        return $statusList;
    }

    /**
     * Execute a single migration with error logging and status tracking.
     *
     * @param MigrationInterface $migration Migration instance
     * @return bool
     */
    private function executeMigration(MigrationInterface $migration): bool
    {
        $id = $migration->getId();

        try {
            $this->_repository->recordStart(
                $id,
                $migration->getTimestamp(),
                $migration->getLabel(),
                $migration->getDescription()
            );

            $result = $migration->run();

            if ($result) {
                $this->_repository->recordSuccess($id);
                return true;
            } else {
                throw new \RuntimeException(sprintf('Migration %s returned false.', $id));
            }
        } catch (\Throwable $e) {
            $this->_repository->recordFailure($id, $e);

            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log(sprintf('[WP Rollback Migration Error] %s: %s', $id, $e->getMessage()));
            }

            return false;
        }
    }
}
