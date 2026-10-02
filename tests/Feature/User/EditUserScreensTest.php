<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Data\NavItem;
use CraftCms\Cms\Edition;
use CraftCms\Cms\User\EditUserScreens;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Edition::set(Edition::Pro);
    actingAs(User::findOne());

    // The account, selected the way the edit screens select it — the password
    // is what decides whether the Account Security screens are offered.
    $this->user = User::find()->addSelect(['users.password'])->one();
});

test('subnav is a list, at both levels', function () {
    $subnav = app(EditUserScreens::class)->subnav($this->user, EditUserScreens::PERMISSIONS);

    // The CP shell types this as `NavItem[]` and decides whether to draw the
    // secondary nav from the item count, so string keys would hide it.
    expect(array_is_list($subnav))->toBeTrue()
        ->and($subnav)->each->toBeInstanceOf(NavItem::class);

    $group = collect($subnav)->firstWhere('group', true);

    expect($group)->not->toBeNull()
        ->and(array_is_list($group->subnav))->toBeTrue()
        ->and($group->subnav)->each->toBeInstanceOf(NavItem::class);
});

test('the selected screen is marked, along with the group holding it', function () {
    $screens = app(EditUserScreens::class);

    $subnav = $screens->subnav($this->user, EditUserScreens::PASSWORD);
    $group = collect($subnav)->firstWhere('group', true);

    expect($group->selected)->toBeTrue()
        ->and(collect($subnav)->firstWhere('selected', true))->toBe($group)
        ->and(collect($group->subnav)->firstWhere('selected', true)->url)
        ->toBe($screens->url($this->user, EditUserScreens::PASSWORD));
});
