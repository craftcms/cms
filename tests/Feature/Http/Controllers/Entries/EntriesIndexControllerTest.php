<?php

declare(strict_types=1);

use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\User\Elements\User;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('requires login', function () {
    get(cp_url('content'))->assertRedirect(cp_url('login'));
});

it('redirects to the first page', function () {
    actingAs(User::find()->one());

    expect(get(cp_url('content')))->assertRedirect(cp_url('content/entries'));
    expect(get(cp_url('entries')))->assertRedirect(cp_url('content/entries'));
});

it('redirects a section handle to its content index page', function () {
    actingAs(User::find()->one());

    Section::factory()->create(['handle' => 'blog']);

    get(cp_url('entries/blog'))
        ->assertRedirectContains(Sections::getSectionByHandle('blog')->getCpIndexUri());
});

it('falls back to the first page for unknown section handles', function () {
    actingAs(User::find()->one());

    get(cp_url('entries/nope'))
        ->assertRedirect(cp_url('content/entries'));
});

it('redirects the singles handle to the singles source page', function () {
    actingAs(User::find()->one());

    get(cp_url('entries/singles'))
        ->assertRedirect(cp_url('content/entries/singles'));
});
