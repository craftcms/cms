<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\LoginRateLimiter;
use CraftCms\Cms\Auth\TwoFactorRateLimiter;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Controllers\AddressesController;
use CraftCms\Cms\Http\Controllers\AnnouncementsController;
use CraftCms\Cms\Http\Controllers\ApiController;
use CraftCms\Cms\Http\Controllers\App\CpAlertsController;
use CraftCms\Cms\Http\Controllers\App\HealthCheckController;
use CraftCms\Cms\Http\Controllers\App\LicensesController;
use CraftCms\Cms\Http\Controllers\App\PluginsController;
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
use CraftCms\Cms\Http\Controllers\Auth\SetPasswordController;
use CraftCms\Cms\Http\Controllers\Auth\TwoFactorAuthenticationController;
use CraftCms\Cms\Http\Controllers\Auth\VerifyEmailController;
use CraftCms\Cms\Http\Controllers\BaseUpdaterController;
use CraftCms\Cms\Http\Controllers\ConditionsController;
use CraftCms\Cms\Http\Controllers\Dashboard\Widgets\CraftSupportController;
use CraftCms\Cms\Http\Controllers\Dashboard\Widgets\FeedController;
use CraftCms\Cms\Http\Controllers\Dashboard\Widgets\NewUsersController;
use CraftCms\Cms\Http\Controllers\Dashboard\WidgetsController;
use CraftCms\Cms\Http\Controllers\EditionController;
use CraftCms\Cms\Http\Controllers\Elements\CopyElementValuesController;
use CraftCms\Cms\Http\Controllers\Elements\CreateElementController;
use CraftCms\Cms\Http\Controllers\Elements\DeleteElementController;
use CraftCms\Cms\Http\Controllers\Elements\DeleteElementsController;
use CraftCms\Cms\Http\Controllers\Elements\DuplicateElementController;
use CraftCms\Cms\Http\Controllers\Elements\EditElementController;
use CraftCms\Cms\Http\Controllers\Elements\ElementActivityController;
use CraftCms\Cms\Http\Controllers\Elements\ElementDraftsController;
use CraftCms\Cms\Http\Controllers\Elements\ElementIndex\ElementIndexController;
use CraftCms\Cms\Http\Controllers\Elements\ElementIndex\ElementIndexSourcesController;
use CraftCms\Cms\Http\Controllers\Elements\ElementIndex\ExportElementIndexController;
use CraftCms\Cms\Http\Controllers\Elements\ElementIndex\SaveElementIndexElementsController;
use CraftCms\Cms\Http\Controllers\Elements\ElementRevisionsController;
use CraftCms\Cms\Http\Controllers\Elements\ElementSelectorModalController;
use CraftCms\Cms\Http\Controllers\Elements\ElementSourcesController;
use CraftCms\Cms\Http\Controllers\Elements\PerformElementActionController;
use CraftCms\Cms\Http\Controllers\Elements\SaveElementController;
use CraftCms\Cms\Http\Controllers\Elements\SearchController as ElementSearchController;
use CraftCms\Cms\Http\Controllers\Elements\UpdateFieldLayoutController;
use CraftCms\Cms\Http\Controllers\Elements\ValidateElementController;
use CraftCms\Cms\Http\Controllers\Entries\CreateEntryController;
use CraftCms\Cms\Http\Controllers\Entries\MoveEntryToSectionController;
use CraftCms\Cms\Http\Controllers\Entries\ReassignEntriesModalController;
use CraftCms\Cms\Http\Controllers\Entries\StoreEntryController;
use CraftCms\Cms\Http\Controllers\FieldsController;
use CraftCms\Cms\Http\Controllers\Gql\ApiController as GqlApiController;
use CraftCms\Cms\Http\Controllers\IconController;
use CraftCms\Cms\Http\Controllers\Import\ImportConfigController;
use CraftCms\Cms\Http\Controllers\Import\ImportRunController;
use CraftCms\Cms\Http\Controllers\MatrixController;
use CraftCms\Cms\Http\Controllers\MigrateController;
use CraftCms\Cms\Http\Controllers\NestedElementsController;
use CraftCms\Cms\Http\Controllers\PluginStore\InstallController as PluginStoreInstallController;
use CraftCms\Cms\Http\Controllers\PluginStore\PluginStoreController;
use CraftCms\Cms\Http\Controllers\PreviewController;
use CraftCms\Cms\Http\Controllers\QueueController;
use CraftCms\Cms\Http\Controllers\RelationalFieldsController;
use CraftCms\Cms\Http\Controllers\Settings\EntryTypesController;
use CraftCms\Cms\Http\Controllers\Settings\VolumesController;
use CraftCms\Cms\Http\Controllers\StructuresController;
use CraftCms\Cms\Http\Controllers\Updates\UpdatesController;
use CraftCms\Cms\Http\Controllers\Users\ActivateController;
use CraftCms\Cms\Http\Controllers\Users\AuthMethodController;
use CraftCms\Cms\Http\Controllers\Users\EnableController;
use CraftCms\Cms\Http\Controllers\Users\ImpersonationController;
use CraftCms\Cms\Http\Controllers\Users\PasskeysController as UserPasskeysController;
use CraftCms\Cms\Http\Controllers\Users\PasswordController;
use CraftCms\Cms\Http\Controllers\Users\PhotoController;
use CraftCms\Cms\Http\Controllers\Users\RecoveryCodesController;
use CraftCms\Cms\Http\Controllers\Users\SaveUserController;
use CraftCms\Cms\Http\Controllers\Users\SuspendController;
use CraftCms\Cms\Http\Controllers\Users\UnlockController;
use CraftCms\Cms\Http\Controllers\Utilities\AssetIndexesController;
use CraftCms\Cms\Http\Controllers\Utilities\UtilitiesController;
use CraftCms\Cms\Http\Middleware\EnsureTwoFactorChallengeIsRecent;
use CraftCms\Cms\Http\Middleware\RequireAdmin;
use CraftCms\Cms\Http\Middleware\RequireAdminChanges;
use CraftCms\Cms\Http\Middleware\RequireEdition;
use CraftCms\Cms\Http\Middleware\RequireToken;
use CraftCms\Cms\Http\Middleware\StartSessionWithoutPersistence;
use CraftCms\Cms\Route\Routes as CraftRoutes;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

