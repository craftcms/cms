<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\Enums\CpAuthPath;
use CraftCms\Cms\Auth\LoginRateLimiter;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Controllers\Assets\EditAssetController;
use CraftCms\Cms\Http\Controllers\Assets\IndexController as AssetsIndexController;
use CraftCms\Cms\Http\Controllers\Auth\LoginController;
use CraftCms\Cms\Http\Controllers\Auth\SetPasswordController;
use CraftCms\Cms\Http\Controllers\Auth\TwoFactorAuthenticationController;
use CraftCms\Cms\Http\Controllers\Auth\VerifyEmailController;
use CraftCms\Cms\Http\Controllers\BaseUpdaterController;
use CraftCms\Cms\Http\Controllers\ConfigSyncController;
use CraftCms\Cms\Http\Controllers\ContentIndexController;
use CraftCms\Cms\Http\Controllers\Dashboard\DashboardController;
use CraftCms\Cms\Http\Controllers\Elements\EditElementController;
use CraftCms\Cms\Http\Controllers\Elements\ElementRedirectController;
use CraftCms\Cms\Http\Controllers\Elements\ElementRevisionsController;
use CraftCms\Cms\Http\Controllers\Elements\PreviewElementController;
use CraftCms\Cms\Http\Controllers\Entries\CreateEntryController;
use CraftCms\Cms\Http\Controllers\Entries\EditEntryController;
use CraftCms\Cms\Http\Controllers\Entries\EntriesIndexController;
use CraftCms\Cms\Http\Controllers\FieldsController;
use CraftCms\Cms\Http\Controllers\Gql\GraphiqlController;
use CraftCms\Cms\Http\Controllers\Gql\IndexController as GqlIndexController;
use CraftCms\Cms\Http\Controllers\Gql\SchemasController;
use CraftCms\Cms\Http\Controllers\Gql\TokensController;
use CraftCms\Cms\Http\Controllers\InstallController;
use CraftCms\Cms\Http\Controllers\NotificationsController;
use CraftCms\Cms\Http\Controllers\PluginsController;
use CraftCms\Cms\Http\Controllers\PluginStore\PluginStoreController;
use CraftCms\Cms\Http\Controllers\PluginStore\RemoveController;
use CraftCms\Cms\Http\Controllers\QueueController;
use CraftCms\Cms\Http\Controllers\Settings\AddressSettingsController;
use CraftCms\Cms\Http\Controllers\Settings\AssetTransformersController;
use CraftCms\Cms\Http\Controllers\Settings\EmailSettingsController;
use CraftCms\Cms\Http\Controllers\Settings\EntryTypesController;
use CraftCms\Cms\Http\Controllers\Settings\FilesystemsController;
use CraftCms\Cms\Http\Controllers\Settings\GeneralSettingsController;
use CraftCms\Cms\Http\Controllers\Settings\ImageTransformsController;
use CraftCms\Cms\Http\Controllers\Settings\RoutesController;
use CraftCms\Cms\Http\Controllers\Settings\SectionsController;
use CraftCms\Cms\Http\Controllers\Settings\SettingsIndexController;
use CraftCms\Cms\Http\Controllers\Settings\SiteGroupsController;
use CraftCms\Cms\Http\Controllers\Settings\SitesController;
use CraftCms\Cms\Http\Controllers\Settings\Users\UserFieldsController;
use CraftCms\Cms\Http\Controllers\Settings\Users\UserGroupsController;
use CraftCms\Cms\Http\Controllers\Settings\Users\UserSettingsController;
use CraftCms\Cms\Http\Controllers\Settings\VolumesController;
use CraftCms\Cms\Http\Controllers\Updates\UpdaterController;
use CraftCms\Cms\Http\Controllers\Users\AddressesController;
use CraftCms\Cms\Http\Controllers\Users\IndexController as UsersIndexController;
use CraftCms\Cms\Http\Controllers\Users\PasskeysController;
use CraftCms\Cms\Http\Controllers\Users\PasswordController;
use CraftCms\Cms\Http\Controllers\Users\PermissionsController;
use CraftCms\Cms\Http\Controllers\Users\PreferencesController;
use CraftCms\Cms\Http\Controllers\Users\SignInProvidersController;
use CraftCms\Cms\Http\Controllers\Users\UsersController;
use CraftCms\Cms\Http\Controllers\Utilities\ClearCachesController;
use CraftCms\Cms\Http\Controllers\Utilities\DbBackupController;
use CraftCms\Cms\Http\Controllers\Utilities\DeprecationErrorsController;
use CraftCms\Cms\Http\Controllers\Utilities\FindAndReplaceController;
use CraftCms\Cms\Http\Controllers\Utilities\MigrationsController;
use CraftCms\Cms\Http\Controllers\Utilities\ProjectConfigController;
use CraftCms\Cms\Http\Controllers\Utilities\SystemMessagesController;
use CraftCms\Cms\Http\Controllers\Utilities\UtilitiesController;
use CraftCms\Cms\Http\Middleware\EnsureTwoFactorChallengeIsRecent;
use CraftCms\Cms\Http\Middleware\RequireAdmin;
use CraftCms\Cms\Http\Middleware\RequireAdminChanges;
use CraftCms\Cms\Http\Middleware\RequireEdition;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

