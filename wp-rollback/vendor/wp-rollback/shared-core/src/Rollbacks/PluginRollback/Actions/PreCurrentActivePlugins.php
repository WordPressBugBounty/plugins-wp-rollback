<?php

/**
 * @package WpRollback\SharedCore\Rollbacks\PluginRollback\Actions
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Rollbacks\PluginRollback\Actions;

/**
 * Adds rollback flag to active plugins
 * 
 * @since 1.0.0
 */
class PreCurrentActivePlugins
{
    /**
     * Add a rollback flag to all plugins in the list
     * 
     * @since 1.0.0
     * @param array $plugins Active plugins list
     * @return array Modified plugins list with rollback flag
     */
    public function __invoke(array $plugins): array
    {
        $updated = $plugins;
        foreach ($updated as $key => $value) {
            $updated[$key] = $value;
            $updated[$key]['rollback'] = true;
        }

        return $updated;
    }
} 