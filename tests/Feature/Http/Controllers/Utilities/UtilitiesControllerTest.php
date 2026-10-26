<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Controllers\Utilities\UtilitiesController;
use CraftCms\Cms\Queue\JobProgress;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

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

it('titles a utility subpage, with a breadcrumb back to the utility', function () {
    app(JobProgress::class)->queued('job-1', 'Updating search indexes');

    get('/admin/utilities/queue-manager/job-1')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('utilities/Show')
            ->where('title', 'Updating search indexes')
            ->has('crumbs', 3)
            ->where('crumbs.1.label', 'Queue Manager')
            ->where('crumbs.1.href', fn (?string $href): bool => is_string($href) && str_ends_with($href, '/utilities/queue-manager'))
            ->where('crumbs.2.label', 'Updating search indexes')
            ->where('crumbs.2.href', null)
            ->etc()
        );

    get('/admin/utilities/queue-manager')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('title', 'Queue Manager')
            ->has('crumbs', 2)
            ->where('crumbs.1.href', null)
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

it('returns the nav badge counts for each utility and the Utilities item', function () {
    DB::table('deprecationerrors')->insert([
        'key' => 'test-deprecation',
        'message' => 'Test deprecation message',
        'fingerprint' => md5('test-deprecation'),
        'lastOccurrence' => now(),
        'file' => __FILE__,
        'dateCreated' => now(),
        'dateUpdated' => now(),
        'uid' => Str::uuid(),
    ]);

    postJson(action([UtilitiesController::class, 'badgeCount']))
        ->assertOk()
        ->assertJsonPath('badgeCounts.nav-utilities-deprecation-errors', 1)
        ->assertJsonPath('badgeCounts.nav-utilities', 1);
});
