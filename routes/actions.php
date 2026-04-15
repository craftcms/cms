<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Controllers\AddressesController;
use CraftCms\Cms\Http\Controllers\AnnouncementsController;
use CraftCms\Cms\Http\Controllers\ApiController;
use CraftCms\Cms\Http\Controllers\App\CpAlertsController;
use CraftCms\Cms\Http\Controllers\App\HealthCheckController;
use CraftCms\Cms\Http\Controllers\App\LicensesController;
use CraftCms\Cms\Http\Controllers\App\RenderController;
use CraftCms\Cms\Http\Controllers\Assets\ActionController as AssetsActionController;
use CraftCms\Cms\Http\Controllers\Assets\FolderController as AssetsFolderController;
use CraftCms\Cms\Http\Controllers\Assets\IconController as AssetsIconController;
use CraftCms\Cms\Http\Controllers\Assets\ImageEditorController;
use CraftCms\Cms\Http\Controllers\Assets\PreviewController as AssetsPreviewController;
use CraftCms\Cms\Http\Controllers\Assets\TransformController;
use CraftCms\Cms\Http\Controllers\Assets\UploadController as AssetsUploadController;
use CraftCms\Cms\Http\Controllers\Auth\LoginController;
use CraftCms\Cms\Http\Controllers\Auth\PasskeyController;
use CraftCms\Cms\Http\Controllers\Auth\SessionInfoController;
use CraftCms\Cms\Http\Controllers\Auth\TwoFactorAuthenticationController;
use CraftCms\Cms\Http\Controllers\BaseUpdaterController;
use CraftCms\Cms\Http\Controllers\ConditionsController;
use CraftCms\Cms\Http\Controllers\ConfigSyncController;
use CraftCms\Cms\Http\Controllers\Dashboard\Widgets\CraftSupportController;
use CraftCms\Cms\Http\Controllers\Dashboard\Widgets\FeedController;
use CraftCms\Cms\Http\Controllers\Dashboard\Widgets\NewUsersController;
use CraftCms\Cms\Http\Controllers\Dashboard\WidgetsController;
use CraftCms\Cms\Http\Controllers\EditionController;
use CraftCms\Cms\Http\Controllers\Elements\ElementIndexSourcesController;
use CraftCms\Cms\Http\Controllers\Elements\ElementSelectorModalController;
use CraftCms\Cms\Http\Controllers\Elements\ElementSourcesController;
use CraftCms\Cms\Http\Controllers\Elements\ExportElementIndexController;
use CraftCms\Cms\Http\Controllers\Elements\PerformElementActionController;
use CraftCms\Cms\Http\Controllers\Elements\SearchController as ElementSearchController;
use CraftCms\Cms\Http\Controllers\Entries\CreateEntryController;
use CraftCms\Cms\Http\Controllers\Entries\MoveEntryToSectionController;
use CraftCms\Cms\Http\Controllers\Entries\StoreEntryController;
use CraftCms\Cms\Http\Controllers\FieldsController;
use CraftCms\Cms\Http\Controllers\Gql\ApiController as GqlApiController;
use CraftCms\Cms\Http\Controllers\Gql\SchemasController as GqlSchemasController;
use CraftCms\Cms\Http\Controllers\Gql\TokensController as GqlTokensController;
use CraftCms\Cms\Http\Controllers\IconController;
use CraftCms\Cms\Http\Controllers\InstallController;
use CraftCms\Cms\Http\Controllers\MatrixController;
use CraftCms\Cms\Http\Controllers\MigrateController;
use CraftCms\Cms\Http\Controllers\NestedElementsController;
use CraftCms\Cms\Http\Controllers\PluginsController;
use CraftCms\Cms\Http\Controllers\PluginStore\InstallController as PluginStoreInstallController;
use CraftCms\Cms\Http\Controllers\PluginStore\PluginStoreController;
use CraftCms\Cms\Http\Controllers\PluginStore\RemoveController;
use CraftCms\Cms\Http\Controllers\PreviewController;
use CraftCms\Cms\Http\Controllers\RelationalFieldsController;
use CraftCms\Cms\Http\Controllers\Settings\EntryTypesController;
use CraftCms\Cms\Http\Controllers\Settings\FilesystemsController;
use CraftCms\Cms\Http\Controllers\Settings\ImageTransformsController;
use CraftCms\Cms\Http\Controllers\Settings\RoutesController;
use CraftCms\Cms\Http\Controllers\Settings\SectionsController;
use CraftCms\Cms\Http\Controllers\Settings\UserGroupsController;
use CraftCms\Cms\Http\Controllers\Settings\UserSettingsController;
use CraftCms\Cms\Http\Controllers\Settings\VolumesController;
use CraftCms\Cms\Http\Controllers\StructuresController;
use CraftCms\Cms\Http\Controllers\Updates\UpdaterController;
use CraftCms\Cms\Http\Controllers\Updates\UpdatesController;
use CraftCms\Cms\Http\Controllers\Users\ActivateController;
use CraftCms\Cms\Http\Controllers\Users\AuthMethodController;
use CraftCms\Cms\Http\Controllers\Users\EnableController;
use CraftCms\Cms\Http\Controllers\Users\ImpersonationController;
use CraftCms\Cms\Http\Controllers\Users\PasskeysController as UserPasskeysController;
use CraftCms\Cms\Http\Controllers\Users\PasswordController;
use CraftCms\Cms\Http\Controllers\Users\PermissionsController;
use CraftCms\Cms\Http\Controllers\Users\PhotoController;
use CraftCms\Cms\Http\Controllers\Users\PreferencesController;
use CraftCms\Cms\Http\Controllers\Users\RecoveryCodesController;
use CraftCms\Cms\Http\Controllers\Users\SaveUserController;
use CraftCms\Cms\Http\Controllers\Users\SaveUsersFieldLayoutController;
use CraftCms\Cms\Http\Controllers\Users\SuspendController;
use CraftCms\Cms\Http\Controllers\Users\UnlockController;
use CraftCms\Cms\Http\Controllers\Users\UsersController;
use CraftCms\Cms\Http\Controllers\Utilities\AssetIndexesController;
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
use CraftCms\Cms\Http\Middleware\RequireToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

