<?php

/**
 * Secure Backup Directory Migration Task
 *
 * Migration created on: 2026-09-09 03:58:12 UTC
 *
 * @package WpRollback\SharedCore\Migrations\Tasks
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Migrations\Tasks;

use WP_Filesystem_Base;
use WpRollback\SharedCore\Core\SharedCore;
use WpRollback\SharedCore\Migrations\AbstractMigration;
use WpRollback\SharedCore\PluginSetup\PluginManager;
use WpRollback\SharedCore\Rollbacks\Services\BackupService;

/**
 * Class SecureBackupDirectory
 *
 * Migrates existing backups from predictable uploads/wp-rollback/ directory
 * to a site-unique hashed directory (uploads/wp-rollback-{hash}/) and ensures
 * proper access protection files are in place.
 */
class SecureBackupDirectory extends AbstractMigration
{
    /**
     * @var string Option name for directory hash
     */
    public const HASH_OPTION_NAME = PluginManager::HASH_OPTION_NAME;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->id                 = 'secure_backup_directory';
        $this->timestamp          = 1788926292; // 2026-09-09 03:58:12 UTC
        $this->canRevert = true;

        // Released versions never stored their version number, so an upgrade
        // from them looks like a fresh install. Run anyway: this task only moves
        // files when the old folder exists, so it's a no-op on a real fresh install.
        $this->runsOnFreshInstall = true;
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): string
    {
        return 'Secure Backup Storage Directory';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Migrates existing backups from public uploads/wp-rollback to a secure hashed directory and applies multi-layer access restrictions.';
    }

    /**
     * @inheritDoc
     */
    public function run(): bool
    {
        /** @var BackupService $backupService */
        $backupService = SharedCore::container()->make(BackupService::class);
        $filesystem    = $backupService->getFilesystem();

        $oldDir = $this->getLegacyDirectory();
        $newDir = $backupService->getRollbackDirectory();

        if (!$filesystem->is_dir($newDir) && !wp_mkdir_p($newDir)) {
            throw new \RuntimeException(sprintf('Failed to create new secure rollback directory: %s', $newDir));
        }

        // Move archives one by one. The new folder already exists (created above,
        // or by an earlier backup), so the old folder can't simply be renamed.
        if ($filesystem->is_dir($oldDir)) {
            $this->moveArchives($filesystem, $oldDir, $newDir);
            $this->deleteIfEmpty($filesystem, $oldDir);
        }

        // Rewrite the protection files so folders created by earlier builds get the current rules.
        $backupService->writeProtectionFiles($newDir);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function revert(): bool
    {
        $hash = get_option(self::HASH_OPTION_NAME);

        if (empty($hash) || !is_string($hash)) {
            return true;
        }

        /** @var BackupService $backupService */
        $backupService = SharedCore::container()->make(BackupService::class);
        $filesystem    = $backupService->getFilesystem();

        $oldDir = $this->getLegacyDirectory();
        $newDir = trailingslashit(dirname($oldDir)) . 'wp-rollback-' . $hash;

        if ($filesystem->is_dir($newDir)) {
            if (!$filesystem->is_dir($oldDir) && !wp_mkdir_p($oldDir)) {
                throw new \RuntimeException(sprintf('Failed to recreate legacy rollback directory: %s', $oldDir));
            }

            $backupService->writeProtectionFiles($oldDir);
            $this->moveArchives($filesystem, $newDir, $oldDir);
            $this->deleteIfEmpty($filesystem, $newDir);
        }

        // Only forget the hash once nothing is left in the hashed folder, so its archives stay findable.
        if (!$filesystem->is_dir($newDir)) {
            delete_option(self::HASH_OPTION_NAME);
        }

        return true;
    }

    /**
     * Path of the pre-3.2.0 backup folder (uploads/wp-rollback).
     *
     * @return string
     */
    private function getLegacyDirectory(): string
    {
        $uploadDir = wp_upload_dir();

        return trailingslashit($uploadDir['basedir']) . 'wp-rollback';
    }

    /**
     * Move every archive from one backup folder into another.
     *
     * Protection files are left in place. When the target already has an archive
     * with the same name (same slug and version), the source copy is a duplicate
     * and is deleted.
     *
     * @param WP_Filesystem_Base $filesystem WordPress filesystem instance
     * @param string             $from       Folder to move archives out of
     * @param string             $to         Folder to move archives into
     * @return void
     * @throws \RuntimeException If an archive can't be moved; nothing is deleted in that case.
     */
    private function moveArchives(WP_Filesystem_Base $filesystem, string $from, string $to): void
    {
        $from    = trailingslashit($from);
        $to      = trailingslashit($to);
        $entries = $filesystem->dirlist($from);

        if (!is_array($entries)) {
            return;
        }

        foreach (array_keys($entries) as $name) {
            $name = (string) $name;

            if (in_array($name, BackupService::PROTECTION_FILES, true)) {
                continue;
            }

            if ($filesystem->exists($to . $name)) {
                $filesystem->delete($from . $name, true);
                continue;
            }

            if (!$filesystem->move($from . $name, $to . $name)) {
                throw new \RuntimeException(sprintf('Failed to move %s to %s.', $from . $name, $to . $name));
            }
        }
    }

    /**
     * Delete a backup folder if only its protection files are left.
     *
     * @param WP_Filesystem_Base $filesystem WordPress filesystem instance
     * @param string             $dir        Folder to delete
     * @return void
     */
    private function deleteIfEmpty(WP_Filesystem_Base $filesystem, string $dir): void
    {
        $entries = $filesystem->dirlist($dir);

        if (!is_array($entries)) {
            return;
        }

        $remaining = array_diff(array_map('strval', array_keys($entries)), BackupService::PROTECTION_FILES);

        if ($remaining === []) {
            $filesystem->delete($dir, true);
        }
    }
}
