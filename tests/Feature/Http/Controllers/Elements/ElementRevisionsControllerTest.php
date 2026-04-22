<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Http\Controllers\Elements\ElementRevisionsController;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    actingAs(User::findOne());

    $this->entryType = EntryType::factory()->create();
    $this->section = Section::factory()->withEntryTypes($this->entryType)->create([
        'handle' => 'blog',
        'enableVersioning' => true,
    ]);
});

dataset('elementRevisionRoutes', [
    'generic revisions route' => [
        fn (Entry $entry) => action([ElementRevisionsController::class, 'index'], [
            'id' => $entry->id,
            'slug' => "-$entry->slug",
        ]),
    ],
    'entries revisions route' => [
        fn (Entry $entry) => cp_url(sprintf(
            'entries/%s/%d-%s/revisions',
            $entry->getSection()->handle,
            $entry->id,
            $entry->slug,
        )),
    ],
    'content revisions route' => [
        fn (Entry $entry) => (function () use ($entry) {
            [, $page, $section] = explode('/', $entry->getSection()->getCpIndexUri());

            return action([ElementRevisionsController::class, 'index'], [
                'page' => $page,
                'section' => $section,
                'id' => $entry->id,
                'slug' => "-$entry->slug",
            ]);
        })(),
    ],
]);

it('requires login for each control panel revisions route', function (Closure $route) {
    $entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement([
            'title' => 'My Entry',
            'slug' => 'my-entry',
        ]);

    Auth::logout();

    get($route($entry))->assertRedirectContains('login');
})->with('elementRevisionRoutes');

it('renders the revisions screen for each control panel revisions route', function (Closure $route) {
    $timestamp = now()->startOfMinute();
    Date::setTestNow($timestamp);

    $entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement([
            'title' => 'Current Title',
            'slug' => 'current-title',
        ]);

    app(Revisions::class)->createRevision($entry, auth()->id(), 'Initial notes');

    Date::setTestNow($timestamp->copy()->addMinutes(5));

    $entry->title = 'Updated Title';
    Elements::saveElement($entry);

    Date::setTestNow();

    get($route($entry))
        ->assertOk()
        ->assertSeeText('Revisions for')
        ->assertSeeText('Updated Title')
        ->assertSee('id="revisions"', false)
        ->assertSeeText('Revision 1')
        ->assertSeeText('Initial notes');
})->with('elementRevisionRoutes');

it('returns 400 when the element type does not support revisions', function () {
    $entryType = EntryType::factory()->create();
    $section = Section::factory()->withEntryTypes($entryType)->create([
        'handle' => 'plain',
        'enableVersioning' => false,
    ]);

    $entry = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->createElement([
            'title' => 'Plain Entry',
            'slug' => 'plain-entry',
        ]);

    get(cp_url("revisions/$entry->id-$entry->slug"))->assertBadRequest();
});

it('returns 400 for unpublished drafts', function () {
    $draft = app(Entry::class);
    $draft->siteId = Sites::getPrimarySite()->id;
    $draft->sectionId = $this->section->id;
    $draft->typeId = $this->entryType->id;
    $draft->title = 'Unpublished Draft';
    $draft->slug = 'unpublished-draft';
    $draft->setAuthorIds([auth()->id()]);

    app(Drafts::class)->saveElementAsDraft($draft, auth()->id(), markAsSaved: false);

    get(cp_url("revisions/$draft->id-$draft->slug"))->assertBadRequest();
});