/**
 * Actions that are accessible both with and without CP can be registered here.
 */
foreach ([
    Cms::config()->actionTrigger => ['craft.web'],
    implode('/', [
        Cms::config()->cpTrigger,
        Cms::config()->actionTrigger,
    ]) => ['craft.cp'],
] as $prefix => $middleware) {
    Route::prefix($prefix)->middleware($middleware)->group(function () {
        // App
        Route::get('app/health-check', HealthCheckController::class);

        // Auth
        Route::post('users/login', [LoginController::class, 'attemptLogin']);
        Route::post('auth/verify-totp', [TwoFactorAuthenticationController::class, 'verify']);
        Route::post('auth/verify-recovery-code', [TwoFactorAuthenticationController::class, 'verifyRecoveryCode']);
        Route::post('auth/passkey-request-options', [PasskeyController::class, 'requestOptions']);
        Route::post('users/login-with-passkey', [PasskeyController::class, 'login']);
        Route::post('users/login-modal', [LoginController::class, 'showLoginModal']);
        Route::any('users/redirect', [LoginController::class, 'redirect']);
        Route::any('users/session-info', [SessionInfoController::class, 'show'])->withoutMiddleware(StartSession::class);
        Route::any('users/get-elevated-session-timeout', [SessionInfoController::class, 'confirmTimeout']);
        Route::middleware('throttle:1,1')->post('users/send-password-reset-email', [PasswordController::class, 'sendPasswordResetEmail']);
        Route::post('users/save-user', SaveUserController::class);

        // Asset Transforms (anonymous access)
        Route::any('assets/generate-transform', [TransformController::class, 'generate']);
        Route::get('assets/generate-fallback-transform', [TransformController::class, 'generateFallback']);
    });
}

/**
 * Actions that are accessible without CP can be registered here.
 */
