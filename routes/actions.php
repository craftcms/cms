<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
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
use CraftCms\Cms\Http\Controllers\Settings\GeneralSettingsController;
use CraftCms\Cms\Http\Controllers\Settings\RoutesController;
use CraftCms\Cms\Http\Controllers\Settings\SectionsController;
use CraftCms\Cms\Http\Controllers\Settings\SiteGroupsController;
use CraftCms\Cms\Http\Controllers\Settings\SitesController;
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
use CraftCms\Cms\Http\Middleware\RequireElevatedSession;
use CraftCms\Cms\Http\Middleware\RequireToken;
use CraftCms\Cms\Support\Str;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

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
\Illuminate\Support\Facades\Route::prefix(Cms::config()->actionTrigger)->group(function () {
    \Illuminate\Support\Facades\Route::post('migrate', MigrateController::class);

    \Illuminate\Support\Facades\Route::middleware(['auth'])->group(function () {
        \Illuminate\Support\Facades\Route::post('entries/save-entry', StoreEntryController::class);
    });

    \Illuminate\Support\Facades\Route::middleware([RequireToken::class])->group(function () {
        \Illuminate\Support\Facades\Route::any('preview/preview', [PreviewController::class, 'preview'])->name('preview');
        \Illuminate\Support\Facades\Route::any('users/impersonate-with-token', [ImpersonationController::class, 'withToken']);
    });
});

