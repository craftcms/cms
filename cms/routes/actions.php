<?php

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Http\Controllers\BaseUpdaterController;
use CraftCms\Cms\Http\Controllers\ConfigSyncController;
use CraftCms\Cms\Http\Controllers\Dashboard\Widgets\CraftSupportController;
use CraftCms\Cms\Http\Controllers\Dashboard\Widgets\FeedController;
use CraftCms\Cms\Http\Controllers\Dashboard\WidgetsController;
use CraftCms\Cms\Http\Controllers\InstallController;
use CraftCms\Cms\Http\Controllers\MigrateController;
use CraftCms\Cms\Http\Controllers\PluginsController;
use CraftCms\Cms\Http\Controllers\PluginStore\InstallController as PluginStoreInstallController;
use CraftCms\Cms\Http\Controllers\PluginStore\PluginStoreController;
use CraftCms\Cms\Http\Controllers\PluginStore\RemoveController;
use CraftCms\Cms\Http\Controllers\Updates\UpdaterController;
use CraftCms\Cms\Http\Controllers\Updates\UpdatesController;
use CraftCms\Cms\Http\Controllers\Utilities\ClearCachesController;
use CraftCms\Cms\Http\Controllers\Utilities\DbBackupController;
use CraftCms\Cms\Http\Controllers\Utilities\DeprecationErrorsController;
use CraftCms\Cms\Http\Controllers\Utilities\FindAndReplaceController;
use CraftCms\Cms\Http\Controllers\Utilities\MigrationsController;
use CraftCms\Cms\Http\Middleware\RequireAdmin;

$generalConfig = app(GeneralConfig::class);

/**
 * Actions that are accessible anonymously can be registered here.
 */
Route::prefix($generalConfig->actionTrigger)->group(function () {
    Route::post('migrate', MigrateController::class);
});

/**
 * Actions that are accessible through the control panel can be registered here.
 */
