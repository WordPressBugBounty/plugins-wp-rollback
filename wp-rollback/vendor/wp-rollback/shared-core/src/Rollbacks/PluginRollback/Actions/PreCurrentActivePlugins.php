<?php

/**
 * @package WpRollback\SharedCore\Rollbacks\PluginRollback\Actions
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Rollbacks\PluginRollback\Actions;

/**
 * Adds rollback flag to active plugins
 * 
 */
class PreCurrentActivePlugins
{
    /**
     * Add a rollback flag to all plugins in the list
     * 
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