<?php

/**
 * Shared ToolsPage implementation for rendering the React admin interface.
 *
 * @package WpRollback\SharedCore\Rollbacks\ToolsPage
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Rollbacks\ToolsPage;

/**
 * Renders the React root element for both free and pro plugins.
 *
 */
class ToolsPage
{
    /**
     * Render the React root element.
     *
     */
    public function render(): void
    {
        echo '<div id="root-wp-rollback-admin"></div>';
    }
} 