<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Release\Enums\ReleaseStatus;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Tests\TestClasses\Release\ReleaseQueryFixture;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->entry = EntryModel::factory()->create();

    actingAs(User::findOne());
});

test('drafts', function () {
    EntryModel::factory()->create();

    $element = Elements::getElementById($this->entry->id);
    $draft = app(Drafts::class)->createDraft($element);

    expect(entryQuery()->drafts()->count())->toBe(1);
    expect(entryQuery()->drafts(null)->count())->toBe(3);
    expect(entryQuery()->drafts(false)->count())->toBe(2);
    expect(entryQuery()->drafts()->pluck('id'))->toContain($draft->id);
    expect(entryQuery()->draftId($element->draftId)->first())->not()->toBeNull();
});

test('releaseId returns active member drafts and preserves site and status criteria', function () {
    $drafts = app(Drafts::class);
    $userId = User::findOne()->id;

    $memberCanonical = EntryModel::factory()->createElement(['title' => 'Member']);
    refreshQueryTestEntrySources();
    $member = $drafts->createDraft($memberCanonical, $userId);

    $unpublished = EntryModel::factory()->createElement(['title' => 'Unpublished']);
    $unpublished->setAuthorIds([$userId]);
    refreshQueryTestEntrySources();
    expect($drafts->saveElementAsDraft($unpublished, $userId, 'Unpublished draft'))->toBeTrue()
        ->and($unpublished->getIsUnpublishedDraft())->toBeTrue();

    $pendingModel = EntryModel::factory()->pending()->title('Pending')->create();
    refreshQueryTestEntrySources();
    $pendingCanonical = EntryElement::find()->id($pendingModel->id)->status(null)->one();
    $pending = $drafts->createDraft($pendingCanonical, $userId);

    $secondSite = Site::factory()->create();
    $secondSiteModel = EntryModel::factory()->create();
    $secondSiteModel->element->siteSettings->first()->update(['siteId' => $secondSite->id]);
    $secondSiteModel->section->siteSettings->first()->update(['siteId' => $secondSite->id]);
    Sites::refreshSites();
    refreshQueryTestEntrySources();
    $secondSiteEntry = entryQuery()->id($secondSiteModel->id)->siteId($secondSite->id)->one();
    $secondSiteDraft = $drafts->createDraft($secondSiteEntry, $userId);

    $release = ReleaseQueryFixture::create([$member, $unpublished, $pending, $secondSiteDraft]);

    $nonMemberCanonical = EntryModel::factory()->createElement(['title' => 'Non-member']);
    refreshQueryTestEntrySources();
    $nonMember = $drafts->createDraft($nonMemberCanonical, $userId);
    ReleaseQueryFixture::create([$nonMember]);

    $expectedIds = [$member->id, $unpublished->id, $pending->id, $secondSiteDraft->id];
    sort($expectedIds);

    expect(entryQuery()
        ->releaseId($release->id)
        ->drafts(false)
        ->site('*')
        ->status(null)
        ->orderBy('elements.id')
        ->ids())->toBe($expectedIds)
        ->and(entryQuery()
            ->releaseId($release->id)
            ->siteId($secondSite->id)
            ->status(null)
            ->ids())->toBe([$secondSiteDraft->id])
        ->and(entryQuery()
            ->releaseId($release->id)
            ->site('*')
            ->status(EntryElement::STATUS_PENDING)
            ->ids())->toBe([$pending->id]);
});

test('releaseId only matches releases that have not been published', function (ReleaseStatus $status, bool $matches) {
    $canonical = EntryModel::factory()->createElement();
    refreshQueryTestEntrySources();
    $member = app(Drafts::class)->createDraft($canonical, User::findOne()->id);
    $release = ReleaseQueryFixture::create([$member], $status);

    expect(entryQuery()->releaseId($release->id)->status(null)->ids())
        ->toBe($matches ? [$member->id] : []);
})->with([
    'draft' => [ReleaseStatus::Draft, true],
    'scheduled' => [ReleaseStatus::Scheduled, true],
    'publishing' => [ReleaseStatus::Publishing, true],
    'failed' => [ReleaseStatus::Failed, true],
    'published' => [ReleaseStatus::Published, false],
]);

