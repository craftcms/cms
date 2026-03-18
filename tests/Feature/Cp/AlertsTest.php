<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Alerts;
use CraftCms\Cms\Cp\Events\RegisterCpAlerts;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;

it('returns no alerts for guests', function () {
    auth()->logout();

    $alerts = app(Alerts::class)->get();

    expect($alerts)->toBeArray()->toBeEmpty();
});

it('merges alerts from RegisterCpAlerts listeners', function () {
    actingAs(User::findOne());

    Event::listen(function (RegisterCpAlerts $event) {
        $event->alerts[] = 'custom alert from event';
    });

    $alerts = app(Alerts::class)->get(path: 'utilities/updates');
    $contents = array_map(fn (string|array $alert) => is_array($alert) ? $alert['content'] : $alert, $alerts);

    expect(implode(' ', $contents))->toContain('custom alert from event');
});
