<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Events\RegisterCpAlerts;
use CraftCms\Cms\Http\Controllers\App\CpAlertsController;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

test('get cp alerts validates required path', function () {
    getJson(action([CpAlertsController::class, 'index']))
        ->assertJsonValidationErrors(['path']);
});

test('get cp alerts returns alerts for the requested path', function () {
    Event::listen(function (RegisterCpAlerts $event) {
        $event->alerts[] = 'Test alert';
    });

    getJson(action([CpAlertsController::class, 'index'], ['path' => 'utilities/updates']))
        ->assertOk()
        ->assertJsonPath('alerts.0.content', 'Test alert')
        ->assertJsonPath('alerts.0.showIcon', true);
});

test('shun cp alert validates required message', function () {
    postJson(action([CpAlertsController::class, 'destroy']))
        ->assertJsonValidationErrors(['message']);
});

test('shun cp alert stores the shunned message for the current user', function () {
    postJson(action([CpAlertsController::class, 'destroy']), [
        'message' => 'Hide me',
    ])->assertOk();

    expect(app(Users::class)->hasUserShunnedMessage(auth()->id(), 'Hide me'))->toBeTrue();
});
