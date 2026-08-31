<?php

declare(strict_types=1);

use function Pest\Laravel\get;

beforeAll(fn () => putenv('CRAFT_CP_TRIGGER='));

afterAll(fn () => putenv('CRAFT_CP_TRIGGER'));

test('public shared actions are blocked during maintenance mode', function () {
    auth()->logout();
    app()->maintenanceMode()->activate([]);

    get('/actions/graphql/api?query=%7B__typename%7D')
        ->assertServiceUnavailable();
});