\Illuminate\Support\Facades\Route::prefix(implode('/', [
    Cms::config()->cpTrigger,
    Cms::config()->actionTrigger,
]))->middleware(['craft.cp'])->group(function () {
    /**
     * Actions not needing auth
     */
    \Illuminate\Support\Facades\Route::post('install/validate-db', [InstallController::class, 'validateDb']);
    \Illuminate\Support\Facades\Route::post('install/validate-account', [InstallController::class, 'validateAccount']);
    \Illuminate\Support\Facades\Route::post('install/validate-site', [InstallController::class, 'validateSite']);
    \Illuminate\Support\Facades\Route::post('install/install', [InstallController::class, 'install']);

    \Illuminate\Support\Facades\Route::any('app/api-headers', [ApiController::class, 'headers']);
    \Illuminate\Support\Facades\Route::any('app/process-api-response-headers', [ApiController::class, 'processResponseHeaders']);
    \Illuminate\Support\Facades\Route::any('app/get-utilities-badge-count', [UtilitiesController::class, 'badgeCount']);

    // Updater
    \Illuminate\Support\Facades\Route::prefix('updater')->group(function () {
        \Illuminate\Support\Facades\Route::post('/', [UpdaterController::class, 'index']);
        \Illuminate\Support\Facades\Route::post(UpdaterController::ACTION_FORCE_UPDATE, [UpdaterController::class, 'forceUpdate']);
        \Illuminate\Support\Facades\Route::post(UpdaterController::ACTION_BACKUP, [UpdaterController::class, 'backup']);
        \Illuminate\Support\Facades\Route::post(UpdaterController::ACTION_SERVER_CHECK, [UpdaterController::class, 'serverCheck']);
        \Illuminate\Support\Facades\Route::post(UpdaterController::ACTION_REVERT, [UpdaterController::class, 'revert']);
        \Illuminate\Support\Facades\Route::post(UpdaterController::ACTION_MIGRATE, [UpdaterController::class, 'migrate']);
        \Illuminate\Support\Facades\Route::post(BaseUpdaterController::ACTION_PRECHECK, [UpdaterController::class, 'precheck']);
        \Illuminate\Support\Facades\Route::post(BaseUpdaterController::ACTION_RECHECK_COMPOSER, [UpdaterController::class, 'recheckComposer']);
        \Illuminate\Support\Facades\Route::post(BaseUpdaterController::ACTION_COMPOSER_INSTALL, [UpdaterController::class, 'composerInstall']);
        \Illuminate\Support\Facades\Route::post(BaseUpdaterController::ACTION_COMPOSER_REMOVE, [UpdaterController::class, 'composerRemove']);
        \Illuminate\Support\Facades\Route::post(BaseUpdaterController::ACTION_FINISH, [UpdaterController::class, 'finish']);
    });

    /**
     * Actions needing auth
     */
    \Illuminate\Support\Facades\Route::middleware(['auth'])->group(function () {
        // Addresses
        \Illuminate\Support\Facades\Route::post('addresses/fields', [AddressesController::class, 'fields']);
        \Illuminate\Support\Facades\Route::middleware(RequireAdminChanges::class)->post('addresses/save-field-layout', [AddressesController::class, 'saveFieldLayout']);

        // DeprecationErrors
        \Illuminate\Support\Facades\Route::post('utilities/get-deprecation-error-traces-modal', [DeprecationErrorsController::class, 'getDeprecationErrorTracesModal']);
        \Illuminate\Support\Facades\Route::post('utilities/delete-deprecation-error', [DeprecationErrorsController::class, 'deleteDeprecationError']);
        \Illuminate\Support\Facades\Route::post('utilities/delete-all-deprecation-errors', [DeprecationErrorsController::class, 'deleteAllDeprecationErrors']);

        // ClearCaches
        \Illuminate\Support\Facades\Route::post('utilities/clear-caches-perform-action', [ClearCachesController::class, 'clearCaches']);
        \Illuminate\Support\Facades\Route::post('utilities/invalidate-tags', [ClearCachesController::class, 'invalidateTags']);

        // DbBackup
        \Illuminate\Support\Facades\Route::post('utilities/db-backup-perform-action', DbBackupController::class);

        // Entries
        \Illuminate\Support\Facades\Route::post('entries/create', CreateEntryController::class);
        \Illuminate\Support\Facades\Route::post('entries/save-entry', StoreEntryController::class);
        \Illuminate\Support\Facades\Route::post('entries/move-to-section-modal-data', [MoveEntryToSectionController::class, 'showModal']);
        \Illuminate\Support\Facades\Route::post('entries/move-to-section', [MoveEntryToSectionController::class, 'move']);

        // Entry Types
        \Illuminate\Support\Facades\Route::get('entry-types/table-data', [EntryTypesController::class, 'tableData']);
        \Illuminate\Support\Facades\Route::get('entry-types/edit/{entryTypeId?}', [EntryTypesController::class, 'edit']);
        \Illuminate\Support\Facades\Route::middleware([
            RequireAdminChanges::class,
        ])->group(function () {
            \Illuminate\Support\Facades\Route::post('entry-types/save', [EntryTypesController::class, 'store']);
            \Illuminate\Support\Facades\Route::post('entry-types/delete', [EntryTypesController::class, 'destroy']);
            \Illuminate\Support\Facades\Route::post('entry-types/render-override-settings', [EntryTypesController::class, 'renderOverrideSettings']);
            \Illuminate\Support\Facades\Route::post('entry-types/apply-override-settings', [EntryTypesController::class, 'applyOverrideSettings']);
        });

        // Fields
        \Illuminate\Support\Facades\Route::middleware([RequireAdminChanges::class])->group(function () {
            \Illuminate\Support\Facades\Route::get('fields/edit-field', [FieldsController::class, 'edit']);
            \Illuminate\Support\Facades\Route::post('fields/render-settings', [FieldsController::class, 'renderSettings']);
            \Illuminate\Support\Facades\Route::post('fields/save-field', [FieldsController::class, 'store']);
            \Illuminate\Support\Facades\Route::post('fields/delete-field', [FieldsController::class, 'destroy']);
            \Illuminate\Support\Facades\Route::post('fields/render-layout-component-settings', [FieldsController::class, 'renderLayoutComponentSettings']);
            \Illuminate\Support\Facades\Route::post('fields/apply-layout-tab-settings', [FieldsController::class, 'applyLayoutTabSettings']);
            \Illuminate\Support\Facades\Route::post('fields/apply-layout-element-settings', [FieldsController::class, 'applyLayoutElementSettings']);
            \Illuminate\Support\Facades\Route::post('fields/render-card-preview', [FieldsController::class, 'renderCardPreview']);
        });
        \Illuminate\Support\Facades\Route::middleware([RequireAdmin::class])->group(function () {
            \Illuminate\Support\Facades\Route::get('fields/table-data', [FieldsController::class, 'tableData']);
        });

        // FindAndReplace
        \Illuminate\Support\Facades\Route::post('utilities/find-and-replace-perform-action', FindAndReplaceController::class);

        // Migrations
        \Illuminate\Support\Facades\Route::post('utilities/apply-new-migrations', MigrationsController::class);

        // Preview
        \Illuminate\Support\Facades\Route::any('preview/create-token', [PreviewController::class, 'createToken']);

        // Widgets
        \Illuminate\Support\Facades\Route::post('dashboard/create-widget', [WidgetsController::class, 'store']);
        \Illuminate\Support\Facades\Route::post('dashboard/save-widget-settings', [WidgetsController::class, 'update']);
        \Illuminate\Support\Facades\Route::post('dashboard/delete-user-widget', [WidgetsController::class, 'delete']);
        \Illuminate\Support\Facades\Route::post('dashboard/change-widget-colspan', [WidgetsController::class, 'updateColspan']);
        \Illuminate\Support\Facades\Route::post('dashboard/reorder-user-widgets', [WidgetsController::class, 'reorder']);
        \Illuminate\Support\Facades\Route::post('dashboard/cache-feed-data', [FeedController::class, 'cacheData']);
        \Illuminate\Support\Facades\Route::post('dashboard/send-support-request', CraftSupportController::class);

        // Filesystems
        \Illuminate\Support\Facades\Route::middleware([RequireAdminChanges::class])->group(function () {
            \Illuminate\Support\Facades\Route::get('fs/edit', [FilesystemsController::class, 'edit']);
            \Illuminate\Support\Facades\Route::post('fs/save', [FilesystemsController::class, 'save']);
            \Illuminate\Support\Facades\Route::post('fs/remove', [FilesystemsController::class, 'delete']);
        });

        // Plugins
        \Illuminate\Support\Facades\Route::middleware([RequireAdminChanges::class])->group(function () {
            \Illuminate\Support\Facades\Route::post('plugins/install-plugin', [PluginsController::class, 'install']);
            \Illuminate\Support\Facades\Route::post('plugins/uninstall-plugin', [PluginsController::class, 'uninstall']);
            \Illuminate\Support\Facades\Route::post('plugins/switch-edition', [PluginsController::class, 'switchEdition']);
            \Illuminate\Support\Facades\Route::post('plugins/disable-plugin', [PluginsController::class, 'disable']);
            \Illuminate\Support\Facades\Route::post('plugins/enable-plugin', [PluginsController::class, 'enable']);

            \Illuminate\Support\Facades\Route::post('plugins/save-plugin-settings', [PluginsController::class, 'saveSettings']);
        });

        // Project config utility
        \Illuminate\Support\Facades\Route::post('project-config/rebuild', [ProjectConfigController::class, 'rebuild']);
        \Illuminate\Support\Facades\Route::get('project-config/diff', [ProjectConfigController::class, 'diff']);
        \Illuminate\Support\Facades\Route::post('project-config/discard', [ProjectConfigController::class, 'discard']);
        \Illuminate\Support\Facades\Route::get('project-config/download', [ProjectConfigController::class, 'download']);

        // Project Config sync
        \Illuminate\Support\Facades\Route::prefix('config-sync')->group(function () {
            \Illuminate\Support\Facades\Route::post('/', [ConfigSyncController::class, 'index']);
            \Illuminate\Support\Facades\Route::post(ConfigSyncController::ACTION_RETRY, [ConfigSyncController::class, 'retry']);
            \Illuminate\Support\Facades\Route::post(ConfigSyncController::ACTION_APPLY_YAML_CHANGES, [ConfigSyncController::class, 'applyYamlChanges']);
            \Illuminate\Support\Facades\Route::post(ConfigSyncController::ACTION_REGENERATE_YAML, [ConfigSyncController::class, 'regenerateYaml']);
            \Illuminate\Support\Facades\Route::post(ConfigSyncController::ACTION_UNINSTALL_PLUGIN, [ConfigSyncController::class, 'uninstallPlugin']);
            \Illuminate\Support\Facades\Route::post(ConfigSyncController::ACTION_INSTALL_PLUGIN, [ConfigSyncController::class, 'installPlugin']);
            \Illuminate\Support\Facades\Route::post(BaseUpdaterController::ACTION_PRECHECK, [ConfigSyncController::class, 'precheck']);
            \Illuminate\Support\Facades\Route::post(BaseUpdaterController::ACTION_RECHECK_COMPOSER, [ConfigSyncController::class, 'recheckComposer']);
            \Illuminate\Support\Facades\Route::post(BaseUpdaterController::ACTION_COMPOSER_INSTALL, [ConfigSyncController::class, 'composerInstall']);
            \Illuminate\Support\Facades\Route::post(BaseUpdaterController::ACTION_COMPOSER_REMOVE, [ConfigSyncController::class, 'composerRemove']);
            \Illuminate\Support\Facades\Route::post(BaseUpdaterController::ACTION_FINISH, [ConfigSyncController::class, 'finish']);
        });

        // Routes
        \Illuminate\Support\Facades\Route::middleware([RequireAdminChanges::class])->group(function () {
            \Illuminate\Support\Facades\Route::post('routes/save-route', [RoutesController::class, 'store']);
            \Illuminate\Support\Facades\Route::post('routes/delete-route', [RoutesController::class, 'destroy']);
            \Illuminate\Support\Facades\Route::post('routes/update-route-order', [RoutesController::class, 'reorder']);
        });

        // Sections
        \Illuminate\Support\Facades\Route::get('sections/table-data', [SectionsController::class, 'tableData']);
        \Illuminate\Support\Facades\Route::get('sections/edit/{sectionId?}', [SectionsController::class, 'edit']);
        \Illuminate\Support\Facades\Route::middleware([
            RequireAdminChanges::class,
        ])->group(function () {
            \Illuminate\Support\Facades\Route::post('sections/save-section', [SectionsController::class, 'store']);
            \Illuminate\Support\Facades\Route::post('sections/delete-section', [SectionsController::class, 'destroy']);
        });

        // Sites & Site Groups
        \Illuminate\Support\Facades\Route::middleware([
            RequireAdminChanges::class,
        ])->group(function () {
            \Illuminate\Support\Facades\Route::post('sites/rename-group-field', [SiteGroupsController::class, 'showGroupRenameField']);
            \Illuminate\Support\Facades\Route::post('sites/save-group', [SiteGroupsController::class, 'store']);
            \Illuminate\Support\Facades\Route::post('sites/delete-group', [SiteGroupsController::class, 'destroy']);
            \Illuminate\Support\Facades\Route::post('sites/save-site', [SitesController::class, 'store']);
            \Illuminate\Support\Facades\Route::post('sites/reorder-sites', [SitesController::class, 'reorder']);
            \Illuminate\Support\Facades\Route::post('sites/delete-site', [SitesController::class, 'destroy']);
        });

        // Structures
        \Illuminate\Support\Facades\Route::post('structures/get-element-level-delta', [StructuresController::class, 'getElementLevelDelta']);
        \Illuminate\Support\Facades\Route::post('structures/move-element', [StructuresController::class, 'moveElement']);

        // SystemMessages
        \Illuminate\Support\Facades\Route::post('system-messages/get-message-modal', [SystemMessagesController::class, 'show']);
        \Illuminate\Support\Facades\Route::post('system-messages/save-message', [SystemMessagesController::class, 'store']);

        // System settings
        \Illuminate\Support\Facades\Route::middleware([
            RequireAdminChanges::class,
        ])->group(function () {
            \Illuminate\Support\Facades\Route::post('system-settings/save-general-settings', [GeneralSettingsController::class, 'store']);
        });

        // Updates
        \Illuminate\Support\Facades\Route::post('app/check-for-updates', [UpdatesController::class, 'check']);
        \Illuminate\Support\Facades\Route::post('app/cache-updates', [UpdatesController::class, 'cache']);

        // Users
        \Illuminate\Support\Facades\Route::middleware([RequireElevatedSession::class])->group(function () {
            \Illuminate\Support\Facades\Route::post('users/impersonate', [ImpersonationController::class, 'impersonate']);
            \Illuminate\Support\Facades\Route::post('users/get-impersonation-url', [ImpersonationController::class, 'getUrl']);
        });

        // Pluginstore
        \Illuminate\Support\Facades\Route::middleware([
            RequireAdmin::class,
        ])->group(function () {
            \Illuminate\Support\Facades\Route::get('plugin-store/craft-data', [PluginStoreController::class, 'craftData']);
            \Illuminate\Support\Facades\Route::post('plugin-store/save-plugin-license-keys', [PluginStoreController::class, 'savePluginLicenseKeys']);
        });

        \Illuminate\Support\Facades\Route::prefix('pluginstore/install')->middleware([
            RequireAdminChanges::class,
        ])->group(function () {
            \Illuminate\Support\Facades\Route::post('/', [PluginStoreInstallController::class, 'index']);
            \Illuminate\Support\Facades\Route::post(PluginStoreInstallController::ACTION_CRAFT_INSTALL, [PluginStoreInstallController::class, 'craftInstall']);
            \Illuminate\Support\Facades\Route::post(PluginStoreInstallController::ACTION_ENABLE, [PluginStoreInstallController::class, 'enable']);
            \Illuminate\Support\Facades\Route::post(PluginStoreInstallController::ACTION_MIGRATE, [PluginStoreInstallController::class, 'migrate']);
            \Illuminate\Support\Facades\Route::post(BaseUpdaterController::ACTION_PRECHECK, [PluginStoreInstallController::class, 'precheck']);
            \Illuminate\Support\Facades\Route::post(BaseUpdaterController::ACTION_RECHECK_COMPOSER, [PluginStoreInstallController::class, 'recheckComposer']);
            \Illuminate\Support\Facades\Route::post(BaseUpdaterController::ACTION_COMPOSER_INSTALL, [PluginStoreInstallController::class, 'composerInstall']);
            \Illuminate\Support\Facades\Route::post(BaseUpdaterController::ACTION_COMPOSER_REMOVE, [PluginStoreInstallController::class, 'composerRemove']);
            \Illuminate\Support\Facades\Route::post(BaseUpdaterController::ACTION_FINISH, [PluginStoreInstallController::class, 'finish']);
        });

        \Illuminate\Support\Facades\Route::prefix('pluginstore/remove')->middleware([
            RequireAdminChanges::class,
        ])->group(function () {
            \Illuminate\Support\Facades\Route::post('/', [RemoveController::class, 'index']);
            \Illuminate\Support\Facades\Route::post(BaseUpdaterController::ACTION_PRECHECK, [RemoveController::class, 'precheck']);
            \Illuminate\Support\Facades\Route::post(BaseUpdaterController::ACTION_RECHECK_COMPOSER, [RemoveController::class, 'recheckComposer']);
            \Illuminate\Support\Facades\Route::post(BaseUpdaterController::ACTION_COMPOSER_INSTALL, [RemoveController::class, 'composerInstall']);
            \Illuminate\Support\Facades\Route::post(BaseUpdaterController::ACTION_COMPOSER_REMOVE, [RemoveController::class, 'composerRemove']);
            \Illuminate\Support\Facades\Route::post(BaseUpdaterController::ACTION_FINISH, [RemoveController::class, 'finish']);
        });
    });
});
