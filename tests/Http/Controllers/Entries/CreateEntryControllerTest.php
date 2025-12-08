<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\User\Elements\User;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

beforeEach(function () {
    actingAs(User::find()->firstOrFail());
});

it('requires login', function () {
    \Illuminate\Support\Facades\Auth::logout();

    get(cp_url('entries/pages/new'))->assertRedirect(cp_url('login'));
});

it('requires a valid section handle', function () {
    get(cp_url('entries/non-existent-handle/new'))->assertBadRequest();
});

it('requires a valid site when passed', function () {
    Section::factory()->create([
        'handle' => 'blog',
    ]);

    get(cp_url('entries/blog/new').'?siteId=999')->assertBadRequest();
});

it('creates a draft and redirects to it', function () {
    $section = Section::factory()->create([
        'handle' => 'blog',
    ]);
    $section->entryTypes()->save(EntryType::factory()->create(), ['sortOrder' => 1]);

    get(cp_url('entries/blog/new'))
        ->assertStatus(302)
        ->assertRedirectContains(cp_url('content/entries/blog/'))
        ->assertRedirectContains('draftId')
        ->assertRedirectContains('fresh=1');

    getJson(cp_url('entries/blog/new'))
        ->assertJsonStructure([
            'cpEditUrl',
            'modelName',
            'modelClass',
            'entry',
            'modelId',
            'message',
        ]);
});