Route::prefix(implode('/', [
    $generalConfig->cpTrigger,
    $generalConfig->actionTrigger,
]))->middleware(['craft.cp'])->group(function () {
    /**
     * Actions not needing auth
     */
    Route::post('install/validate-db', [InstallController::class, 'validateDb']);
    Route::post('install/validate-account', [InstallController::class, 'validateAccount']);
    Route::post('install/validate-site', [InstallController::class, 'validateSite']);
    Route::post('install/install', [InstallController::class, 'install']);

    /**
     * Actions needing auth
     */
    Route::middleware(['auth'])->group(function () {
        // DeprecationErrors
        Route::post('utilities/get-deprecation-error-traces-modal', [DeprecationErrorsController::class, 'getDeprecationErrorTracesModal']);
        Route::post('utilities/delete-deprecation-error', [DeprecationErrorsController::class, 'deleteDeprecationError']);
        Route::post('utilities/delete-all-deprecation-errors', [DeprecationErrorsController::class, 'deleteAllDeprecationErrors']);

        // ClearCaches
        Route::post('utilities/clear-caches-perform-action', [ClearCachesController::class, 'clearCaches']);
        Route::post('utilities/invalidate-tags', [ClearCachesController::class, 'invalidateTags']);

        // DbBackup
        Route::post('utilities/db-backup-perform-action', DbBackupController::class);

        // FindAndReplace
        Route::post('utilities/find-and-replace-perform-action', FindAndReplaceController::class);

        // Migrations
        Route::post('utilities/apply-new-migrations', MigrationsController::class);

        // Widgets
        Route::post('dashboard/create-widget', [WidgetsController::class, 'store']);
        Route::post('dashboard/save-widget-settings', [WidgetsController::class, 'update']);
        Route::post('dashboard/delete-user-widget', [WidgetsController::class, 'delete']);
        Route::post('dashboard/change-widget-colspan', [WidgetsController::class, 'updateColspan']);
        Route::post('dashboard/reorder-user-widgets', [WidgetsController::class, 'reorder']);
        Route::post('dashboard/cache-feed-data', [FeedController::class, 'cacheData']);
        Route::post('dashboard/send-support-request', CraftSupportController::class);

        // Plugins
        Route::middleware([RequireAdmin::class])->group(function () {
            Route::post('plugins/install-plugin', [PluginsController::class, 'install']);
            Route::post('plugins/uninstall-plugin', [PluginsController::class, 'uninstall']);
            Route::post('plugins/switch-edition', [PluginsController::class, 'switchEdition']);
            Route::post('plugins/disable-plugin', [PluginsController::class, 'disable']);
            Route::post('plugins/enable-plugin', [PluginsController::class, 'enable']);

            Route::post('plugins/save-plugin-settings', [PluginsController::class, 'saveSettings']);
        });

        // Project Config
        Route::prefix('config-sync')->group(function () {
            Route::post('/', [ConfigSyncController::class, 'index']);
            Route::post(ConfigSyncController::ACTION_RETRY, [ConfigSyncController::class, 'retry']);
            Route::post(ConfigSyncController::ACTION_APPLY_YAML_CHANGES, [ConfigSyncController::class, 'applyYamlChanges']);
            Route::post(ConfigSyncController::ACTION_REGENERATE_YAML, [ConfigSyncController::class, 'regenerateYaml']);
            Route::post(ConfigSyncController::ACTION_UNINSTALL_PLUGIN, [ConfigSyncController::class, 'uninstallPlugin']);
            Route::post(ConfigSyncController::ACTION_INSTALL_PLUGIN, [ConfigSyncController::class, 'installPlugin']);
            Route::post(BaseUpdaterController::ACTION_PRECHECK, [ConfigSyncController::class, 'precheck']);
            Route::post(BaseUpdaterController::ACTION_RECHECK_COMPOSER, [ConfigSyncController::class, 'recheckComposer']);
            Route::post(BaseUpdaterController::ACTION_COMPOSER_INSTALL, [ConfigSyncController::class, 'composerInstall']);
            Route::post(BaseUpdaterController::ACTION_COMPOSER_REMOVE, [ConfigSyncController::class, 'composerRemove']);
            Route::post(BaseUpdaterController::ACTION_FINISH, [ConfigSyncController::class, 'finish']);
        });

        // Updates
        Route::post('app/check-for-updates', [UpdatesController::class, 'check']);
        Route::post('app/cache-updates', [UpdatesController::class, 'cache']);

        // Updater
        Route::prefix('updater')->group(function () {
            Route::post('/', [UpdaterController::class, 'index']);
            Route::post(UpdaterController::ACTION_FORCE_UPDATE, [UpdaterController::class, 'forceUpdate']);
            Route::post(UpdaterController::ACTION_BACKUP, [UpdaterController::class, 'backup']);
            Route::post(UpdaterController::ACTION_SERVER_CHECK, [UpdaterController::class, 'serverCheck']);
            Route::post(UpdaterController::ACTION_REVERT, [UpdaterController::class, 'revert']);
            Route::post(UpdaterController::ACTION_MIGRATE, [UpdaterController::class, 'revert']);
            Route::post(BaseUpdaterController::ACTION_PRECHECK, [UpdaterController::class, 'precheck']);
            Route::post(BaseUpdaterController::ACTION_RECHECK_COMPOSER, [UpdaterController::class, 'recheckComposer']);
            Route::post(BaseUpdaterController::ACTION_COMPOSER_INSTALL, [UpdaterController::class, 'composerInstall']);
            Route::post(BaseUpdaterController::ACTION_COMPOSER_REMOVE, [UpdaterController::class, 'composerRemove']);
            Route::post(BaseUpdaterController::ACTION_FINISH, [UpdaterController::class, 'finish']);
        });

        // Pluginstore
        Route::middleware([
            RequireAdmin::class.':false',
        ])->group(function () {
            Route::get('plugin-store/craft-data', [PluginStoreController::class, 'craftData']);
            Route::get('plugin-store/save-plugin-license-keys', [PluginStoreController::class, 'savePluginLicenseKeys']);
        });

        Route::prefix('pluginstore/install')->middleware([
            RequireAdmin::class,
        ])->group(function () {
            Route::post('/', [PluginStoreInstallController::class, 'index']);
            Route::post(PluginStoreInstallController::ACTION_CRAFT_INSTALL, [PluginStoreInstallController::class, 'craftInstall']);
            Route::post(PluginStoreInstallController::ACTION_ENABLE, [PluginStoreInstallController::class, 'enable']);
            Route::post(PluginStoreInstallController::ACTION_MIGRATE, [PluginStoreInstallController::class, 'migrate']);
            Route::post(BaseUpdaterController::ACTION_PRECHECK, [PluginStoreInstallController::class, 'precheck']);
            Route::post(BaseUpdaterController::ACTION_RECHECK_COMPOSER, [PluginStoreInstallController::class, 'recheckComposer']);
            Route::post(BaseUpdaterController::ACTION_COMPOSER_INSTALL, [PluginStoreInstallController::class, 'composerInstall']);
            Route::post(BaseUpdaterController::ACTION_COMPOSER_REMOVE, [PluginStoreInstallController::class, 'composerRemove']);
            Route::post(BaseUpdaterController::ACTION_FINISH, [PluginStoreInstallController::class, 'finish']);
        });

        Route::prefix('pluginstore/remove')->middleware([
            RequireAdmin::class,
        ])->group(function () {
            Route::post('/', [RemoveController::class, 'index']);
            Route::post(BaseUpdaterController::ACTION_PRECHECK, [RemoveController::class, 'precheck']);
            Route::post(BaseUpdaterController::ACTION_RECHECK_COMPOSER, [RemoveController::class, 'recheckComposer']);
            Route::post(BaseUpdaterController::ACTION_COMPOSER_INSTALL, [RemoveController::class, 'composerInstall']);
            Route::post(BaseUpdaterController::ACTION_COMPOSER_REMOVE, [RemoveController::class, 'composerRemove']);
            Route::post(BaseUpdaterController::ACTION_FINISH, [RemoveController::class, 'finish']);
        });
    });
});