Route::prefix(Cms::config()->actionTrigger)->group(function () {
    Route::any('graphql/api', GqlApiController::class);
    Route::post('migrate', MigrateController::class);

    Route::middleware(['auth:craft'])->group(function () {
        Route::post('entries/save-entry', StoreEntryController::class);
        Route::post('users/save-address', [CraftCms\Cms\Http\Controllers\Users\AddressesController::class, 'store']);
        Route::post('users/delete-address', [CraftCms\Cms\Http\Controllers\Users\AddressesController::class, 'destroy']);
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
    Route::any('app/icon-svg', [IconController::class, 'svg']);
    Route::any('app/icon-picker-options', [IconController::class, 'pickerOptions']);

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
    Route::middleware(['auth:craft'])->group(function () {
        // Addresses
        Route::post('addresses/fields', [AddressesController::class, 'fields']);
        Route::middleware(RequireAdminChanges::class)->post('addresses/save-field-layout', [AddressesController::class, 'saveFieldLayout']);

        // App
        Route::any('app/get-cp-alerts', [CpAlertsController::class, 'index']);
        Route::any('app/shun-cp-alert', [CpAlertsController::class, 'destroy']);
        Route::any('app/set-license-shun-cookie', [LicensesController::class, 'setShunCookie']);
        Route::middleware(RequireAdmin::class)->get('app/get-plugin-license-info', [CraftCms\Cms\Http\Controllers\App\PluginsController::class, 'getLicenseInfo']);
        Route::middleware(RequireAdminChanges::class)->post('app/update-plugin-license', [CraftCms\Cms\Http\Controllers\App\PluginsController::class, 'updateLicense']);
        Route::any('app/render-elements', [RenderController::class, 'elements']);
        Route::any('app/render-components', [RenderController::class, 'components']);

        // Auth methods
        Route::post('auth/method-setup-html', [AuthMethodController::class, 'setupHtml']);
        Route::post('auth/method-listing-html', [AuthMethodController::class, 'listingHtml']);
        Route::post('auth/remove-method', [AuthMethodController::class, 'destroy']);

        Route::post('auth/passkey-creation-options', [UserPasskeysController::class, 'creationOptions']);
        Route::post('auth/verify-passkey-creation', [UserPasskeysController::class, 'verifyCreation']);
        Route::post('auth/delete-passkey', [UserPasskeysController::class, 'delete']);

        Route::post('auth/generate-recovery-codes', [RecoveryCodesController::class, 'generate']);
        Route::post('auth/download-recovery-codes', [RecoveryCodesController::class, 'download']);

        // DeprecationErrors
        Route::post('utilities/get-deprecation-error-traces-modal', [DeprecationErrorsController::class, 'getDeprecationErrorTracesModal']);
        Route::post('utilities/delete-deprecation-error', [DeprecationErrorsController::class, 'deleteDeprecationError']);
        Route::post('utilities/delete-all-deprecation-errors', [DeprecationErrorsController::class, 'deleteAllDeprecationErrors']);

        // ClearCaches
        Route::post('utilities/clear-caches-perform-action', [ClearCachesController::class, 'clearCaches']);
        Route::post('utilities/invalidate-tags', [ClearCachesController::class, 'invalidateTags']);

        // Conditions
        Route::post('conditions/render', [ConditionsController::class, 'show']);
        Route::post('conditions/add-rule', [ConditionsController::class, 'store']);
        Route::post('conditions/remove-rule', [ConditionsController::class, 'destroy']);

        // DbBackup
        Route::post('utilities/db-backup-perform-action', DbBackupController::class);

        // DeprecationErrors
        Route::post('utilities/get-deprecation-error-traces-modal', [DeprecationErrorsController::class, 'getDeprecationErrorTracesModal']);
        Route::post('utilities/delete-deprecation-error', [DeprecationErrorsController::class, 'deleteDeprecationError']);
        Route::post('utilities/delete-all-deprecation-errors', [DeprecationErrorsController::class, 'deleteAllDeprecationErrors']);

        // Edition
        Route::middleware([RequireAdmin::class])->group(function () {
            Route::post('app/try-edition', [EditionController::class, 'tryEdition']);
            Route::post('app/switch-to-licensed-edition', [EditionController::class, 'switchToLicensedEdition']);
        });

        // Elements
        Route::post('element-indexes/source-path', [ElementIndexSourcesController::class, 'sourcePath']);
        Route::post('element-indexes/source-attribute-info', [ElementIndexSourcesController::class, 'sourceAttributeInfo']);
        Route::post('element-indexes/get-source-tree-html', [ElementIndexSourcesController::class, 'getSourceTreeHtml']);
        Route::post('element-indexes/export', ExportElementIndexController::class);
        Route::post('element-indexes/perform-action', PerformElementActionController::class);
        Route::post('element-search/search', ElementSearchController::class);
        Route::post('element-selector-modals/body', ElementSelectorModalController::class);
        Route::middleware([RequireAdminChanges::class])->group(function () {
            Route::post('element-index-settings/get-customize-sources-modal-data', [ElementSourcesController::class, 'show']);
            Route::post('element-index-settings/save-customize-sources-modal-settings', [ElementSourcesController::class, 'store']);
        });

        // Entries
        Route::post('entries/create', CreateEntryController::class);
        Route::post('entries/save-entry', StoreEntryController::class);
        Route::post('entries/move-to-section-modal-data', [MoveEntryToSectionController::class, 'showModal']);
        Route::post('entries/move-to-section', [MoveEntryToSectionController::class, 'move']);

        // Entry Types
        Route::get('entry-types/table-data', [EntryTypesController::class, 'tableData']);
        Route::get('entry-types/edit/{entryType?}', [EntryTypesController::class, 'edit']);
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

        // GraphQL
        Route::middleware([RequireAdmin::class])->group(function () {
            Route::post('graphql/delete-token', [GqlTokensController::class, 'destroy']);
            Route::post('graphql/generate-token', [GqlTokensController::class, 'generate']);

            Route::middleware('password.confirm')->group(function () {
                Route::post('graphql/save-token', [GqlTokensController::class, 'store']);
                Route::post('graphql/fetch-token', [GqlTokensController::class, 'fetch']);
            });
        });

        Route::middleware([RequireAdminChanges::class])->group(function () {
            Route::post('graphql/delete-schema', [GqlSchemasController::class, 'destroy']);

            Route::middleware('password.confirm')->group(function () {
                Route::post('graphql/save-schema', [GqlSchemasController::class, 'save']);
                Route::post('graphql/save-public-schema', [GqlSchemasController::class, 'savePublic']);
            });
        });

        // Matrix
        Route::post('matrix/default-table-column-options', [MatrixController::class, 'defaultTableColumnOptions']);
        Route::post('matrix/create-entry', [MatrixController::class, 'createEntry']);
        Route::post('matrix/render-blocks', [MatrixController::class, 'renderBlocks']);

        // Migrations
        Route::post('utilities/apply-new-migrations', MigrationsController::class);

        // Nested entries
        Route::post('nested-elements/reorder', [NestedElementsController::class, 'reorder']);
        Route::post('nested-elements/delete', [NestedElementsController::class, 'destroy']);

        // Asset Indexes
        Route::post('asset-indexes/start-indexing', [AssetIndexesController::class, 'startIndexing']);
        Route::post('asset-indexes/stop-indexing-session', [AssetIndexesController::class, 'stopIndexingSession']);
        Route::post('asset-indexes/process-indexing-session', [AssetIndexesController::class, 'processIndexingSession']);
        Route::post('asset-indexes/indexing-session-overview', [AssetIndexesController::class, 'indexingSessionOverview']);
        Route::post('asset-indexes/finish-indexing-session', [AssetIndexesController::class, 'finishIndexingSession']);

        // Assets
        Route::post('assets/upload', [AssetsUploadController::class, 'upload']);
        Route::post('assets/replace-file', [AssetsUploadController::class, 'replaceFile']);
        Route::post('assets/delete-asset', [AssetsActionController::class, 'deleteAsset']);
        Route::post('assets/move-asset', [AssetsActionController::class, 'moveAsset']);
        Route::post('assets/download-asset', [AssetsActionController::class, 'downloadAsset']);
        Route::any('assets/show-in-folder', [AssetsActionController::class, 'showInFolder']);
        Route::post('assets/move-info', [AssetsActionController::class, 'moveInfo']);
        Route::post('assets/preview-thumb', [AssetsPreviewController::class, 'previewThumb']);
        Route::post('assets/preview-file', [AssetsPreviewController::class, 'previewFile']);
        Route::post('assets/create-folder', [AssetsFolderController::class, 'create']);
        Route::post('assets/delete-folder', [AssetsFolderController::class, 'delete']);
        Route::post('assets/rename-folder', [AssetsFolderController::class, 'rename']);
        Route::post('assets/move-folder', [AssetsFolderController::class, 'move']);
        Route::post('assets/image-editor', [ImageEditorController::class, 'show']);
        Route::get('assets/edit-image', [ImageEditorController::class, 'editImage']);
        Route::post('assets/save-image', [ImageEditorController::class, 'save']);
        Route::post('assets/update-focal-position', [ImageEditorController::class, 'updateFocalPoint']);
        Route::get('assets/icon/{extension?}', AssetsIconController::class);

        // Preview
        Route::any('preview/create-token', [PreviewController::class, 'createToken']);

        // Relational fields
        Route::any('relational-fields/structured-input-html', [RelationalFieldsController::class, 'structuredInputHtml']);

        // Widgets
        Route::post('dashboard/create-widget', [WidgetsController::class, 'store']);
        Route::post('dashboard/save-widget-settings', [WidgetsController::class, 'update']);
        Route::post('dashboard/delete-user-widget', [WidgetsController::class, 'delete']);
        Route::post('dashboard/change-widget-colspan', [WidgetsController::class, 'updateColspan']);
        Route::post('dashboard/reorder-user-widgets', [WidgetsController::class, 'reorder']);
        Route::post('dashboard/cache-feed-data', [FeedController::class, 'cacheData']);
        Route::post('dashboard/send-support-request', CraftSupportController::class);
        Route::post('charts/get-new-users-data', [NewUsersController::class, 'data']);

        // Filesystems
        Route::middleware([RequireAdminChanges::class])->group(function () {
            Route::get('fs/edit', [FilesystemsController::class, 'edit']);
            Route::post('fs/save', [FilesystemsController::class, 'save']);
            Route::post('fs/remove', [FilesystemsController::class, 'delete']);
        });

        // Volumes
        Route::middleware([RequireAdminChanges::class])->group(function () {
            Route::post('volumes/save-volume', [VolumesController::class, 'save']);
            Route::post('volumes/delete-volume', [VolumesController::class, 'delete']);
            Route::post('volumes/reorder-volumes', [VolumesController::class, 'reorder']);
            Route::post('image-transforms/save', [ImageTransformsController::class, 'save']);
            Route::post('image-transforms/delete', [ImageTransformsController::class, 'delete']);
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
        Route::prefix('config-sync')->middleware([RequireAdmin::class])->group(function () {
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
        Route::post('users/mark-announcements-as-read', [AnnouncementsController::class, 'markRead']);

        Route::middleware('password.confirm')->group(function () {
            Route::post('users/save-password', [PasswordController::class, 'store']);
        });

        Route::middleware([RequireEdition::class.':'.Edition::Team->value, 'can:editUsers'])->group(function () {
            Route::middleware('password.confirm')->group(function () {
                Route::post('users/impersonate', [ImpersonationController::class, 'impersonate']);
                Route::post('users/get-impersonation-url', [ImpersonationController::class, 'getUrl']);
            });

            Route::post('users/get-password-reset-url', [PasswordController::class, 'passwordResetUrl']);
            Route::post('users/enable-user', EnableController::class);
            Route::post('users/activate-user', [ActivateController::class, 'activate']);
            Route::post('users/deactivate-user', [ActivateController::class, 'deactivate']);
            Route::post('users/send-activation-email', [ActivateController::class, 'sendActivationEmail']);
            Route::post('users/unlock-user', UnlockController::class);
            Route::post('users/suspend-user', [SuspendController::class, 'suspend']);
            Route::post('users/unsuspend-user', [SuspendController::class, 'unsuspend']);
        });

        Route::post('users/save-permissions', [PermissionsController::class, 'store']);
        Route::post('users/save-preferences', [PreferencesController::class, 'store']);
        Route::post('users/delete-user', [UsersController::class, 'destroy']);
        Route::post('users/user-content-summary', [UsersController::class, 'contentSummary']);
        Route::post('users/render-photo-input', [PhotoController::class, 'renderInput']);
        Route::post('users/upload-user-photo', [PhotoController::class, 'upload']);
        Route::post('users/delete-user-photo', [PhotoController::class, 'destroy']);
        Route::post('users/require-password-reset', [PasswordController::class, 'requireReset']);
        Route::post('users/remove-password-reset-requirement', [PasswordController::class, 'removeResetRequirement']);
        Route::post('users/verify-password', [PasswordController::class, 'verifyPassword']);
        Route::post('users/save-field-layout', SaveUsersFieldLayoutController::class);

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
