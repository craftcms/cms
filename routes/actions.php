<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\LoginRateLimiter;
use CraftCms\Cms\Auth\TwoFactorRateLimiter;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Controllers\AddressesController;
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
use CraftCms\Cms\Http\Controllers\Assets\ResolveUploadConflictController;
use CraftCms\Cms\Http\Controllers\Assets\TransformController;
use CraftCms\Cms\Http\Controllers\Assets\UploadSessionController as AssetUploadSessionController;
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
use CraftCms\Cms\Http\Controllers\Elements\ActivityCommentsController;
use CraftCms\Cms\Http\Controllers\Elements\ActivityMentionSuggestionsController;
use CraftCms\Cms\Http\Controllers\Elements\ActivityTimelineController;
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
use CraftCms\Cms\Http\Controllers\UploadSessionController;
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
use CraftCms\Cms\Http\Middleware\RequireConfirmedPassword;
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
        Route::post('assets/uploads', [AssetUploadSessionController::class, 'store'])
            ->middleware('throttle:60,1')
            ->name(in_array('craft.cp', $middleware, true) ? 'craft.cp.uploads.store' : 'craft.uploads.store');

        Route::prefix('uploads')
            ->name(in_array('craft.cp', $middleware, true) ? 'craft.cp.uploads.' : 'craft.uploads.')
            ->group(function () {
                Route::any('{upload}/transfer', [UploadSessionController::class, 'transfer'])->whereUuid('upload')->name('transfer');
                Route::get('{upload}', [UploadSessionController::class, 'status'])->whereUuid('upload')->name('status');
                Route::post('{upload}/complete', [UploadSessionController::class, 'complete'])->whereUuid('upload')->name('complete');
                Route::delete('{upload}', [UploadSessionController::class, 'destroy'])->whereUuid('upload')->name('destroy');
            });

        // App
        Route::allowDuringMaintenance()->get('app/health-check', HealthCheckController::class);

        // Auth
        Route::allowDuringMaintenance()->group(function () use ($middleware) {
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
            Route::middleware(
                in_array('craft.cp', $middleware) ? null : 'throttle:password-reset'
            )->post('users/send-password-reset-email', [PasswordController::class, 'sendPasswordResetEmail']);
        });
        Route::any('users/get-elevated-session-timeout', [SessionInfoController::class, 'confirmTimeout'])
            ->middleware(StartSessionWithoutPersistence::class)
            ->withoutMiddleware([StartSession::class, ShareErrorsFromSession::class, PreventRequestForgery::class]);
        Route::post('users/confirm-password', [SessionInfoController::class, 'confirmPassword'])
            ->middleware(['auth', 'can:accessCp'])
            ->block();
        Route::post('users/save-user', SaveUserController::class);

        // Asset Transforms (anonymous access)
        Route::any('assets/generate-transform', [TransformController::class, 'generate']);

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
    Route::allowDuringMaintenance()->post('migrate', MigrateController::class);

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
        Route::post('conditions/render-rule', [ConditionsController::class, 'rule']);
        Route::post('conditions/validate', [ConditionsController::class, 'validate']);

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
        Route::post('elements/activity', ActivityTimelineController::class);
        Route::middleware('throttle:60,1')->group(function () {
            Route::post('elements/activity/comments', [ActivityCommentsController::class, 'store']);
            Route::patch('elements/activity/comments', [ActivityCommentsController::class, 'update']);
            Route::delete('elements/activity/comments', [ActivityCommentsController::class, 'destroy']);
        });
        Route::get('elements/activity/mentions', ActivityMentionSuggestionsController::class)->middleware('throttle:120,1');
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
            Route::post('element-index-settings/source-settings-ui', [ElementSourcesController::class, 'ui']);
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