use function CraftCms\Cms\cp_url;

/**
 * Admin requests that do not require a login
 */
Route::allowDuringMaintenance()->prefix('install')->group(function () {
    Route::get('/', [InstallController::class, 'index']);
    Route::post('/', [InstallController::class, 'install']);
    Route::post('validate-db', [InstallController::class, 'validateDb']);
    Route::post('validate-account', [InstallController::class, 'validateAccount']);
    Route::post('validate-site', [InstallController::class, 'validateSite']);
});

Route::allowDuringMaintenance()->prefix('updates')->name('updates.')->group(function () {
    Route::post('/', [UpdaterController::class, 'index'])->name('index');
    Route::post(UpdaterController::ACTION_FORCE_UPDATE, [UpdaterController::class, 'forceUpdate'])->name('force-update');
    Route::post(UpdaterController::ACTION_BACKUP, [UpdaterController::class, 'backup'])->name('backup');
    Route::post(UpdaterController::ACTION_SERVER_CHECK, [UpdaterController::class, 'serverCheck'])->name('server-check');
    Route::post(UpdaterController::ACTION_REVERT, [UpdaterController::class, 'revert'])->name('revert');
    Route::post(UpdaterController::ACTION_MIGRATE, [UpdaterController::class, 'migrate'])->name('migrate');
    Route::post(BaseUpdaterController::ACTION_PRECHECK, [UpdaterController::class, 'precheck'])->name('precheck');
    Route::post(BaseUpdaterController::ACTION_RECHECK_COMPOSER, [UpdaterController::class, 'recheckComposer'])->name('recheck-composer');
    Route::post(BaseUpdaterController::ACTION_COMPOSER_INSTALL, [UpdaterController::class, 'composerInstall'])->name('composer-install');
    Route::post(BaseUpdaterController::ACTION_COMPOSER_REMOVE, [UpdaterController::class, 'composerRemove'])->name('composer-remove');
    Route::post(BaseUpdaterController::ACTION_FINISH, [UpdaterController::class, 'finish'])->name('finish');
});

Route::allowDuringMaintenance()->middleware('craft.web')->group(function () {
    Route::get(CpAuthPath::Login->value, [LoginController::class, 'showLogin']);
    Route::post(CpAuthPath::Login->value, [LoginController::class, 'attemptLogin'])->middleware('throttle:'.LoginRateLimiter::NAME);
    Route::any(CpAuthPath::Logout->value, [LoginController::class, 'logout'])->name('logout');
    Route::get(CpAuthPath::TwoFactorChallenge->value, [TwoFactorAuthenticationController::class, 'showForm'])->middleware(EnsureTwoFactorChallengeIsRecent::class);
    Route::get(CpAuthPath::SetPassword->value, [SetPasswordController::class, 'show']);
    Route::post(CpAuthPath::SetPassword->value, [SetPasswordController::class, 'store']);
    Route::get(CpAuthPath::VerifyEmail->value, [VerifyEmailController::class, 'show']);
    Route::post(CpAuthPath::VerifyEmail->value, [VerifyEmailController::class, 'store']);
});

