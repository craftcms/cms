<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Controllers\AddressesController;
use CraftCms\Cms\Http\Controllers\ApiController;
use CraftCms\Cms\Http\Controllers\BaseUpdaterController;
use CraftCms\Cms\Http\Controllers\ConfigSyncController;
use CraftCms\Cms\Http\Controllers\Dashboard\Widgets\CraftSupportController;
use CraftCms\Cms\Http\Controllers\Dashboard\Widgets\FeedController;
use CraftCms\Cms\Http\Controllers\Dashboard\WidgetsController;
use CraftCms\Cms\Http\Controllers\Entries\CreateEntryController;
use CraftCms\Cms\Http\Controllers\Entries\MoveEntryToSectionController;
use CraftCms\Cms\Http\Controllers\Entries\StoreEntryController;
use CraftCms\Cms\Http\Controllers\FieldsController;
use CraftCms\Cms\Http\Controllers\FilesystemsController;
use CraftCms\Cms\Http\Controllers\InstallController;
use CraftCms\Cms\Http\Controllers\MigrateController;
use CraftCms\Cms\Http\Controllers\PluginsController;
use CraftCms\Cms\Http\Controllers\PluginStore\InstallController as PluginStoreInstallController;
use CraftCms\Cms\Http\Controllers\PluginStore\PluginStoreController;
use CraftCms\Cms\Http\Controllers\PluginStore\RemoveController;
use CraftCms\Cms\Http\Controllers\PreviewController;
use CraftCms\Cms\Http\Controllers\Settings\EntryTypesController;
use CraftCms\Cms\Http\Controllers\Settings\RoutesController;
use CraftCms\Cms\Http\Controllers\Settings\SectionsController;
use CraftCms\Cms\Http\Controllers\Settings\SiteGroupsController;
use CraftCms\Cms\Http\Controllers\Settings\SitesController;
use CraftCms\Cms\Http\Controllers\Settings\UserGroupsController;
use CraftCms\Cms\Http\Controllers\Settings\UserSettingsController;
use CraftCms\Cms\Http\Controllers\StructuresController;
use CraftCms\Cms\Http\Controllers\Updates\UpdaterController;
use CraftCms\Cms\Http\Controllers\Updates\UpdatesController;
use CraftCms\Cms\Http\Controllers\Users\ImpersonationController;
use CraftCms\Cms\Http\Controllers\Utilities\ClearCachesController;
use CraftCms\Cms\Http\Controllers\Utilities\DbBackupController;
use CraftCms\Cms\Http\Controllers\Utilities\DeprecationErrorsController;
use CraftCms\Cms\Http\Controllers\Utilities\FindAndReplaceController;
use CraftCms\Cms\Http\Controllers\Utilities\MigrationsController;
use CraftCms\Cms\Http\Controllers\Utilities\ProjectConfigController;
use CraftCms\Cms\Http\Controllers\Utilities\SystemMessagesController;
use CraftCms\Cms\Http\Controllers\Utilities\UtilitiesController;
use CraftCms\Cms\Http\Middleware\RequireAdmin;
use CraftCms\Cms\Http\Middleware\RequireAdminChanges;
use CraftCms\Cms\Http\Middleware\RequireEdition;
use CraftCms\Cms\Http\Middleware\RequireElevatedSession;
use CraftCms\Cms\Http\Middleware\RequireToken;
use CraftCms\Cms\Support\Str;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

/**
 * Actions that should not have CSRF token verification. These are automatically
 * mapped to `/{cpTrigger}/{actionTrigger}/route` and `/{actionTrigger}/{route}`
 */
VerifyCsrfToken::except(collect([
    'preview/preview',
])->flatMap(fn (string $route) => [
    $route,
    Cms::config()->actionTrigger.Str::start($route, '/'),
    Cms::config()->cpTrigger.'/'.Cms::config()->actionTrigger.Str::start($route, '/'),
])->all());

/**
 * Actions that are accessible without CP can be registered here.
 */
Route::prefix(Cms::config()->actionTrigger)->group(function () {
    Route::post('migrate', MigrateController::class);

    Route::middleware(['auth'])->group(function () {
        Route::post('entries/save-entry', StoreEntryController::class);
    });

    Route::middleware([RequireToken::class])->group(function () {
        Route::any('preview/preview', [PreviewController::class, 'preview'])->name('preview');
        Route::any('users/impersonate-with-token', [ImpersonationController::class, 'withToken']);
    });
});

