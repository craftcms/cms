<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\BaseUpdaterController;
use CraftCms\Cms\Http\Controllers\PluginStore\RemoveController;
use CraftCms\Cms\Support\Composer;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\User\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;
use function Pest\Laravel\swap;

beforeEach(function () {
    actingAs(User::first());

    $this->hashedData = \Craft::$app->getSecurity()->hashData(Json::encode([
        'packageName' => 'craftcms/test-plugin',
    ]));
});

dataset('routes', [
    [RemoveController::class, 'index'],
    [RemoveController::class, 'precheck'],
    [RemoveController::class, 'recheckComposer'],
    [RemoveController::class, 'composerInstall'],
    [RemoveController::class, 'composerRemove'],
    [RemoveController::class, 'finish'],
]);

it('requires authentication, adminChanges and admin for all routes', function (string $controller, string $action) {
    auth()->logout();

    postJson(action([$controller, $action]))->assertUnauthorized();

    User::first()->update(['admin' => false]);
    actingAs(User::first());

    postJson(action([$controller, $action]))->assertForbidden();

    User::first()->update(['admin' => true]);
    actingAs(User::first());
    Cms::config()->allowAdminChanges(false);

    postJson(action([$controller, $action]))->assertForbidden();
})->with('routes');

test('all routes validate data', function (string $controller, string $action) {
    if ($action === 'index') {
        postJson(action([$controller, $action]))
            ->assertJsonValidationErrors([
                'packageName',
            ]);

        return;
    }

    postJson(action([$controller, $action]), [
        'data' => 'invalid-data',
    ])->assertJsonValidationErrors([
        'data',
    ]);
})->with('routes');

test('index', function () {
    postJson(action([RemoveController::class, 'index']), [
        'packageName' => 'craftcms/test-plugin',
    ])
        ->assertSee('composer-remove') // next action
        ->assertSee('Plugin Uninstaller')
        ->assertSee('Craft.updater');
});

test('composer-remove', function () {
    // Fake the composer uninstall call
    swap(Composer::class, new class extends Composer
    {
        #[\Override]
        public function uninstall(array $packages, ?callable $callback = null): void
        {
            $callback('', '');
        }
    });

    postJson(action([RemoveController::class, 'composerRemove']), [
        'data' => $this->hashedData,
    ])->assertJsonFragment([
        'status' => 'The plugin was removed successfully.',
        'nextAction' => BaseUpdaterController::ACTION_FINISH,
    ]);
});

test('finish', function () {
    postJson(action([RemoveController::class, 'finish']), [
        'data' => $this->hashedData,
    ])->assertJsonFragment([
        'finished' => true,
        'returnUrl' => 'settings/plugins',
    ]);
});
