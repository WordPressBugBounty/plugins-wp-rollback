<?php

/**
 * Base class for registering admin menus.
 *
 * @package WpRollback\SharedCore\Rollbacks\Actions
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Rollbacks\Actions;

use WpRollback\SharedCore\Core\BaseConstants;
use WpRollback\SharedCore\Core\Exceptions\BindingResolutionException;
use WpRollback\SharedCore\Core\SharedCore;
use WpRollback\SharedCore\Rollbacks\ToolsPage\ToolsPage;
use WpRollback\SharedCore\Rollbacks\Traits\PluginHelpers;

/**
 * Base class for registering admin menus in both free and pro versions
 *
 * @since 1.0.0
 */
abstract class BaseRegisterAdminMenu
{
    use PluginHelpers;
    /**
     * Get the menu title to be displayed in the admin menu
     *
     * @since 1.0.0
     * @return string The menu title
     */
    abstract protected function getMenuTitle(): string;

    /**
     * Get the page title to be displayed in the browser title
     *
     * @since 1.0.0
     * @return string The page title
     */
    abstract protected function getPageTitle(): string;

    /**
     * Get the capability required to access the page
     *
     * @since 1.0.0
     * @return string The capability
     */
    protected function getCapability(): string
    {
        return 'update_plugins';
    }

    /**
     * Get the menu slug for the admin menu
     *
     * @since 1.0.0
     * @return string The menu slug
     */
    protected function getMenuSlug(): string
    {
        return 'wp-rollback';
    }

    /**
     * Register the admin menu item
     *
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function __invoke(): void
    {
        // Skip individual site menu registration if plugin is network activated
        if ($this->isNetworkActivated()) {
            return;
        }

        $toolsPage = SharedCore::container()->make(ToolsPage::class);

        add_management_page(
            $this->getPageTitle(),
            $this->getMenuTitle(),
            $this->getCapability(),
            $this->getMenuSlug(),
            [$toolsPage, 'render']
        );
    }

    /**
     * Register menu in multisite context
     * 
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function registerMultisiteMenu(): void
    {
        $toolsPage = SharedCore::container()->make(ToolsPage::class);
        
        add_submenu_page(
            'settings.php',
            $this->getPageTitle(),
            $this->getMenuTitle(),
            $this->getCapability(),
            $this->getMenuSlug(),
            [$toolsPage, 'render']
        );
    }
} 