$routes = app(CraftRoutes::class);
$sharedActionRouteGroups = $routes->actionTriggerRoutePrefix() === $routes->cpActionTriggerRoutePrefix()
    ? [[$routes->cpActionTriggerRoutePrefix(), ['craft.cp']]]
    : [
        [$routes->actionTriggerRoutePrefix(), ['craft.web']],
        [$routes->cpActionTriggerRoutePrefix(), ['craft.cp']],
    ];

/**
 * Actions that are accessible both with and without CP can be registered here.
 */
foreach ($sharedActionRouteGroups as [$prefix, $middleware]) {
    Route::prefix($prefix)->middleware($middleware)->group(function () use ($middleware) {
        // App
        Route::get('app/health-check', HealthCheckController::class);

        // Auth
        Route::middleware([EnsureTwoFactorChallengeIsRecent::class, 'throttle:'.TwoFactorRateLimiter::NAME])->group(function () {
            Route::post('auth/verify-totp', [TwoFactorAuthenticationController::class, 'verify']);
            Route::post('auth/verify-recovery-code', [TwoFactorAuthenticationController::class, 'verifyRecoveryCode']);
        });
        Route::post('auth/passkey-request-options', [PasskeyController::class, 'requestOptions']);
        Route::post('users/login', [LoginController::class, 'attemptLogin'])
            ->middleware('throttle:'.LoginRateLimiter::NAME);
        Route::post('users/login-with-passkey', [PasskeyController::class, 'login'])
            ->middleware('throttle:'.LoginRateLimiter::NAME);
        Route::post('users/login-modal', [LoginController::class, 'showLoginModal']);
        Route::any('users/redirect', [LoginController::class, 'redirect']);
        Route::post('users/set-password', [SetPasswordController::class, 'store']);
        Route::post('users/verify-email', [VerifyEmailController::class, 'store']);
        Route::any('users/session-info', [SessionInfoController::class, 'show'])
            ->middleware(StartSessionWithoutPersistence::class)
            ->withoutMiddleware([StartSession::class, ShareErrorsFromSession::class, PreventRequestForgery::class]);
        Route::any('users/get-elevated-session-timeout', [SessionInfoController::class, 'confirmTimeout'])
            ->middleware(StartSessionWithoutPersistence::class)
            ->withoutMiddleware([StartSession::class, ShareErrorsFromSession::class, PreventRequestForgery::class]);
        Route::post('users/confirm-password', [SessionInfoController::class, 'confirmPassword'])
            ->middleware(['auth', 'can:accessCp'])
            ->block();
        Route::middleware(
            in_array('craft.cp', $middleware) ? null : 'throttle:password-reset'
        )->post('users/send-password-reset-email', [PasswordController::class, 'sendPasswordResetEmail']);
        Route::post('users/save-user', SaveUserController::class);

        // Asset Transforms (anonymous access)
        Route::any('assets/generate-transform', [TransformController::class, 'generate']);
        Route::get('assets/generate-fallback-transform', [TransformController::class, 'generateFallback']);

        // GQL API
        Route::any('graphql/api', GqlApiController::class);

        // Queue
        Route::any('queue/run', [QueueController::class, 'run']);
        Route::middleware(['auth', 'can:accessCp'])
            ->get('queue/get-job-info', [QueueController::class, 'jobInfo']);
    });
}

