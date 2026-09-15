<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\CachedState;

use function Pest\Laravel\get;

beforeAll(function () {
    CachedState::$cachedRoutes = null;
    putenv('CRAFT_CP_TRIGGER=');
});

afterAll(function () {
    putenv('CRAFT_CP_TRIGGER');
    CachedState::$cachedRoutes = null;
});

test('public shared actions are blocked during maintenance mode', function () {
    auth()->logout();
    app()->maintenanceMode()->activate([]);

    get('/actions/graphql/api?query=%7B__typename%7D')
        ->assertServiceUnavailable();
});