/**
 * Admin requests that require a login
 */
Route::middleware(['auth', 'can:accessCp'])->group(function () {
    Route::get('/', [DashboardController::class, 'redirect']);
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('notifications/mark-read', [NotificationsController::class, 'markRead']);

    Route::get('utilities', [UtilitiesController::class, 'index']);

    // DeprecationErrors
    Route::get('utilities/deprecation-errors/{logId}', [DeprecationErrorsController::class, 'show'])->whereNumber('logId')->name('utilities.deprecation-errors.show');
    Route::delete('utilities/deprecation-errors/{logId}', [DeprecationErrorsController::class, 'destroy']);
    Route::delete('utilities/deprecation-errors', [DeprecationErrorsController::class, 'destroyAll']);

    Route::post('utilities/migrations/apply', MigrationsController::class);
    Route::post('utilities/clear-caches', [ClearCachesController::class, 'clearCaches']);
    Route::post('utilities/clear-caches/invalidate-tags', [ClearCachesController::class, 'invalidateTags']);
    Route::post('utilities/db-backup', DbBackupController::class);
    Route::post('utilities/find-and-replace', FindAndReplaceController::class);

    Route::prefix('utilities/project-config')->group(function () {
        Route::get('diff', [ProjectConfigController::class, 'diff']);
        Route::post('rebuild', [ProjectConfigController::class, 'rebuild']);
        Route::post('discard', [ProjectConfigController::class, 'discard']);
        Route::get('download', [ProjectConfigController::class, 'download']);
    });

    Route::prefix('utilities/queue-manager')->group(function () {
        Route::post('release-all', [QueueController::class, 'cancelAll']);
        Route::post('retry-all', [QueueController::class, 'retryAll']);
        Route::post('{id}/release', [QueueController::class, 'cancel']);
        Route::post('{id}/retry', [QueueController::class, 'retry']);
    });

    // The rest of the utilities
    Route::get('utilities/{id}/{extra?}', [UtilitiesController::class, 'show'])
        ->where('extra', '.*')
        ->name('utilities.show');

    // SystemMessages
    Route::get('system-messages/{key}', [SystemMessagesController::class, 'show']);
    Route::post('system-messages', [SystemMessagesController::class, 'store']);

    Route::middleware(RequireAdminChanges::class)->group(function () {
        Route::get('settings/addresses', [AddressSettingsController::class, 'index']);
        Route::post('settings/addresses', [AddressSettingsController::class, 'store']);
    });

    Route::allowDuringMaintenance()->prefix('pluginstore/remove')->middleware(RequireAdminChanges::class)->group(function () {
        Route::post('/', [RemoveController::class, 'index']);
        Route::post(BaseUpdaterController::ACTION_PRECHECK, [RemoveController::class, 'precheck']);
        Route::post(BaseUpdaterController::ACTION_RECHECK_COMPOSER, [RemoveController::class, 'recheckComposer']);
        Route::post(BaseUpdaterController::ACTION_COMPOSER_INSTALL, [RemoveController::class, 'composerInstall']);
        Route::post(BaseUpdaterController::ACTION_COMPOSER_REMOVE, [RemoveController::class, 'composerRemove']);
        Route::post(BaseUpdaterController::ACTION_FINISH, [RemoveController::class, 'finish']);
    });

    /**
     * Elements
     */
    $idSlugParams = [
        'id' => '\d+',
        'slug' => '(?:-[^\/]*)',
    ];

    Route::get('preview/{id}{slug}', PreviewElementController::class)->where($idSlugParams);
    Route::get('edit/{id}{slug}', ElementRedirectController::class)->where($idSlugParams);
    Route::get('edit/{uid}', ElementRedirectController::class);
    Route::get('revisions/{id}{slug}', [ElementRevisionsController::class, 'index'])->where($idSlugParams);
    Route::get('entries/{section}/{id}{slug}/revisions', [ElementRevisionsController::class, 'index'])->where($idSlugParams);
    Route::get('content/{page}/{section}/{id}{slug}/revisions', [ElementRevisionsController::class, 'index'])->where([
        ...$idSlugParams,
        'page' => '[^\/]+',
    ]);
    Route::get('assets/edit/{id}{slug}', EditAssetController::class)->where($idSlugParams);
    Route::get('entries/{section}/{id}{slug?}', EditEntryController::class)->where($idSlugParams);
    Route::get('content/{page}/{section}/{id}{slug?}', EditEntryController::class)->where([
        ...$idSlugParams,
        'page' => '[^\/]+',
    ]);

    /**
     * Entries & Content
     */
    Route::get('entries', EntriesIndexController::class);
    Route::get('entries/{sectionHandle}', EntriesIndexController::class);
    Route::get('entries/{section}/new', CreateEntryController::class);

    Route::get('content', EntriesIndexController::class);
    // Registered before the index route, which would otherwise match
    // `content/{section}/new` with `new` as its section handle.
    Route::get('content/{section}/new', CreateEntryController::class);
    Route::get('content/{page}/{sectionHandle?}', ContentIndexController::class)
        ->name('content.index')
        ->where('page', '[^\/]+');

    /**
     * Users
     */
    Route::get('myaccount', [UsersController::class, 'edit']);
    Route::get('myaccount/addresses', [AddressesController::class, 'index']);
    Route::get('myaccount/permissions', [PermissionsController::class, 'index']);
    Route::patch('myaccount/permissions', [PermissionsController::class, 'update']);
    Route::get('myaccount/passkeys', [PasskeysController::class, 'index']);
    Route::get('myaccount/password', [PasswordController::class, 'index']);
    Route::get('myaccount/preferences', [PreferencesController::class, 'index']);
    Route::patch('myaccount/preferences', [PreferencesController::class, 'update']);
    Route::get('myaccount/sign-in-providers', [SignInProvidersController::class, 'index']);
    Route::post('myaccount/sign-in-providers/{provider}/connect', [SignInProvidersController::class, 'connect']);
    Route::delete('myaccount/sign-in-providers/{provider}', [SignInProvidersController::class, 'destroy']);

    Route::middleware([
        RequireEdition::class.':'.Edition::Team->value,
    ])->group(function () {
        Route::get('users/new', [UsersController::class, 'create']);
        Route::get('users/{userId}', [UsersController::class, 'edit'])->whereNumber('userId');
        Route::get('users/{userId}/addresses', [AddressesController::class, 'index'])->whereNumber('userId');
        Route::get('users/{userId}/permissions', [PermissionsController::class, 'index'])->whereNumber('userId');
        Route::patch('users/{userId}/permissions', [PermissionsController::class, 'update'])->whereNumber('userId');
    });

    Route::get('users/{slug?}', UsersIndexController::class)->name('users.index');

    /**
     * Assets
     */
    // Route::get('assets/edit/{id}-{filename}', EditElementController::class); - TODO
    Route::get('assets/{defaultSource?}', AssetsIndexController::class)
        ->name('assets.index')
        ->where('defaultSource', '(?!edit(?:/|$)).*');

    /**
     * Routes that require admin, but do not require admin changes
     */
    Route::middleware([
        RequireAdmin::class,
    ])->group(function () {
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
            Route::allowDuringMaintenance()->post(BaseUpdaterController::ACTION_FINISH, [ConfigSyncController::class, 'finish']);
        });

        // Index page
        Route::get('settings', SettingsIndexController::class)
            ->name('settings.index');

        // Entry types
        Route::prefix('settings/entry-types')->group(function () {
            Route::get('/', [EntryTypesController::class, 'index']);

            Route::middleware(RequireAdminChanges::class)->group(function () {
                Route::get('new', [EntryTypesController::class, 'create']);
                Route::post('/', [EntryTypesController::class, 'store']);
                Route::delete('{entryType}', [EntryTypesController::class, 'destroy']);
            });

            Route::get('{entryType}', [EntryTypesController::class, 'edit']);
        });

        // Fields
        Route::prefix('settings/fields')->group(function () {
            Route::get('/', [FieldsController::class, 'index']);
            Route::middleware(RequireAdminChanges::class)->get('edit', [FieldsController::class, 'edit']);
            Route::get('edit/{fieldId}', [FieldsController::class, 'edit'])->whereNumber('fieldId');

            Route::middleware(RequireAdminChanges::class)->group(function () {
                Route::get('new', [FieldsController::class, 'create']);
                Route::post('/', [FieldsController::class, 'store']);
                Route::delete('{fieldId}', [FieldsController::class, 'destroy'])->whereNumber('fieldId');
            });
        });

        // General
        Route::allowDuringMaintenance()
            ->get('settings/general', [GeneralSettingsController::class, 'index'])
            ->name('settings.general.index');
        Route::allowDuringMaintenance()
            ->post('settings/general', [GeneralSettingsController::class, 'store'])
            ->name('settings.general.store');

        // Email
        Route::get('settings/email', [EmailSettingsController::class, 'index'])
            ->name('settings.email.index');
        Route::post('settings/email', [EmailSettingsController::class, 'store'])
            ->middleware([RequireAdminChanges::class])
            ->name('settings.email.store');
        Route::post('settings/email/test', [EmailSettingsController::class, 'test'])
            ->name('settings.email.test');

        // GraphQL
        Route::get('graphql', GqlIndexController::class);
        Route::get('graphql/explore', GraphiqlController::class);

        Route::prefix('graphql/tokens')->name('graphql.tokens.')->group(function () {
            Route::get('/', [TokensController::class, 'index'])->name('index');
            Route::get('new', [TokensController::class, 'create'])->name('create');
            Route::get('{tokenId}', [TokensController::class, 'edit'])->whereNumber('tokenId')->name('edit');
            Route::post('generate', [TokensController::class, 'generate'])->name('generate');

            Route::middleware('password.confirm')->group(function () {
                Route::post('/', [TokensController::class, 'store'])->name('store');
                Route::patch('{tokenId}', [TokensController::class, 'update'])->whereNumber('tokenId')->name('update');
                Route::post('{tokenId}/access-token', [TokensController::class, 'accessToken'])->whereNumber('tokenId')->name('accessToken');
            });
        });

        Route::middleware(RequireAdminChanges::class)->group(function () {
            Route::prefix('graphql/schemas')->name('graphql.schemas.')->group(function () {
                Route::get('/', [SchemasController::class, 'index'])->name('index');
                Route::get('new', [SchemasController::class, 'create'])->name('create');
                Route::get('{schemaId}', [SchemasController::class, 'edit'])->where('schemaId', 'public|\d+')->name('edit');
                Route::delete('{schemaId}', [SchemasController::class, 'destroy'])->whereNumber('schemaId')->name('destroy');

                Route::middleware('password.confirm')->group(function () {
                    Route::post('/', [SchemasController::class, 'store'])->name('store');
                    Route::patch('{schemaId}', [SchemasController::class, 'update'])->where('schemaId', 'public|\d+')->name('update');
                });
            });

            Route::delete('graphql/tokens/{tokenId}', [TokensController::class, 'destroy'])->whereNumber('tokenId')->name('graphql.tokens.destroy');
        });

        // Plugins
        Route::prefix('settings/plugins')->group(function () {
            Route::get('/', [PluginsController::class, 'index']);

            Route::middleware(RequireAdminChanges::class)->group(function () {
                Route::post('{handle}/install', [PluginsController::class, 'install']);
                Route::post('{handle}/uninstall', [PluginsController::class, 'uninstall']);
                Route::post('{handle}/enable', [PluginsController::class, 'enable']);
                Route::post('{handle}/disable', [PluginsController::class, 'disable']);
                Route::post('{handle}/switch-edition', [PluginsController::class, 'switchEdition']);
                Route::post('{handle}/render-form', [PluginsController::class, 'renderSettingsForm']);
                Route::post('{handle}', [PluginsController::class, 'saveSettings']);
            });

            Route::get('{handle}', [PluginsController::class, 'editSettings']);
        });
        Route::get('plugin-store{any?}', [PluginStoreController::class, 'index'])->where('any', '.*');

        // Rebrand
        // @TODO: Remove when rebrand assets are refactored
        Route::get('rebrand/{type}/{filename}', function (string $type, string $filename) {
            abort_unless(in_array($type, ['icon', 'logo']), 404);

            $file = Storage::disk('rebrand')->path("$type/$filename");

            abort_unless(file_exists($file), 404);

            return response()->file($file);
        })->where('filename', '.*');

        // Routes
        Route::prefix('settings/routes')->name('settings.routes.')->group(function () {
            Route::get('/', [RoutesController::class, 'index'])->name('index');
            Route::get('{uid}', [RoutesController::class, 'edit'])->name('edit');

            Route::middleware(RequireAdminChanges::class)->group(function () {
                Route::get('new', [RoutesController::class, 'create'])->name('create');
                Route::post('/', [RoutesController::class, 'store'])->name('store');
                Route::patch('{uid}', [RoutesController::class, 'update'])->name('update');
                Route::delete('{uid}', [RoutesController::class, 'destroy'])->name('destroy');
                Route::post('reorder', [RoutesController::class, 'reorder'])->name('reorder');
            });
        });

        // Sections
        Route::get('settings/sections', [SectionsController::class, 'index'])
            ->name('settings.sections.index');
        Route::middleware(RequireAdminChanges::class)->group(function () {
            Route::get('settings/sections/new', [SectionsController::class, 'create']);
            Route::post('settings/sections/render-form', [SectionsController::class, 'renderForm']);
        });
        Route::get('settings/sections/{section}', [SectionsController::class, 'edit']);
        Route::middleware(RequireAdminChanges::class)->delete('settings/sections/{section}', [SectionsController::class, 'destroy']);
        Route::middleware(RequireAdminChanges::class)->post('sections/sections', [SectionsController::class, 'store']);

        // Volumes
        Route::prefix('settings/assets')->group(function () {
            Route::get('/', [VolumesController::class, 'index']);

            Route::prefix('volumes')->group(function () {
                Route::middleware(RequireAdminChanges::class)->get('new', [VolumesController::class, 'create']);
                Route::get('{volumeId}', [VolumesController::class, 'edit'])->whereNumber('volumeId');

                Route::middleware(RequireAdminChanges::class)->group(function () {
                    Route::post('form', [VolumesController::class, 'renderForm']);
                    Route::delete('{volumeId}', [VolumesController::class, 'destroy'])->whereNumber('volumeId');
                    Route::post('/', [VolumesController::class, 'store']);
                });
            });
        });

        // Transforms
        Route::prefix('settings/assets/transforms')->name('settings.assets.transforms.')->group(function () {
            Route::get('/', [ImageTransformsController::class, 'index'])->name('index');

            Route::middleware(RequireAdminChanges::class)->group(function () {
                Route::get('new', [ImageTransformsController::class, 'create'])->name('create');
                Route::post('/', [ImageTransformsController::class, 'store']);
                Route::post('form', [ImageTransformsController::class, 'renderForm']);
                Route::delete('{transformId}', [ImageTransformsController::class, 'destroy'])->name('destroy');
            });

            Route::get('{transformHandle}', [ImageTransformsController::class, 'edit'])->name('edit');
        });

        Route::prefix('settings/assets/transformers')->name('settings.assets.transformers.')->group(function () {
            Route::get('/', [AssetTransformersController::class, 'index'])->name('index');

            Route::middleware(RequireAdminChanges::class)->group(function () {
                Route::get('new', [AssetTransformersController::class, 'create'])->name('create');
                Route::post('/', [AssetTransformersController::class, 'store']);
                Route::post('form', [AssetTransformersController::class, 'renderForm']);
                Route::delete('{handle}', [AssetTransformersController::class, 'destroy'])->name('destroy');
            });

            Route::get('{handle}', [AssetTransformersController::class, 'edit'])->name('edit');
        });

        // Sites
        Route::get('settings/sites', [SitesController::class, 'index'])
            ->name('settings.sites.index');
        Route::middleware(RequireAdminChanges::class)
            ->group(function () {
                Route::get('settings/sites/new', [SitesController::class, 'create']);
                Route::post('settings/sites/form', [SitesController::class, 'renderForm']);
                Route::post('settings/sites/reorder', [SitesController::class, 'reorder']);
                Route::post('settings/sites', [SitesController::class, 'store']);
                Route::delete('settings/sites/{site}', [SitesController::class, 'destroy']);
            });
        Route::get('settings/sites/{site}', [SitesController::class, 'edit']);

        // Site Groups
        Route::middleware(RequireAdminChanges::class)
            ->group(function () {
                Route::post('settings/site-groups', [SiteGroupsController::class, 'store']);
                Route::delete('settings/site-groups/{groupId}', [SiteGroupsController::class, 'destroy'])
                    ->name('settings.site-groups.destroy');
            });

        // User settings index
        if (Edition::isAtLeast(Edition::Team)) {
            Route::get('settings/users', [UserGroupsController::class, 'index']);
        } else {
            Route::get('settings/users', fn () => redirect(cp_url('settings/users/fields')));
        }
        Route::get('settings/users/fields', [UserFieldsController::class, 'index']);
        Route::middleware(RequireAdminChanges::class)
            ->post('settings/users/fields', [UserFieldsController::class, 'store']);

        // User groups
        Route::middleware([RequireEdition::class.':'.Edition::Team->value])->group(function () {
            Route::middleware([
                RequireEdition::class.':'.Edition::Pro->value,
                RequireAdminChanges::class,
            ])->group(function () {
                Route::get('settings/users/groups/new', [UserGroupsController::class, 'create']);
                Route::post('settings/users/groups', [UserGroupsController::class, 'store'])->whereNumber('groupId');
                Route::delete('settings/users/groups/{groupId}', [UserGroupsController::class, 'destroy'])->whereNumber('groupId');
            });
            Route::get('settings/users/groups/{userGroup}', [UserGroupsController::class, 'edit'])
                ->name('settings.users.groups.edit');
        });

        // User settings
        Route::get('settings/users/settings', [UserSettingsController::class, 'index'])->name('settings.users.index');
        Route::middleware(RequireAdminChanges::class)->group(function () {
            Route::post('settings/users/settings/render-form', [UserSettingsController::class, 'renderForm']);
            Route::post('settings/users/settings', [UserSettingsController::class, 'store']);
        });
    });

    Route::prefix('settings/filesystems')->group(function () {
        Route::middleware([
            RequireAdmin::class,
        ])->group(function () {
            Route::get('/', [FilesystemsController::class, 'index']);
            Route::get('new', [FilesystemsController::class, 'create']);
            Route::get('{handle}', [FilesystemsController::class, 'edit']);
            Route::get('{handle}/edit', [FilesystemsController::class, 'edit']);
        });

        Route::middleware([
            RequireAdminChanges::class,
        ])->group(function () {
            Route::post('/', [FilesystemsController::class, 'store']);
            Route::post('form', [FilesystemsController::class, 'renderForm']);
            Route::delete('{handle}', [FilesystemsController::class, 'destroy']);
        });
    });
});
