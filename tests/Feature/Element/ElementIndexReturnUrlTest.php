<?php

declare(strict_types=1);

use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\ElementIndexReturnUrl;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\User\Elements\User;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    Edition::set(Edition::Pro);
    actingAs(User::findOne());
});

it('sends an element back to the index it was last viewed at', function () {
    $section = Section::factory()->create();
    $entry = EntryModel::factory()->forSection($section)->createElement();

    expect(ElementHelper::postEditUrl($entry))->toBe(cp_url('content/entries'));

    $indexUrl = cp_url("content/entries/$section->handle", ['page' => 2, 'search' => 'news']);
    get($indexUrl)->assertOk();

    expect(ElementHelper::postEditUrl($entry))->toBe($indexUrl);
});

it('remembers where a bare index lands, not the bare URL', function () {
    get(cp_url('users'))->assertRedirect();
    expect(ElementIndexReturnUrl::get(User::class))->toBeNull();

    get(cp_url('users/admins'))->assertOk();
    expect(ElementIndexReturnUrl::get(User::class))->toBe(cp_url('users/admins'));
});

it('ignores prefetched indexes', function () {
    get(cp_url('users/admins'), ['Purpose' => 'prefetch'])->assertOk();

    expect(ElementIndexReturnUrl::get(User::class))->toBeNull();
});

it('keeps each element type’s index separately', function () {
    get(cp_url('users/admins'))->assertOk();
    get(cp_url('content/entries'))->assertOk();

    expect(ElementIndexReturnUrl::get(User::class))->toBe(cp_url('users/admins'))
        ->and(ElementIndexReturnUrl::get(Entry::class))->toBe(cp_url('content/entries'));
});
