<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\PluginStore\RemoveController;
use CraftCms\Cms\Support\Composer;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Crypt;
use Inertia\Testing\AssertableInertia;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;
use function Pest\Laravel\swap;

beforeEach(function () {
    actingAs(User::find()->one());

    $this->hashedData = Crypt::encrypt(Json::encode([
        'packageName' => 'craftcms/test-plugin',
    ]));
});

dataset('routes', [
    'index',
    'precheck',
    'recheckComposer',
    'composerInstall',
    'composerRemove',
    'finish',
]);

it('uses normal CP routes', function (string $action) {
    expect(parse_url(action([RemoveController::class, $action]), PHP_URL_PATH))
        ->toStartWith(parse_url(cp_url('pluginstore/remove'), PHP_URL_PATH));
})->with('routes');

it('requires authentication, adminChanges and admin for all routes', function (string $action) {
    auth()->logout();

    postJson(action([RemoveController::class, $action]))->assertUnauthorized();

    CraftCms\Cms\User\Models\User::first()->update(['admin' => false]);
    actingAs(User::find()->one());

    postJson(action([RemoveController::class, $action]))->assertForbidden();

    CraftCms\Cms\User\Models\User::first()->update(['admin' => true]);
    actingAs(User::find()->one());
    Cms::config()->allowAdminChanges(false);

    postJson(action([RemoveController::class, $action]))->assertForbidden();
})->with('routes');

test('all routes validate data', function (string $action) {
    if ($action === 'index') {
        postJson(action([RemoveController::class, $action]))
            ->assertJsonValidationErrors([
                'packageName',
            ]);

        return;
    }

    postJson(action([RemoveController::class, $action]), [
        'data' => 'invalid-data',
    ])->assertJsonValidationErrors([
        'data',
    ]);
})->with('routes');

test('index', function () {
    postJson(action([RemoveController::class, 'index']), [
        'packageName' => 'craftcms/test-plugin',
    ])
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('updater/Index')
            ->where('initialState.nextUrl', action([RemoveController::class, 'precheck']))
            ->where('initialState.finishUrl', action([RemoveController::class, 'finish']))
        );
});

test('composer-remove', function () {
    // Fake the composer uninstall call
    swap(Composer::class, new class extends Composer
    {
        #[Override]
        public function uninstall(array $packages, ?callable $callback = null): void
        {
            $callback('', '');
        }
    });

    postJson(action([RemoveController::class, 'composerRemove']), [
        'data' => $this->hashedData,
    ])->assertJsonFragment([
        'status' => 'The plugin was removed successfully.',
        'nextUrl' => action([RemoveController::class, 'finish']),
    ]);
});

test('finish', function () {
    postJson(action([RemoveController::class, 'finish']), [
        'data' => $this->hashedData,
    ])->assertJsonFragment([
        'finished' => true,
        'returnUrl' => \CraftCms\Cms\cp_url('settings/plugins'),
    ]);
});
