<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Http\Controllers\Elements\PreviewElementController;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Crypt;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    actingAs(User::findOne());
});

it('renders the edit screen for the draft, to open in Live Preview', function () {
    $entry = EntryModel::factory()->createElement([
        'title' => 'Canonical Title',
        'slug' => 'canonical-title',
    ]);

    $draft = app(Drafts::class)->createDraft($entry, auth()->id(), name: 'Preview Draft');
    $draft->title = 'Draft Title';
    Elements::saveElement($draft);

    get(action(PreviewElementController::class, [
        'id' => $entry->id,
        'slug' => "-$entry->slug",
        'draftId' => $draft->draftId,
    ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('elements/Edit')
            ->where('title', 'Draft Title')
            ->where('draftId', $draft->draftId)
            ->where('standalonePreview.redirect', fn (string $redirect) => Crypt::decrypt($redirect) === ElementHelper::postEditUrl($draft))
        );
});

it('leaves for a signed return url once saved', function () {
    $entry = EntryModel::factory()->createElement([
        'title' => 'Canonical Title',
        'slug' => 'canonical-title',
    ]);

    get(action(PreviewElementController::class, [
        'id' => $entry->id,
        'slug' => "-$entry->slug",
        'returnUrl' => Crypt::encrypt('entries'),
    ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('standalonePreview.redirect', fn (string $redirect) => Crypt::decrypt($redirect) === 'entries')
        );
});

it('renders the edit screen for revisions', function () {
    $entry = EntryModel::factory()->createElement([
        'title' => 'Current Title',
        'slug' => 'current-title',
    ]);

    $revisionElementId = app(Revisions::class)->createRevision($entry, auth()->id());
    $revision = Elements::getElementById($revisionElementId, Entry::class, $entry->siteId);

    get(action(PreviewElementController::class, [
        'id' => $entry->id,
        'slug' => "-$entry->slug",
        'revisionId' => $revision->revisionId,
    ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('elements/Edit')
            ->where('elementId', $revision->id)
            ->has('standalonePreview.redirect')
        );
});

it('redirects to the canonical edit url when the requested draft is invalid', function () {
    $entry = EntryModel::factory()->createElement([
        'title' => 'Canonical Title',
        'slug' => 'canonical-title',
    ]);

    get(action(PreviewElementController::class, [
        'id' => $entry->id,
        'slug' => "-$entry->slug",
        'draftId' => 999999,
    ]))->assertRedirect($entry->getCpEditUrl());
});

it('works without the slug, as Craft 5’s route did', function () {
    $entry = EntryModel::factory()->createElement([
        'title' => 'Canonical Title',
        'slug' => 'canonical-title',
    ]);

    get(action(PreviewElementController::class, ['id' => $entry->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('standalonePreview'));
});

it('returns a bad request when no element matches the preview request', function () {
    get(action(PreviewElementController::class, [
        'id' => 999999,
        'slug' => '-missing',
    ]))->assertBadRequest();
});
