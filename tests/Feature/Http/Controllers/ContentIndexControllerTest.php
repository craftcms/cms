<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\User\Elements\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    actingAs(User::find()->one());
    $this->cpTrigger = Cms::config()->cpTrigger;
});

it('returns an Inertia response with elements and pagination', function () {
    EntryModel::factory()->count(3)->create();

    get("/{$this->cpTrigger}/content/entries")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('content/Index')
            ->has('elements')
            ->has('pagination')
            ->has('sort')
            ->has('sources')
        );
});

it('paginates elements via query params', function () {
    EntryModel::factory()->count(5)->create();

    get("/{$this->cpTrigger}/content/entries?".http_build_query(['per_page' => 2, 'page' => 1]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('content/Index')
            ->where('pagination.per_page', 2)
            ->where('pagination.total', 5)
            ->where('pagination.current_page', 1)
        );
});

it('accepts sort parameters', function () {
    EntryModel::factory()->createElement(['title' => 'Zebra']);
    EntryModel::factory()->createElement(['title' => 'Apple']);

    get("/{$this->cpTrigger}/content/entries?".http_build_query([
        'sort' => [['field' => 'title', 'direction' => 'asc']],
    ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sort.0.field', 'title')
            ->where('sort.0.direction', 'asc')
        );
});

it('defaults to dateCreated desc sort', function () {
    EntryModel::factory()->create();

    get("/{$this->cpTrigger}/content/entries")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sort.0.field', 'dateCreated')
            ->where('sort.0.direction', 'desc')
        );
});

it('clamps per_page to minimum of 1', function () {
    EntryModel::factory()->create();

    get("/{$this->cpTrigger}/content/entries?per_page=0")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('pagination.per_page', 1)
        );
});
