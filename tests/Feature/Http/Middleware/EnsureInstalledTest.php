<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\InstallController;
use CraftCms\Cms\Support\Url;
use Inertia\Testing\AssertableInertia;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

it('redirects installer page requests to the dashboard when Craft is installed', function () {
    get(cp_url('install'))->assertRedirect(Url::cpUrl('dashboard'));
});

it('allows non-installer control panel requests through when Craft is installed', function () {
    Cms::setIsInstalled();

    get(cp_url('login'))->assertOk();
});

it('allows installer requests through when Craft is not installed', function () {
    Cms::setIsInstalled(false);

    get(cp_url('install'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('Install'));

    expect(postJson(action([InstallController::class, 'validateDb']))->status())->not()->toBe(503);
    expect(postJson(action([InstallController::class, 'validateAccount']))->status())->not()->toBe(503);
    expect(postJson(action([InstallController::class, 'validateSite']))->status())->not()->toBe(503);
    expect(postJson(action([InstallController::class, 'install']))->status())->not()->toBe(503);
});

it('aborts site requests with a bare 503 when Craft is not installed', function () {
    Cms::setIsInstalled(false);

    get('/site-page')->assertServiceUnavailable();
});

it('aborts control panel requests with the Craft 5 install message when Craft is not installed outside debug mode', function () {
    Cms::setIsInstalled(false);
    config()->set('app.debug', false);

    get(cp_url('login'))->assertServiceUnavailable();
});

it('redirects control panel requests to the installer when Craft is not installed in debug mode', function () {
    Cms::setIsInstalled(false);
    config()->set('app.debug', true);

    get(cp_url('login'))->assertRedirect(action([InstallController::class, 'index']));
});