Route::prefix(implode('/', [
    Cms::config()->cpTrigger,
    Cms::config()->actionTrigger,
]))->middleware(['craft.cp'])->group(function () {
    /**
     * Actions not needing auth
     */
    Route::post('install/validate-db', [InstallController::class, 'validateDb']);
    Route::post('install/validate-account', [InstallController::class, 'validateAccount']);
    Route::post('install/validate-site', [InstallController::class, 'validateSite']);
    Route::post('install/install', [InstallController::class, 'install']);

    Route::any('app/api-headers', [ApiController::class, 'headers']);
    Route::any('app/process-api-response-headers', [ApiController::class, 'processResponseHeaders']);
    Route::any('app/get-utilities-badge-count', [UtilitiesController::class, 'badgeCount']);

    // Updater
    Route::prefix('updater')->group(function () {
        Route::post('/', [UpdaterController::class, 'index']);
        Route::post(UpdaterController::ACTION_FORCE_UPDATE, [UpdaterController::class, 'forceUpdate']);
        Route::post(UpdaterController::ACTION_BACKUP, [UpdaterController::class, 'backup']);
        Route::post(UpdaterController::ACTION_SERVER_CHECK, [UpdaterController::class, 'serverCheck']);
        Route::post(UpdaterController::ACTION_REVERT, [UpdaterController::class, 'revert']);
        Route::post(UpdaterController::ACTION_MIGRATE, [UpdaterController::class, 'migrate']);
        Route::post(BaseUpdaterController::ACTION_PRECHECK, [UpdaterController::class, 'precheck']);
        Route::post(BaseUpdaterController::ACTION_RECHECK_COMPOSER, [UpdaterController::class, 'recheckComposer']);
        Route::post(BaseUpdaterController::ACTION_COMPOSER_INSTALL, [UpdaterController::class, 'composerInstall']);
        Route::post(BaseUpdaterController::ACTION_COMPOSER_REMOVE, [UpdaterController::class, 'composerRemove']);
        Route::post(BaseUpdaterController::ACTION_FINISH, [UpdaterController::class, 'finish']);
    });

    /**
     * Actions needing auth
     */
    Route::middleware(['auth'])->group(function () {
        // Addresses
        Route::post('addresses/fields', [AddressesController::class, 'fields']);
        Route::middleware(RequireAdminChanges::class)->post('addresses/save-field-layout', [AddressesController::class, 'saveFieldLayout']);

        // DeprecationErrors
        Route::post('utilities/get-deprecation-error-traces-modal', [DeprecationErrorsController::class, 'getDeprecationErrorTracesModal']);
        Route::post('utilities/delete-deprecation-error', [DeprecationErrorsController::class, 'deleteDeprecationError']);
        Route::post('utilities/delete-all-deprecation-errors', [DeprecationErrorsController::class, 'deleteAllDeprecationErrors']);

        // ClearCaches
        Route::post('utilities/clear-caches-perform-action', [ClearCachesController::class, 'clearCaches']);
        Route::post('utilities/invalidate-tags', [ClearCachesController::class, 'invalidateTags']);

        // DbBackup
        Route::post('utilities/db-backup-perform-action', DbBackupController::class);

        // Entries
        Route::post('entries/create', CreateEntryController::class);
        Route::post('entries/save-entry', StoreEntryController::class);
        Route::post('entries/move-to-section-modal-data', [MoveEntryToSectionController::class, 'showModal']);
        Route::post('entries/move-to-section', [MoveEntryToSectionController::class, 'move']);

        // Entry Types
        Route::get('entry-types/table-data', [EntryTypesController::class, 'tableData']);
        Route::get('entry-types/edit/{entryType}', [EntryTypesController::class, 'edit']);
        Route::middleware([
            RequireAdminChanges::class,
        ])->group(function () {
            Route::get('entry-types/new', [EntryTypesController::class, 'create']);
            Route::post('entry-types/save', [EntryTypesController::class, 'store']);
            Route::post('entry-types/delete', [EntryTypesController::class, 'destroy']);
            Route::post('entry-types/render-override-settings', [EntryTypesController::class, 'renderOverrideSettings']);
            Route::post('entry-types/apply-override-settings', [EntryTypesController::class, 'applyOverrideSettings']);
        });

        // Fields
        Route::middleware([RequireAdminChanges::class])->group(function () {
            Route::get('fields/edit-field', [FieldsController::class, 'edit']);
            Route::post('fields/render-settings', [FieldsController::class, 'renderSettings']);
            Route::post('fields/save-field', [FieldsController::class, 'store']);
            Route::post('fields/delete-field', [FieldsController::class, 'destroy']);
            Route::post('fields/render-layout-component-settings', [FieldsController::class, 'renderLayoutComponentSettings']);
            Route::post('fields/apply-layout-tab-settings', [FieldsController::class, 'applyLayoutTabSettings']);
            Route::post('fields/apply-layout-element-settings', [FieldsController::class, 'applyLayoutElementSettings']);
            Route::post('fields/render-card-preview', [FieldsController::class, 'renderCardPreview']);
        });
        Route::middleware([RequireAdmin::class])->group(function () {
            Route::get('fields/table-data', [FieldsController::class, 'tableData']);
        });

        // FindAndReplace
        Route::post('utilities/find-and-replace-perform-action', FindAndReplaceController::class);

        // Migrations
        Route::post('utilities/apply-new-migrations', MigrationsController::class);

        // Preview
        Route::any('preview/create-token', [PreviewController::class, 'createToken']);

        // Widgets
        Route::post('dashboard/create-widget', [WidgetsController::class, 'store']);
        Route::post('dashboard/save-widget-settings', [WidgetsController::class, 'update']);
        Route::post('dashboard/delete-user-widget', [WidgetsController::class, 'delete']);
        Route::post('dashboard/change-widget-colspan', [WidgetsController::class, 'updateColspan']);
        Route::post('dashboard/reorder-user-widgets', [WidgetsController::class, 'reorder']);
        Route::post('dashboard/cache-feed-data', [FeedController::class, 'cacheData']);
        Route::post('dashboard/send-support-request', CraftSupportController::class);

        // Filesystems
        Route::middleware([RequireAdminChanges::class])->group(function () {
            Route::get('fs/edit', [FilesystemsController::class, 'edit']);
            Route::post('fs/save', [FilesystemsController::class, 'save']);
            Route::post('fs/remove', [FilesystemsController::class, 'delete']);
        });

        // Plugins
        Route::middleware([RequireAdminChanges::class])->group(function () {
            Route::post('plugins/install-plugin', [PluginsController::class, 'install']);
            Route::post('plugins/uninstall-plugin', [PluginsController::class, 'uninstall']);
            Route::post('plugins/switch-edition', [PluginsController::class, 'switchEdition']);
            Route::post('plugins/disable-plugin', [PluginsController::class, 'disable']);
            Route::post('plugins/enable-plugin', [PluginsController::class, 'enable']);

            Route::post('plugins/save-plugin-settings', [PluginsController::class, 'saveSettings']);
        });

        // Project config utility
        Route::post('project-config/rebuild', [ProjectConfigController::class, 'rebuild']);
        Route::get('project-config/diff', [ProjectConfigController::class, 'diff']);
        Route::post('project-config/discard', [ProjectConfigController::class, 'discard']);
        Route::get('project-config/download', [ProjectConfigController::class, 'download']);

        // Project Config sync
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

        // Routes
        Route::middleware([RequireAdminChanges::class])->group(function () {
            Route::post('routes/save-route', [RoutesController::class, 'store']);
            Route::post('routes/delete-route', [RoutesController::class, 'destroy']);
            Route::post('routes/update-route-order', [RoutesController::class, 'reorder']);
        });

        // Sections
        Route::get('sections/table-data', [SectionsController::class, 'tableData']);
        Route::get('sections/edit/{section}', [SectionsController::class, 'edit']);
        Route::middleware([
            RequireAdminChanges::class,
        ])->group(function () {
            Route::post('sections/save-section', [SectionsController::class, 'store']);
            Route::post('sections/delete-section', [SectionsController::class, 'destroy']);
        });

        // Sites & Site Groups
        Route::middleware([
            RequireAdminChanges::class,
        ])->group(function () {
            Route::post('sites/rename-group-field', [SiteGroupsController::class, 'showGroupRenameField']);
            Route::post('sites/save-group', [SiteGroupsController::class, 'store']);
            Route::post('sites/delete-group', [SiteGroupsController::class, 'destroy']);
            Route::post('sites/save-site', [SitesController::class, 'store']);
            Route::post('sites/reorder-sites', [SitesController::class, 'reorder']);
            Route::post('sites/delete-site', [SitesController::class, 'destroy']);
        });

        // Structures
        Route::post('structures/get-element-level-delta', [StructuresController::class, 'getElementLevelDelta']);
        Route::post('structures/move-element', [StructuresController::class, 'moveElement']);

        // SystemMessages
        Route::post('system-messages/get-message-modal', [SystemMessagesController::class, 'show']);
        Route::post('system-messages/save-message', [SystemMessagesController::class, 'store']);

        // Updates
        Route::post('app/check-for-updates', [UpdatesController::class, 'check']);
        Route::post('app/cache-updates', [UpdatesController::class, 'cache']);

        // Users
        Route::middleware([RequireElevatedSession::class])->group(function () {
            Route::post('users/impersonate', [ImpersonationController::class, 'impersonate']);
            Route::post('users/get-impersonation-url', [ImpersonationController::class, 'getUrl']);
        });

        // User groups
        Route::middleware([
            RequireAdminChanges::class,
            RequireEdition::class.':'.Edition::Team->value,
        ])->group(function () {
            Route::post('user-settings/save-group', [UserGroupsController::class, 'store']);
            Route::post('user-settings/delete-group', [UserGroupsController::class, 'destroy']);
        });

        // User settings
        Route::middleware([
            RequireAdminChanges::class,
        ])->group(function () {
            Route::post('user-settings/save-user-settings', [UserSettingsController::class, 'store']);
        });

        // Pluginstore
        Route::middleware([
            RequireAdmin::class,
        ])->group(function () {
            Route::get('plugin-store/craft-data', [PluginStoreController::class, 'craftData']);
            Route::post('plugin-store/save-plugin-license-keys', [PluginStoreController::class, 'savePluginLicenseKeys']);
        });

        Route::prefix('pluginstore/install')->middleware([
            RequireAdminChanges::class,
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
            RequireAdminChanges::class,
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
