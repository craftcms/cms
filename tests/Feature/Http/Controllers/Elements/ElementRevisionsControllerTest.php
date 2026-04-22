<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Enums\ElementActivityType;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Http\Controllers\Elements\ElementRevisionsController;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

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
        fn (Entry $entry) => cp_url(sprintf(
            '%s/%d-%s/revisions',
            $entry->getSection()->getCpIndexUri(),
            $entry->id,
            $entry->slug,
        )),
    ],
]);

describe('index', function () {
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
});

describe('revert', function () {
    it('requires login', function () {
        Auth::logout();

        postJson(action([ElementRevisionsController::class, 'revert']))->assertUnauthorized();
    });

    it('returns 400 when no revision is identified by the request', function () {
        $entry = EntryModel::factory()
            ->forSection($this->section)
            ->forEntryType($this->entryType)
            ->createElement([
                'title' => 'Canonical Title',
                'slug' => 'canonical-title',
            ]);

        postJson(action([ElementRevisionsController::class, 'revert']), [
            'elementType' => Entry::class,
            'elementId' => $entry->id,
            'siteId' => $entry->siteId,
        ])->assertBadRequest();
    });

    it('forbids reverting a revision when the user cannot save the canonical element', function () {
        $entry = EntryModel::factory()
            ->forSection($this->section)
            ->forEntryType($this->entryType)
            ->createElement([
                'title' => 'Canonical Title',
                'slug' => 'canonical-title',
            ]);
        /** @var Entry $revision */
        $revision = Elements::getElementById(app(Revisions::class)->createRevision($entry, auth()->id()));

        $viewer = UserModel::factory()
            ->withPermissions([
                'accessCp',
                sprintf('editSite:%s', Sites::getPrimarySite()->uid),
                sprintf('viewEntries:%s', $this->section->uid),
            ])
            ->createElement();

        actingAs($viewer);

        postJson(action([ElementRevisionsController::class, 'revert']), [
            'elementType' => Entry::class,
            'revisionId' => $revision->revisionId,
            'siteId' => $revision->siteId,
        ])->assertForbidden();
    });

    it('reverts a revision to its canonical element and tracks save activity', function () {
        $entry = EntryModel::factory()
            ->forSection($this->section)
            ->forEntryType($this->entryType)
            ->createElement([
                'title' => 'Original Title',
                'slug' => 'original-title',
            ]);
        /** @var Entry $revision */
        $revision = Elements::getElementById(app(Revisions::class)->createRevision($entry, auth()->id(), 'Initial notes'));

        $entry->title = 'Updated Title';
        $entry->slug = 'updated-title';
        Elements::saveElement($entry);

        postJson(action([ElementRevisionsController::class, 'revert']), [
            'elementType' => Entry::class,
            'revisionId' => $revision->revisionId,
            'siteId' => $revision->siteId,
        ])->assertOk()
            ->assertJsonPath('message', t('{type} reverted to past revision.', ['type' => Entry::displayName()]))
            ->assertJsonPath('element.title', 'Original Title')
            ->assertJsonPath('element.slug', 'original-title');

        /** @var Entry $canonical */
        $canonical = Entry::find()
            ->id($entry->id)
            ->siteId($entry->siteId)
            ->status(null)
            ->one();

        $activity = DB::table(Table::ELEMENTACTIVITY)->first();

        expect($canonical->title)->toBe('Original Title')
            ->and($canonical->slug)->toBe('original-title')
            ->and($activity->elementId)->toBe($entry->id)
            ->and($activity->userId)->toBe(auth()->id())
            ->and($activity->draftId)->toBeNull()
            ->and($activity->type)->toBe(ElementActivityType::Save->value);
    });
});