test('releaseId null clears the release criterion without resetting draft selection', function () {
    $drafts = app(Drafts::class);
    $userId = User::findOne()->id;
    $memberCanonical = EntryModel::factory()->createElement();
    refreshQueryTestEntrySources();
    $member = $drafts->createDraft($memberCanonical, $userId);
    $nonMemberCanonical = EntryModel::factory()->createElement();
    refreshQueryTestEntrySources();
    $nonMember = $drafts->createDraft($nonMemberCanonical, $userId);
    $release = ReleaseQueryFixture::create([$member]);

    $ids = entryQuery()
        ->releaseId($release->id)
        ->releaseId()
        ->status(null)
        ->ids();

    expect($ids)->toEqualCanonicalizing([$member->id, $nonMember->id]);
});

test('draftOf', function () {

    $element = Elements::getElementById($this->entry->id);

    app(Drafts::class)->createDraft($element);

    expect(entryQuery()->draftOf($element->id)->count())->toBe(1);
    expect(entryQuery()->draftOf([$element->id])->count())->toBe(1);
    expect(entryQuery()->draftOf([$element])->count())->toBe(1);
    expect(entryQuery()->draftOf($element)->count())->toBe(1);
    expect(entryQuery()->draftOf($element->id + 1)->count())->toBe(0);

    $this->expectException(InvalidArgumentException::class);
    entryQuery()->draftOf('foo')->count();
});

test('draftCreator', function () {
    $user = User::find()->one();

    $element = Elements::getElementById($this->entry->id);
    app(Drafts::class)->createDraft($element, $user->id);

    expect(entryQuery()->draftCreator($user->id)->count())->toBe(1);
    expect(entryQuery()->draftCreator($user)->count())->toBe(1);
    expect(entryQuery()->draftCreator($user->id + 1)->count())->toBe(0);
});

test('provisionalDrafts', function () {
    $element = Elements::getElementById($this->entry->id);
    app(Drafts::class)->createDraft($element, provisional: true);

    expect(entryQuery()->drafts()->count())->toBe(0);
    expect(entryQuery()->drafts()->provisionalDrafts()->count())->toBe(1);
    expect(entryQuery()->drafts()->provisionalDrafts(null)->count())->toBe(1);
});

test('canonicalsOnly', function () {
    $element = Elements::getElementById($this->entry->id);
    app(Drafts::class)->createDraft($element);

    expect(entryQuery()->canonicalsOnly()->count())->toBe(1);
    expect(entryQuery()->drafts(null)->canonicalsOnly()->count())->toBe(1);
    expect(entryQuery()->drafts()->canonicalsOnly()->count())->toBe(0);
});

test('savedDraftsOnly', function () {
    $element = Elements::getElementById($this->entry->id);
    app(Drafts::class)->createDraft($element);

    expect(entryQuery()->savedDraftsOnly()->count())->toBe(1);
});

test('revisions', function () {
    EntryModel::factory()->create();

    $element = Elements::getElementById($this->entry->id);
    $revision = app(Revisions::class)->createRevision($element);

    expect(entryQuery()->revisions()->count())->toBe(1);
    expect(entryQuery()->revisions(null)->count())->toBe(3);
    expect(entryQuery()->revisions(false)->count())->toBe(2);
    expect(entryQuery()->revisions()->pluck('id'))->toContain($revision);
    expect(entryQuery()->revisionId($element->revisionId)->first())->not()->toBeNull();
    expect(entryQuery()->revisionOf($element)->first())->not()->toBeNull();
});

test('revisionCreator', function () {
    $user = User::find()->one();

    $element = Elements::getElementById($this->entry->id);
    app(Revisions::class)->createRevision($element, $user->id);

    expect(entryQuery()->revisionCreator($user->id)->count())->toBe(1);
    expect(entryQuery()->revisionCreator($user)->count())->toBe(1);
    expect(entryQuery()->revisionCreator($user->id + 1)->count())->toBe(0);
});

function refreshQueryTestEntrySources(): void
{
    Sections::refreshSections();
    EntryTypes::refreshEntryTypes();
}
