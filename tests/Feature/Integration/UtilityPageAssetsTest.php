<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    actingAs(User::find()->one());

    $this->cpTrigger = Cms::config()->cpTrigger;

    Edition::set(Edition::Pro);
});

/**
 * Utility pages used to drain the whole HtmlStack into their own Inertia page
 * props, which also swallowed the global CP asset bundle that
 * HandleInertiaRequests registers for every CP request. That left the root
 * template without jQuery, Garnish or the legacy CP bundle, so the page died
 * on `$ is not defined` before the Inertia app could mount.
 */
it('leaves the global CP asset bundle in the root template', function (string $id) {
    $html = get("/{$this->cpTrigger}/utilities/{$id}")
        ->assertOk()
        ->getContent();

    // The legacy bundle and its jQuery dependency are body-end JS files…
    expect($html)
        ->toContain('legacy/cp/dist/cp.js')
        ->toContain('legacy/jquery/dist/jquery.js');

    // …while the `window.Craft` config it reads is head JS. It has to land in
    // the root template's <head>, ahead of the module scripts that boot the
    // Inertia CP.
    expect(Str::between($html, '<head>', '</head>'))
        ->toContain('window.Craft = Object.assign');
})->with(['system-report', 'php-info', 'deprecation-errors']);

it('keeps a utility’s own assets on the page props', function () {
    // A utility's own registrations belong on the page props, where
    // AppLayout's `useAppendHtml()` applies them — on the initial render and
    // on subsequent Inertia visits, which never re-render the root template.
    get("/{$this->cpTrigger}/utilities/system-report")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('headHtml')
            ->has('bodyHtml')
            ->etc());
});
