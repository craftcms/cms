<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Responses\CpErrorPage;
use CraftCms\Cms\User\Models\User;
use Illuminate\Http\Request;

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

it('leaves site requests to the site’s error templates', function () {
    expect(CpErrorPage::render(new RuntimeException, Request::create('/not-the-cp')))->toBeNull();
});

it('hides an unexpected exception’s message', function () {
    config(['app.debug' => false]);

    $response = CpErrorPage::render(new RuntimeException('secret details'), Request::create(cp_url('foo')));

    expect($response->getStatusCode())->toBe(500)
        ->and($response->getContent())->toContain('Internal Server Error')
        ->and($response->getContent())->not->toContain('secret details');
});

it('leaves unexpected exceptions to the debug page in debug mode', function () {
    config(['app.debug' => true]);

    expect(CpErrorPage::render(new RuntimeException, Request::create(cp_url('foo'))))->toBeNull();
});
