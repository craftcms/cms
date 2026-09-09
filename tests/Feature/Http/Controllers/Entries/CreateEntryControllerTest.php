<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

beforeEach(function () {
    actingAs(User::find()->one());
});

it('requires login', function () {
    Auth::logout();

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
    $section = Section::factory()->withEntryTypes(EntryType::factory()->create())->create([
        'handle' => 'blog',
    ]);

    get(cp_url('entries/blog/new'))->assertFound()
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

it('does not assign authors when the section disallows them', function () {
    $section = Section::factory()->withEntryTypes(EntryType::factory()->create())->create([
        'handle' => 'solo',
        'minAuthors' => 0,
        'maxAuthors' => 0,
    ]);

    $response = getJson(cp_url('entries/solo/new'))
        ->assertOk()
        ->json();

    $draft = Elements::getElementById($response['modelId']);

    expect($draft->sectionId)->toBe($section->id)
        ->and($draft->getAuthors())->toBeEmpty();
});
