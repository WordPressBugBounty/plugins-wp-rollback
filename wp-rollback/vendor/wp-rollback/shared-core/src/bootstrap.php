<?php

/**
 * Bootstrap file for Shared Core
 *
 * This file is responsible for bootstrapping the shared core library.
 * It should be included once in the plugin's main file.
 *
 * @package WpRollback\SharedCore
 * @since 1.0.0
 */

declare(strict_types=1);

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// If the Core is already bootstrapped, return early
if (defined('WPROLLBACK_SHARED_CORE_BOOTSTRAPPED')) {
    return;
}

// Mark as bootstrapped
define('WPROLLBACK_SHARED_CORE_BOOTSTRAPPED', true);

// Include the SharedCore class and dependencies
require_once __DIR__ . '/Core/Container/ContainerInterface.php';
require_once __DIR__ . '/Core/Container/Container.php';
require_once __DIR__ . '/Core/Container/ContainerSingleton.php';
require_once __DIR__ . '/Core/SharedCore.php';

// Include PluginSetup base class
require_once __DIR__ . '/PluginSetup/PluginSetup.php';

// Initialize the autoloader if running from the monorepo
if (defined('WP_ROLLBACK_IS_DEV') && WP_ROLLBACK_IS_DEV) {
    $sharedCorePath = dirname(__DIR__);
    if (file_exists($sharedCorePath . '/vendor/autoload.php')) {
        require_once $sharedCorePath . '/vendor/autoload.php';
    }
} 