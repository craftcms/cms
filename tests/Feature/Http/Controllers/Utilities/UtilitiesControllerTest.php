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

it('describes the utilities nav instead of leaving the page to draw it', function () {
    // The page used to fill `SecondaryNav`'s default slot with its own markup,
    // which the nav's collapsed action menu — built from the items — could not
    // see, so below the large breakpoint it listed nothing at all.
    get('/admin/utilities/deprecation-errors')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('subnav', fn (Collection $subnav): bool => $subnav->isNotEmpty()
                && $subnav->every(fn (array $item): bool => isset($item['label'], $item['href']))
                && $subnav->where('selected', true)->count() === 1
                && str_ends_with(
                    $subnav->firstWhere('selected', true)['href'],
                    '/utilities/deprecation-errors',
                )
            )
            ->etc()
        );
});
