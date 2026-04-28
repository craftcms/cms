<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
    app()->maintenanceMode()->activate([]);
});

afterEach(function () {
    app()->maintenanceMode()->deactivate();
});

test('non-exempted routes return 503 during maintenance mode', function () {
    $cpTrigger = Cms::config()->cpTrigger;
    $actionTrigger = Cms::config()->actionTrigger;

    $response = postJson("/{$cpTrigger}/{$actionTrigger}/app/api-headers");

    expect($response->status())->toBe(503);
});

test('migrate action route is accessible during maintenance mode', function () {
    $actionTrigger = Cms::config()->actionTrigger;

    $response = post("/{$actionTrigger}/migrate");

    expect($response->status())->not->toBe(503);
});
