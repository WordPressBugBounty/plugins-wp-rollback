<?php

/**
 * REST API Service Provider
 *
 * @package WpRollback\SharedCore\RestAPI
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\RestAPI;

use WpRollback\SharedCore\Core\Hooks;
use WpRollback\SharedCore\Core\SharedCore;
use WpRollback\SharedCore\Core\Contracts\ServiceProvider as ServiceProviderContract;
use WpRollback\SharedCore\Rollbacks\Registry\RollbackStepRegisterer;

/**
 * Service Provider for REST API components.
 *
 * @since 1.0.0
 */
class ServiceProvider implements ServiceProviderContract
{
    /**
     * Register services.
     *
     * @inheritdoc
     * @since 1.0.0
     */
    public function register(): void
    {
        // Register factories for classes that need specific dependencies
        Hooks::registerFactory(ProcessRollbackApiRoute::class, function () {
            // Use the shared RollbackStepRegisterer instance from the container
            $stepRegisterer = SharedCore::container()->make(RollbackStepRegisterer::class);
            return new ProcessRollbackApiRoute($stepRegisterer);
        });
        
        // Register factory for the rollback steps API route
        Hooks::registerFactory(RollbackStepsApiRoute::class, function () {
            $stepRegisterer = SharedCore::container()->make(RollbackStepRegisterer::class);
            return new RollbackStepsApiRoute($stepRegisterer);
        });
    }

    /**
     * Bootstrap services.
     *
     * @inheritdoc
     * @since 1.0.0
     */
    public function boot(): void
    {
        Hooks::addAction('rest_api_init', FetchInfoApiRoute::class, 'register');
        Hooks::addAction('rest_api_init', ProcessRollbackApiRoute::class, 'register');
        Hooks::addAction('rest_api_init', RollbackStepsApiRoute::class, 'register');
    }
} 