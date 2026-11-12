<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
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
 * HandleInertiaRequests registers for every CP request. Its assets belong in
 * the root template, which takes the `window.Craft` config from the page data
 * rather than repeating it.
 */
it('leaves the global CP asset bundle in the root template', function (string $id) {
    $html = get("/{$this->cpTrigger}/utilities/{$id}")
        ->assertOk()
        ->getContent();

    // The legacy Craft.js bundle, and the jQuery and Garnish it needs, come
    // with the Yii2 adapter, not core…
    expect($html)
        ->toContain('legacy/theme/dist/')
        ->not->toContain('legacy/jquery/dist/jquery.js')
        ->not->toContain('legacy/cpcompat/dist/craft.js');

    // …and the config reaches the page once, as the `craft.general` prop.
    expect($html)->not->toContain('window.Craft = Object.assign')
        ->and(substr_count($html, '"asciiCharMap":'))->toBe(1);
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