/**
 * Actions that are accessible without CP can be registered here.
 */
Route::prefix($routes->actionTriggerRoutePrefix())->group(function () {
    Route::post('migrate', MigrateController::class);

    Route::middleware(['auth'])->group(function () {
        Route::post('entries/save-entry', StoreEntryController::class);
        Route::post('users/save-address', [CraftCms\Cms\Http\Controllers\Users\AddressesController::class, 'store']);
        Route::post('users/delete-address', [CraftCms\Cms\Http\Controllers\Users\AddressesController::class, 'destroy']);
    });

    Route::middleware([RequireToken::class])->group(function () {
        Route::any('users/impersonate-with-token', [ImpersonationController::class, 'withToken']);
    });
});

Route::prefix($routes->cpActionTriggerRoutePrefix())->middleware(['craft.cp'])->group(function () {
    /**
     * Actions not needing auth
     */
    Route::any('app/api-headers', [ApiController::class, 'headers']);
    Route::any('app/process-api-response-headers', [ApiController::class, 'processResponseHeaders']);
    Route::any('app/get-utilities-badge-count', [UtilitiesController::class, 'badgeCount']);
    Route::any('app/icon-svg', [IconController::class, 'svg']);
    Route::any('app/icon-picker-options', [IconController::class, 'pickerOptions']);

    /**
     * Actions needing auth
     */
    Route::middleware(['auth', 'can:accessCp'])->group(function () {
        // Addresses
        Route::post('addresses/fields', [AddressesController::class, 'fields']);

        // App
        Route::post('app/get-cp-alerts', [CpAlertsController::class, 'index']);
        Route::post('app/shun-cp-alert', [CpAlertsController::class, 'destroy']);
        Route::post('app/set-license-shun-cookie', [LicensesController::class, 'setShunCookie']);
        Route::middleware(RequireAdmin::class)->post('app/get-plugin-license-info', [PluginsController::class, 'getLicenseInfo']);
        Route::middleware(RequireAdminChanges::class)->post('app/update-plugin-license', [PluginsController::class, 'updateLicense']);
        Route::post('app/render-elements', [RenderController::class, 'elements']);
        Route::post('app/render-components', [RenderController::class, 'components']);
        Route::post('app/render-markdown', [RenderController::class, 'markdown']);

        // Auth methods
        Route::post('auth/method-setup-html', [AuthMethodController::class, 'setupHtml']);
        Route::post('auth/method-listing-html', [AuthMethodController::class, 'listingHtml']);
        Route::post('auth/remove-method', [AuthMethodController::class, 'destroy']);

        Route::post('auth/passkey-creation-options', [UserPasskeysController::class, 'creationOptions']);
        Route::post('auth/verify-passkey-creation', [UserPasskeysController::class, 'verifyCreation']);
        Route::post('auth/delete-passkey', [UserPasskeysController::class, 'delete']);

        Route::post('auth/generate-recovery-codes', [RecoveryCodesController::class, 'generate']);
        Route::post('auth/download-recovery-codes', [RecoveryCodesController::class, 'download']);

        // Conditions
        Route::post('conditions/render', [ConditionsController::class, 'show']);
        Route::post('conditions/add-rule', [ConditionsController::class, 'store']);
        Route::post('conditions/remove-rule', [ConditionsController::class, 'destroy']);

        // Edition
        Route::middleware([RequireAdmin::class])->group(function () {
            Route::post('app/try-edition', [EditionController::class, 'tryEdition']);
            Route::post('app/switch-to-licensed-edition', [EditionController::class, 'switchToLicensedEdition']);
        });

        // Elements
        Route::post('delete-elements/deletion-blockers', [DeleteElementsController::class, 'deletionBlockers']);
        Route::post('delete-elements/delete', [DeleteElementsController::class, 'destroy']);
        Route::any('delete-elements/replace-relations-modal', [DeleteElementsController::class, 'replaceRelationsModal']);
        Route::post('delete-elements/replace-relations', [DeleteElementsController::class, 'replaceRelations']);
        Route::any('delete-elements/replace-references-modal', [DeleteElementsController::class, 'replaceReferencesModal']);
        Route::post('delete-elements/replace-references', [DeleteElementsController::class, 'replaceReferences']);

        Route::post('elements/create', CreateElementController::class);
        Route::any('elements/edit', EditElementController::class);
        Route::post('elements/save', [SaveElementController::class, 'store']);
        Route::post('elements/save-nested-element-for-derivative', [SaveElementController::class, 'storeForDerivative']);
        Route::post('elements/delete', [DeleteElementController::class, 'destroy']);
        Route::post('elements/delete-for-site', [DeleteElementController::class, 'destroyForSite']);
        Route::post('elements/save-draft', [ElementDraftsController::class, 'store']);
        Route::post('elements/ensure-draft', [ElementDraftsController::class, 'ensure']);
        Route::post('elements/apply-draft', [ElementDraftsController::class, 'apply']);
        Route::post('elements/delete-draft', [ElementDraftsController::class, 'destroy']);
        Route::post('elements/revert', [ElementRevisionsController::class, 'revert']);
        Route::post('elements/validate', ValidateElementController::class);
        Route::post('elements/recent-activity', ElementActivityController::class);
        Route::post('elements/update-field-layout', UpdateFieldLayoutController::class);
        Route::post('elements/duplicate', [DuplicateElementController::class, 'duplicate']);
        Route::post('elements/bulk-duplicate', [DuplicateElementController::class, 'bulkDuplicate']);
        Route::post('elements/copy-values-from-site', CopyElementValuesController::class);

        // Element Indexes
        Route::post('element-indexes/source-path', [ElementIndexSourcesController::class, 'sourcePath']);
        Route::post('element-indexes/source-attribute-info', [ElementIndexSourcesController::class, 'sourceAttributeInfo']);
        Route::post('element-indexes/get-elements', [ElementIndexController::class, 'getElements']);
        Route::post('element-indexes/get-more-elements', [ElementIndexController::class, 'getMoreElements']);
        Route::post('element-indexes/count-elements', [ElementIndexController::class, 'countElements']);
        Route::post('element-indexes/get-source-tree-html', [ElementIndexSourcesController::class, 'getSourceTreeHtml']);
        Route::post('element-indexes/filter-hud', [ElementIndexController::class, 'filterHud']);
        Route::post('element-indexes/element-table-html', [ElementIndexController::class, 'elementTableHtml']);
        Route::post('element-indexes/save-elements', SaveElementIndexElementsController::class);
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
        Route::any('entries/reassign-modal', [ReassignEntriesModalController::class, 'show']);
        Route::any('entries/reassign', [ReassignEntriesModalController::class, 'store']);

        // Entry Types
        Route::middleware([
            RequireAdminChanges::class,
        ])->group(function () {
            Route::post('entry-types/render-form', [EntryTypesController::class, 'renderForm']);
            Route::post('entry-types/render-override-settings', [EntryTypesController::class, 'renderOverrideSettings']);
            Route::post('entry-types/apply-override-settings', [EntryTypesController::class, 'applyOverrideSettings']);
        });

        // Fields
        Route::post('fields/render-field-layout-designer', [FieldsController::class, 'renderFieldLayoutDesigner']);
        Route::middleware([RequireAdminChanges::class])->group(function () {
            Route::post('fields/render-form', [FieldsController::class, 'renderForm']);
            Route::post('fields/render-grouped-entry-type-manager', [FieldsController::class, 'renderGroupedEntryTypeManager']);
            Route::post('fields/render-condition-builder', [FieldsController::class, 'renderConditionBuilder']);
            Route::post('fields/normalize-condition-builder', [FieldsController::class, 'normalizeConditionBuilder']);
            Route::post('fields/render-field-select', [FieldsController::class, 'renderFieldSelect']);
            Route::post('fields/render-layout-component-settings', [FieldsController::class, 'renderLayoutComponentSettings']);
            Route::post('fields/refresh-layout-component-settings', [FieldsController::class, 'refreshLayoutComponentSettings']);
            Route::post('fields/apply-layout-tab-settings', [FieldsController::class, 'applyLayoutTabSettings']);
            Route::post('fields/apply-layout-element-settings', [FieldsController::class, 'applyLayoutElementSettings']);
            Route::post('fields/render-card-preview', [FieldsController::class, 'renderCardPreview']);
        });

        // Import
        Route::middleware('can:editImportConfigs')->group(function () {
            Route::post('import/configs/render-settings', [ImportConfigController::class, 'renderSettings']);
            Route::post('import/configs/save', [ImportConfigController::class, 'store']);
            Route::post('import/configs/saveFieldLayoutProvider', [ImportConfigController::class, 'storeFieldLayoutProvider']);
            Route::post('import/configs/saveMap', [ImportConfigController::class, 'storeMap']);
            Route::get('import/configs/editNestedFieldMapping', [ImportConfigController::class, 'editNestedFieldMapping']);
            Route::post('import/configs/saveNestedFieldMapping', [ImportConfigController::class, 'storeNestedFieldMapping']);
            Route::post('import/configs/duplicate', [ImportConfigController::class, 'duplicate']);
        });
        Route::middleware('can:deleteImportConfigs')->post('import/configs/delete', [ImportConfigController::class, 'destroy']);

        Route::middleware('can:editImportRuns')->post('import/runs/save', [ImportRunController::class, 'store']);
        Route::middleware('can:deleteImportRuns')->post('import/runs/delete', [ImportRunController::class, 'destroy']);
        Route::middleware('can:triggerImportRuns')->group(function () {
            Route::post('import/run', [ImportRunController::class, 'run']);
            Route::post('import/configs/run', [ImportConfigController::class, 'run']);
        });

        // Matrix
        Route::post('matrix/default-table-column-options', [MatrixController::class, 'defaultTableColumnOptions']);
        Route::post('matrix/create-entry', [MatrixController::class, 'createEntry']);
        Route::post('matrix/render-blocks', [MatrixController::class, 'renderBlocks']);

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
        Route::post('relational-fields/structured-input-html', [RelationalFieldsController::class, 'structuredInputHtml']);

        // Widgets
        Route::post('dashboard/create-widget', [WidgetsController::class, 'store']);
        Route::post('dashboard/save-widget-settings', [WidgetsController::class, 'update']);
        Route::post('dashboard/refresh-widget-settings', [WidgetsController::class, 'refreshSettings']);
        Route::post('dashboard/delete-user-widget', [WidgetsController::class, 'delete']);
        Route::post('dashboard/change-widget-colspan', [WidgetsController::class, 'updateColspan']);
        Route::post('dashboard/reorder-user-widgets', [WidgetsController::class, 'reorder']);
        Route::post('dashboard/cache-feed-data', [FeedController::class, 'cacheData']);
        Route::post('dashboard/send-support-request', CraftSupportController::class);
        Route::post('charts/get-new-users-data', [NewUsersController::class, 'data']);

        // Volumes
        Route::middleware([RequireAdminChanges::class])->group(function () {
            Route::post('volumes/reorder-volumes', [VolumesController::class, 'reorder']);
        });

        // Structures
        Route::post('structures/get-element-level-delta', [StructuresController::class, 'getElementLevelDelta']);
        Route::post('structures/move-element', [StructuresController::class, 'moveElement']);

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

        Route::post('users/render-photo-input', [PhotoController::class, 'renderInput']);
        Route::post('users/upload-user-photo', [PhotoController::class, 'upload']);
        Route::post('users/delete-user-photo', [PhotoController::class, 'destroy']);
        Route::post('users/require-password-reset', [PasswordController::class, 'requireReset']);
        Route::post('users/remove-password-reset-requirement', [PasswordController::class, 'removeResetRequirement']);
        Route::post('users/verify-password', [PasswordController::class, 'verifyPassword']);

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

    });
});
