<?php

declare(strict_types=1);

use CraftCms\Cms\User\Models\User;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

beforeEach(function () {
    actingAs(User::first());
});

it('renders the control panel’s own page for a CP error', function () {
    get(cp_url('settings/sections/999999'))
        ->assertNotFound()
        ->assertSee('<title>Page Not Found</title>', escape: false)
        ->assertSee(cp_url('dashboard'));
});

it('leaves JSON requests alone', function () {
    getJson(cp_url('settings/sections/999999'))
        ->assertNotFound()
        ->assertJsonStructure(['message']);
});
