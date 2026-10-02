<?php

declare(strict_types=1);

use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    actingAs(User::findOne());
});

it('gives a utility screen breadcrumbs the Vue breadcrumbs can follow', function () {
    // Crumbs are `Cp\Data\ActionItem`s, which spell the link `url`. These
    // were hand-written arrays keyed `url` while the Vue breadcrumbs read
    // `href`, so "Utilities" rendered as plain text and led nowhere.
    get('/admin/utilities/deprecation-errors')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('utilities/Show')
            ->where('crumbs', fn (Collection $crumbs): bool => $crumbs
                ->every(fn (array $crumb): bool => ! array_key_exists('url', $crumb))
            )
            ->where('crumbs.0.href', fn (?string $href): bool => is_string($href) && str_ends_with($href, '/utilities'))
            ->etc()
        );
});

it('leaves the utilities nav to the main navigation', function () {
    // The utilities hang off the Utilities item in `Cp\Navigation` now, so the
    // page no longer describes a nav of its own — and without a `subnav` prop
    // the shell draws no secondary sidebar beside the utility.
    get('/admin/utilities/deprecation-errors')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->missing('subnav')
            ->etc()
        );
});
