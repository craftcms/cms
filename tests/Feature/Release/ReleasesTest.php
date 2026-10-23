<?php

declare(strict_types=1);

use CraftCms\Cms\Activity\Activities;
use CraftCms\Cms\Activity\Data\ActivitySubject;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Events\DraftApplying;
use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Lightswitch;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout as FieldLayoutModel;
use CraftCms\Cms\Release\Activity\ReleaseDraftPublished;
use CraftCms\Cms\Release\Enums\ReleaseStatus;
use CraftCms\Cms\Release\Events\ReleasePublished;
use CraftCms\Cms\Release\Exceptions\ReleaseException;
use CraftCms\Cms\Release\Notifications\ReleaseFailed;
use CraftCms\Cms\Release\Releases;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Section\Models\SectionSiteSettings;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\UserPermissions;
use CraftCms\Cms\Tests\TestClasses\Release\DraftableReleaseElement;
use CraftCms\Cms\Tests\TestClasses\Release\ReleasePermissions;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\User\Models\UserGroup;
use CraftCms\Cms\Workflow\Data\WorkflowStageContext;
use CraftCms\Cms\Workflow\Enums\WorkflowStatus;
use CraftCms\Cms\Workflow\Enums\WorkflowTransition;
use CraftCms\Cms\Workflow\Models\Workflow;
use CraftCms\Cms\Workflow\UserReview\UserReviewDecision;
use CraftCms\Cms\Workflow\UserReview\UserReviewStage;
use CraftCms\Cms\Workflow\Workflows;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->actor = User::findOne();
    $this->releases = app(Releases::class);

    actingAs($this->actor);
});

afterEach(fn () => Date::setTestNow());

it('publishes changed disabled and unpublished entries together and retains immutable history', function () {
    Event::fake([ReleasePublished::class]);
    Date::setTestNow('2026-10-01 12:00:00');

    $secondSite = Site::factory()->create();
    Sites::refreshSites();
    $entryType = EntryType::factory()->create();
    $section = Section::factory()->withEntryTypes($entryType)->withSites($secondSite)->create();
    Sections::refreshSections();
    EntryTypes::refreshEntryTypes();

    $changedCanonical = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->createElement(['title' => 'Original changed entry']);
    $changedCanonical->setAuthorIds([$this->actor->id]);
    expect(Elements::saveElement($changedCanonical))->toBeTrue();

    Date::setTestNow(now()->addSecond());

    $changedDraft = releaseDraft($changedCanonical, 'Changed for launch', $this->actor);
    $changedDraftForSecondSite = EntryElement::find()
        ->draftId($changedDraft->draftId)
        ->siteId($secondSite->id)
        ->status(null)
        ->one();
    $changedDraftForSecondSite->title = 'Changed for second site launch';
    expect(Elements::saveElement($changedDraftForSecondSite))->toBeTrue();

    $disabledCanonical = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->createElement(['title' => 'Original enabled entry']);
    $disabledCanonical->setAuthorIds([$this->actor->id]);
    expect(Elements::saveElement($disabledCanonical))->toBeTrue();

    Date::setTestNow(now()->addSecond());

    $disabledDraft = releaseDraft($disabledCanonical, 'Disabled for launch', $this->actor);
    $disabledDraft->enabled = false;
    expect(Elements::saveElement($disabledDraft))->toBeTrue();

    $unpublishedDraft = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->createElement(['title' => 'Brand new launch entry']);
    $unpublishedDraft->setAuthorIds([$this->actor->id]);
    expect(Elements::saveElement($unpublishedDraft))->toBeTrue();
    expect(app(Drafts::class)->saveElementAsDraft(
        $unpublishedDraft,
        $this->actor->id,
        'New launch entry',
    ))->toBeTrue();

    $release = $this->releases->create('October launch', null, $this->actor);
    $items = collect([$changedDraft, $disabledDraft, $unpublishedDraft])
        ->map(fn (EntryElement $draft) => $this->releases->attach($release, $draft, $this->actor));
    $draftIds = $items->pluck('draftId')->all();

    $published = $this->releases->publish($release, $this->actor);

    $changedEntries = EntryElement::find()->id($changedCanonical->id)->site('*')->status(null)->all();
    $changedEntriesBySite = collect($changedEntries)->keyBy('siteId');
    $disabledEntries = EntryElement::find()->id($disabledCanonical->id)->site('*')->status(null)->all();
    $newEntries = EntryElement::find()->id($unpublishedDraft->id)->drafts(false)->site('*')->status(null)->all();
    $history = $published->fresh()->load('items');

    expect($published->status)->toBe(ReleaseStatus::Published)
        ->and($published->publishedAt)->not()->toBeNull()
        ->and($changedEntries)->toHaveCount(2)
        ->and($changedEntriesBySite[$changedDraft->siteId]->title)->toBe('Changed for launch')
        ->and($changedEntriesBySite[$secondSite->id]->title)->toBe('Changed for second site launch')
        ->and($disabledEntries)->toHaveCount(2)
        ->and(collect($disabledEntries)->every(fn (EntryElement $entry): bool => ! $entry->enabled))->toBeTrue()
        ->and($newEntries)->toHaveCount(2)
        ->and($history->items)->toHaveCount(3)
        ->and($history->items->pluck('draftId')->all())->toBe([null, null, null])
        ->and($history->items->every(fn ($item): bool => $item->publishedElementId !== null && $item->publishedAt !== null))->toBeTrue()
        ->and(DB::table(Table::DRAFTS)->whereIn('id', $draftIds)->exists())->toBeFalse()
        ->and(fn () => $this->releases->update($published, 'Renamed launch', null, $this->actor))
        ->toThrow(ReleaseException::class, 'Scheduled and completed releases cannot be changed.')
        ->and(fn () => $this->releases->delete($published, $this->actor))
        ->toThrow(ReleaseException::class, 'Scheduled and completed releases cannot be changed.');

    Event::assertDispatched(fn (ReleasePublished $event): bool => $event->release->is($published));
});

it('rolls back every member and notifies release managers when a later draft fails during publication', function () {
    Event::fake([ReleasePublished::class]);
    Notification::fake();
    $editor = UserModel::factory()->createElement();

    $firstCanonical = EntryModel::factory()->createElement(['title' => 'First original']);
    $firstDraft = releaseDraft($firstCanonical, 'First release change', $this->actor);
    $secondCanonical = EntryModel::factory()->createElement(['title' => 'Second original']);
    $secondDraft = releaseDraft($secondCanonical, 'Second release change', $this->actor);

    $release = $this->releases->create('Atomic release', null, $this->actor);
    $firstItem = $this->releases->attach($release, $firstDraft, $this->actor);
    $secondItem = $this->releases->attach($release, $secondDraft, $this->actor);

    Event::listen(DraftApplying::class, function (DraftApplying $event) use ($secondDraft): void {
        if ($event->draft->draftId === $secondDraft->draftId) {
            throw new RuntimeException('The second member failed.');
        }
    });

    expect(fn () => $this->releases->publish($release, $this->actor))
        ->toThrow(RuntimeException::class, 'The second member failed.');

    $failed = $release->fresh()->load('items');
    $firstAfterFailure = EntryElement::find()->id($firstCanonical->id)->status(null)->one();
    $secondAfterFailure = EntryElement::find()->id($secondCanonical->id)->status(null)->one();

    expect($firstAfterFailure->title)->toBe('First original')
        ->and($secondAfterFailure->title)->toBe('Second original')
        ->and(EntryElement::find()->draftId($firstDraft->draftId)->status(null)->one())->not()->toBeNull()
        ->and(EntryElement::find()->draftId($secondDraft->draftId)->status(null)->one())->not()->toBeNull()
        ->and($failed->status)->toBe(ReleaseStatus::Failed)
        ->and($failed->lastError)->toBe('The second member failed.')
        ->and($failed->publishedAt)->toBeNull()
        ->and($failed->publishAttempts)->toBe(1)
        ->and($failed->items->pluck('draftId')->all())->toBe([$firstItem->draftId, $secondItem->draftId])
        ->and($failed->items->pluck('publishedElementId')->filter())->toBeEmpty();

    Event::assertNotDispatched(ReleasePublished::class);
    Notification::assertSentTo(
        $this->actor,
        ReleaseFailed::class,
        fn (ReleaseFailed $notification): bool => $notification->toDatabase($this->actor)['message']
            === 'The release “Atomic release” could not be published. The second member failed.'
            && $notification->toDatabase($this->actor)['url'] === route('craft.cp.releases.show', $release),
    );
    Notification::assertNotSentTo($editor, ReleaseFailed::class);
});

it('publishes an approved draft with Matrix content without resetting its approval', function () {
    Edition::set(Edition::Pro);

    $secondSite = Site::factory()->create();
    Sites::refreshSites();
    $blockText = Field::factory()->create(['handle' => 'blockText', 'type' => PlainText::class]);
    // A field the draft leaves unset, so it takes its default when the draft is validated for publication.
    $topBorder = Field::factory()->create(['handle' => 'topBorder', 'type' => Lightswitch::class, 'settings' => ['default' => true]]);
    $blockType = EntryType::factory()->withFieldLayout(FieldLayoutModel::factory()->withContentTab([
        new CustomField(config: ['fieldUid' => $blockText->uid]),
        new CustomField(config: ['fieldUid' => $topBorder->uid]),
    ]))->create(['hasTitleField' => false, 'titleFormat' => '{id}']);
    $blocks = Field::factory()->create(['handle' => 'blocks', 'type' => Matrix::class, 'settings' => ['entryTypes' => [$blockType->id]]]);
    $entryType = EntryType::factory()->withFieldLayout(FieldLayoutModel::factory()->forField($blocks))->create();
    $section = Section::factory()->withEntryTypes($entryType)->withSites($secondSite)->create();
    Sections::refreshSections();
    EntryTypes::refreshEntryTypes();
    Fields::refreshFields();

    $reviewer = UserModel::factory()->admin()->createElement();
    $group = UserGroup::factory()->create();
    $group->users()->sync([$reviewer->id]);
    $stageUid = Str::uuid7()->toString();
    $workflow = Workflow::query()->create([
        'name' => 'Editorial review',
        'uid' => Str::uuid7()->toString(),
        'stages' => [[
            'uid' => $stageUid,
            'name' => 'Review',
            'type' => UserReviewStage::class,
            'settings' => ['approvalsRequired' => 1, 'approvalMode' => 'total', 'userGroups' => [$group->uid]],
        ]],
    ]);
    $section->update(['workflowId' => $workflow->id]);
    Sections::refreshSections();

    $entry = new EntryElement;
    $entry->sectionId = $section->id;
    $entry->typeId = $entryType->id;
    $entry->title = 'Launch announcement';
    $entry->setAuthorIds([$this->actor->id]);
    $entry->setFieldValue('blocks', [
        'sortOrder' => ['new1'],
        'entries' => ['new1' => ['type' => $blockType->handle, 'fields' => ['blockText' => 'Doors open at ten.']]],
    ]);
    expect(app(Drafts::class)->saveElementAsDraft($entry, $this->actor->id, 'Launch'))->toBeTrue();

    // A person approves it, so a reset would leave it pending rather than re-approving automatically.
    $workflows = app(Workflows::class);
    $approve = function () use ($workflows, $entry, $reviewer, $stageUid): void {
        actingAs($reviewer);
        $workflows->reportStageResult(
            runId: $workflows->latestRun($entry)->id,
            stageUid: $stageUid,
            result: fn (WorkflowStageContext $context) => $context->stage->component()
                ->decide(UserReviewDecision::Approved, null, $context, $reviewer),
            actor: $reviewer,
            activityTransition: WorkflowTransition::Approve,
        );
        actingAs($this->actor);
    };
    $workflows->submitForReview($entry);
    $approve();
    expect($workflows->latestRun($entry)->status)->toBe(WorkflowStatus::Approved);

    // Editing a block is still a change to the draft, which needs approving again.
    $block = $entry->getFieldValue('blocks')->one();
    $block->setFieldValue('blockText', 'Doors open at nine.');
    expect(Elements::saveElement($block))->toBeTrue()
        ->and($workflows->latestRun($entry)->status)->toBe(WorkflowStatus::Pending);
    $approve();

    $release = $this->releases->create('Launch', null, $this->actor);
    $this->releases->attach($release, $entry, $this->actor);
    $published = $this->releases->publish($release, $this->actor);

    $canonical = EntryElement::find()->id($entry->getCanonicalId())->status(null)->one();

    expect($published->status)->toBe(ReleaseStatus::Published)
        ->and($canonical->getIsDraft())->toBeFalse()
        ->and($canonical->getFieldValue('blocks')->one()?->getFieldValue('blockText'))->toBe('Doors open at nine.');
});

it('rejects an unpublished member with a conflicting URI during publication without disabling it', function () {
    $entryType = EntryType::factory()->create();
    $section = Section::factory()->withEntryTypes($entryType)->create();
    SectionSiteSettings::query()
        ->where('sectionId', $section->id)
        ->update([
            'hasUrls' => true,
            'uriFormat' => 'fixed-release-uri',
        ]);
    Sections::refreshSections();

    $conflictingEntry = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->createElement([
            'title' => 'Existing live entry',
            'slug' => 'existing-live-entry',
        ]);
    expect(Elements::saveElement($conflictingEntry))->toBeTrue();

    $unpublishedDraft = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->createElement([
            'title' => 'Conflicting unpublished entry',
            'slug' => 'conflicting-unpublished-entry',
        ]);
    expect(app(Drafts::class)->saveElementAsDraft(
        $unpublishedDraft,
        $this->actor->id,
        'Conflicting unpublished draft',
    ))->toBeTrue();

    $release = $this->releases->create('Strict URI release', null, $this->actor);
    $item = $this->releases->attach($release, $unpublishedDraft, $this->actor);

    expect(fn () => $this->releases->publish($release, $this->actor))
        ->toThrow(ReleaseException::class, 'Could not generate a unique URI based on the URI format.');

    $failedRelease = $release->fresh();
    $savedDraft = EntryElement::find()->draftId($unpublishedDraft->draftId)->status(null)->one();
    $savedConflict = EntryElement::find()->id($conflictingEntry->id)->status(null)->one();

    expect($failedRelease->status)->toBe(ReleaseStatus::Failed)
        ->and($failedRelease->publishedAt)->toBeNull()
        ->and($item->fresh()->draftId)->toBe($unpublishedDraft->draftId)
        ->and($item->fresh()->publishedElementId)->toBeNull()
        ->and($savedDraft)->not()->toBeNull()
        ->and($savedDraft->enabled)->toBeTrue()
        ->and($savedDraft->getEnabledForSite())->toBeTrue()
        ->and($savedConflict->enabled)->toBeTrue()
        ->and($savedConflict->getEnabledForSite())->toBeTrue();
});

// Like applying a draft, publishing only validates what the draft changes on other sites, as in Craft 5.
it('publishes a draft whose other site misses a required value it leaves unchanged', function () {
    $secondSite = Site::factory()->create();
    Sites::refreshSites();
    $field = Field::factory()->create([
        'handle' => 'releaseSummary',
        'type' => PlainText::class,
        'translationMethod' => 'site',
    ]);
    $entryType = EntryType::factory()->withField($field, required: true)->create();
    $section = Section::factory()->withEntryTypes($entryType)->withSites($secondSite)->create();
    Sections::refreshSections();
    EntryTypes::refreshEntryTypes();

    $canonical = EntryModel::factory()->forSection($section)->forEntryType($entryType)->createElement(['title' => 'Live title']);
    $canonical->setFieldValue('releaseSummary', 'Primary summary');
    expect(Elements::saveElement($canonical))->toBeTrue();
    $secondLive = EntryElement::find()->id($canonical->id)->siteId($secondSite->id)->status(null)->one();
    $secondLive->setFieldValue('releaseSummary', '');
    expect(Elements::saveElement($secondLive, runValidation: false))->toBeTrue();

    $draft = app(Drafts::class)->createDraft($canonical, $this->actor->id);
    $draft->title = 'Released title';
    expect(Elements::saveElement($draft))->toBeTrue();

    $release = $this->releases->create('Translated release', null, $this->actor);
    $this->releases->attach($release, $draft, $this->actor);

    $this->releases->publish($release, $this->actor);

    expect(EntryElement::find()->id($canonical->id)->status(null)->one()->title)->toBe('Released title')
        ->and(EntryElement::find()->id($canonical->id)->siteId($secondSite->id)->status(null)->one()->getFieldValue('releaseSummary'))->toBeNull();
});

it('publishes only due scheduled releases after the scheduling user loses every permission', function () {
    Date::setTestNow('2026-10-01 12:00:00');

    $entryType = EntryType::factory()->create();
    $section = Section::factory()->withEntryTypes($entryType)->create();
    $scheduler = UserModel::factory()
        ->withPermissions(['manageReleases', 'publishReleases'])
        ->createElement();

    $lateDraft = releaseDraft(
        EntryModel::factory()->forSection($section)->forEntryType($entryType)->createElement(['title' => 'Late original']),
        'Late scheduled change',
        $this->actor,
    );
    $lateRelease = $this->releases->create('Late release', null, $this->actor);
    $this->releases->attach($lateRelease, $lateDraft, $this->actor);
    expect($scheduler->can('saveCanonical', $lateDraft))->toBeFalse();

    $lateRelease = $this->releases->schedule($lateRelease, now()->addMinute(), $scheduler);

    $futureDraft = releaseDraft(
        EntryModel::factory()->forSection($section)->forEntryType($entryType)->createElement(['title' => 'Future original']),
        'Future scheduled change',
        $this->actor,
    );
    $futureRelease = $this->releases->create('Future release', null, $this->actor);
    $this->releases->attach($futureRelease, $futureDraft, $this->actor);
    $futureRelease = $this->releases->schedule($futureRelease, now()->addHour(), $scheduler);

    $unscheduledDraft = releaseDraft(
        EntryModel::factory()->forSection($section)->forEntryType($entryType)->createElement(['title' => 'Unscheduled original']),
        'Unscheduled change',
        $this->actor,
    );
    $unscheduledRelease = $this->releases->create('Unscheduled release', null, $this->actor);
    $this->releases->attach($unscheduledRelease, $unscheduledDraft, $this->actor);

    UserPermissions::saveUserPermissions($scheduler->id, []);
    expect($scheduler->can('manageReleases'))->toBeFalse()
        ->and($scheduler->can('publishReleases'))->toBeFalse();

    Date::setTestNow('2026-10-01 12:05:00');

    expect($this->releases->publishDue())->toBe(1)
        ->and($lateRelease->fresh()->status)->toBe(ReleaseStatus::Published)
        ->and($futureRelease->fresh()->status)->toBe(ReleaseStatus::Scheduled)
        ->and($unscheduledRelease->fresh()->status)->toBe(ReleaseStatus::Draft)
        ->and(EntryElement::find()->id($lateDraft->getCanonicalId())->status(null)->one()->title)->toBe('Late scheduled change')
        ->and(EntryElement::find()->id($futureDraft->getCanonicalId())->status(null)->one()->title)->toBe('Future original')
        ->and(EntryElement::find()->id($unscheduledDraft->getCanonicalId())->status(null)->one()->title)->toBe('Unscheduled original');
});

it('requires publication permission to cancel a scheduled release', function () {
    Date::setTestNow('2026-10-01 12:00:00');

    $draft = releaseDraft(EntryModel::factory()->createElement(['title' => 'Original']), 'Scheduled change', $this->actor);
    $release = $this->releases->create('Scheduled release', null, $this->actor);
    $this->releases->attach($release, $draft, $this->actor);
    $release = $this->releases->schedule($release, now()->addMinute(), $this->actor);
    $manager = UserModel::factory()->withPermissions(['manageReleases'])->createElement();
    $publisher = UserModel::factory()->withPermissions(['manageReleases', 'publishReleases'])->createElement();

    expect(fn () => $this->releases->unschedule($release, $manager))
        ->toThrow(HttpException::class, 'You are not allowed to publish releases.');

    expect($release->fresh()->status)->toBe(ReleaseStatus::Scheduled);

    $cancelled = $this->releases->unschedule($release, $publisher);

    expect($cancelled->status)->toBe(ReleaseStatus::Draft)
        ->and($cancelled->publishedAt)->toBeNull()
        ->and($cancelled->scheduledById)->toBeNull();

    Date::setTestNow('2026-10-01 12:05:00');

    expect($this->releases->publishDue())->toBe(0)
        ->and(EntryElement::find()->id($draft->getCanonicalId())->status(null)->one()->title)->toBe('Original');
});

it('keeps publishing due releases after an earlier one fails', function () {
    Date::setTestNow('2026-10-01 12:00:00');

    $failingDraft = releaseDraft(EntryModel::factory()->createElement(['title' => 'Failing original']), 'Failing change', $this->actor);
    $failingRelease = $this->releases->create('Failing release', null, $this->actor);
    $this->releases->attach($failingRelease, $failingDraft, $this->actor);
    $this->releases->schedule($failingRelease, now()->addMinute(), $this->actor);

    $laterDraft = releaseDraft(EntryModel::factory()->createElement(['title' => 'Later original']), 'Later change', $this->actor);
    $laterRelease = $this->releases->create('Later release', null, $this->actor);
    $this->releases->attach($laterRelease, $laterDraft, $this->actor);
    $this->releases->schedule($laterRelease, now()->addMinutes(2), $this->actor);

    Event::listen(DraftApplying::class, function (DraftApplying $event) use ($failingDraft): void {
        if ($event->draft->draftId === $failingDraft->draftId) {
            throw new RuntimeException('The failing member failed.');
        }
    });

    Date::setTestNow('2026-10-01 12:05:00');

    expect($this->releases->publishDue())->toBe(1)
        ->and($failingRelease->fresh()->status)->toBe(ReleaseStatus::Failed)
        ->and($failingRelease->fresh()->lastError)->toBe('The failing member failed.')
        ->and($laterRelease->fresh()->status)->toBe(ReleaseStatus::Published)
        ->and(EntryElement::find()->id($failingDraft->getCanonicalId())->status(null)->one()->title)->toBe('Failing original')
        ->and(EntryElement::find()->id($laterDraft->getCanonicalId())->status(null)->one()->title)->toBe('Later change');
});

it('checks readiness without saving the draft or changing its content and dates', function () {
    Date::setTestNow('2026-10-01 12:00:00');

    $canonical = EntryModel::factory()->createElement(['title' => 'Original title']);
    $draft = releaseDraft($canonical, 'Prepared title', $this->actor);
    $draft->postDate = now()->addDay();
    expect(Elements::saveElement($draft))->toBeTrue();

    $release = $this->releases->create('Readiness release', null, $this->actor);
    $item = $this->releases->attach($release, $draft, $this->actor);
    $savedDraft = EntryElement::find()->draftId($draft->draftId)->status(null)->one();
    $savedDateUpdated = $savedDraft->dateUpdated;
    $savedPostDate = $savedDraft->postDate;

    Date::setTestNow('2026-10-01 12:05:00');

    $live = EntryElement::find()->id($canonical->id)->status(null)->one();
    $live->slug = 'updated-live-slug';
    expect(Elements::saveElement($live))->toBeTrue();

    $readiness = $this->releases->checkReadiness($release, $this->actor);
    $draftAfterReadinessCheck = EntryElement::find()->draftId($draft->draftId)->status(null)->one();

    expect($readiness->ready)->toBeTrue()
        ->and($readiness->itemWarnings[$item->id])->toContain('This entry will remain pending in at least one site at the release time.')
        ->and($draftAfterReadinessCheck->title)->toBe('Prepared title')
        ->and($draftAfterReadinessCheck->slug)->toBe($savedDraft->slug)
        ->and($draftAfterReadinessCheck->postDate->equalTo($savedPostDate))->toBeTrue()
        ->and($draftAfterReadinessCheck->dateUpdated->equalTo($savedDateUpdated))->toBeTrue();
});

function releaseDraft(EntryElement $canonical, string $title, User $actor): EntryElement
{
    Sections::refreshSections();
    EntryTypes::refreshEntryTypes();

    $draft = app(Drafts::class)->createDraft(
        canonical: $canonical,
        creatorId: $actor->id,
        name: "$title draft",
    );
    $draft->title = $title;
    $draft->setAuthorIds([$actor->id]);

    if (! Elements::saveElement($draft)) {
        throw new RuntimeException('The release test draft could not be saved.');
    }

    return $draft;
}

it('allows a release publisher without content permissions to publish a saved non-entry draft', function () {
    $model = Element::factory()->title('Original content')->create(['type' => DraftableReleaseElement::class]);
    $canonical = DraftableReleaseElement::find()->id($model->id)->one();
    $draft = app(Drafts::class)->createDraft($canonical, $this->actor->id, 'Prepared content');
    $draft->title = 'Published content';
    expect(Elements::saveElement($draft))->toBeTrue();

    $release = $this->releases->create('Mixed content', null, $this->actor);
    $this->releases->attach($release, $draft, $this->actor);
    $publisher = UserModel::factory()
        ->withPermissions(['manageReleases', 'publishReleases'])
        ->createElement();

    expect($publisher->can('save', $draft))->toBeFalse()
        ->and($this->releases->checkReadiness($release, $publisher)->ready)->toBeTrue();

    $published = $this->releases->publish($release, $publisher);

    expect($published->status)->toBe(ReleaseStatus::Published)
        ->and(DraftableReleaseElement::find()->id($canonical->id)->one()?->title)->toBe('Published content')
        ->and(DraftableReleaseElement::find()->draftId($draft->draftId)->status(null)->one())->toBeNull();
});

it('merges live changes into untouched fields while publishing the release draft changes', function () {
    Date::setTestNow('2026-10-01 12:00:00');

    $canonical = EntryModel::factory()->createElement(['title' => 'Original title', 'slug' => 'original-slug']);
    $draft = releaseDraft($canonical, 'Release title', $this->actor);
    $release = $this->releases->create('Merge release', null, $this->actor);
    $item = $this->releases->attach($release, $draft, $this->actor);

    Date::setTestNow(now()->addMinute());

    $live = EntryElement::find()->id($canonical->id)->status(null)->one();
    $live->title = 'Live title';
    $live->slug = 'live-slug';
    expect(Elements::saveElement($live))->toBeTrue()
        ->and($this->releases->checkReadiness($release, $this->actor)->ready)->toBeTrue();

    $published = $this->releases->publish($release, $this->actor);
    $live = EntryElement::find()->id($canonical->id)->status(null)->one();

    expect($published->status)->toBe(ReleaseStatus::Published)
        ->and($live->title)->toBe('Release title')
        ->and($live->slug)->toBe('live-slug')
        ->and($item->fresh()->draftId)->toBeNull()
        ->and(EntryElement::find()->draftId($draft->draftId)->status(null)->one())->toBeNull();
});

it('records release publication in the activity of each published entry', function () {
    $canonical = EntryModel::factory()->createElement(['title' => 'Original title']);
    $draft = releaseDraft($canonical, 'Release title', $this->actor);
    $release = $this->releases->create('Launch <day>', null, $this->actor);
    $this->releases->attach($release, $draft, $this->actor);
    DB::table(Table::ACTIVITYEVENTS)->delete();

    $this->releases->publish($release, $this->actor);

    $event = app(Activities::class)->query()
        ->subject(ActivitySubject::fromElement($canonical))
        ->sole();

    expect($event->eventType)->toBe(ReleaseDraftPublished::class)
        ->and(app(Activities::class)->format($event)->toHtml())->toBe(sprintf(
            'Published with the <a href="%s">Launch &lt;day&gt;</a> release',
            route('craft.cp.releases.show', $release),
        ));
});

it('requires release publication permission even when a manager can publish the member', function () {
    $entryType = EntryType::factory()->create();
    $section = Section::factory()->withEntryTypes($entryType)->create();
    $canonical = EntryModel::factory()->forSection($section)->forEntryType($entryType)
        ->createElement(['title' => 'Original content']);
    $draft = releaseDraft($canonical, 'Prepared content', $this->actor);
    $manager = UserModel::factory()
        ->withPermissions(ReleasePermissions::forSection($section, manageReleases: true))
        ->createElement();
    $release = $this->releases->create('Permission release', null, $this->actor);
    $this->releases->attach($release, $draft, $this->actor);

    expect($manager->can('saveCanonical', $draft))->toBeTrue()
        ->and(fn () => $this->releases->publish($release, $manager))
        ->toThrow(ReleaseException::class, 'You are not allowed to publish releases.')
        ->and($release->fresh()->status)->toBe(ReleaseStatus::Draft)
        ->and($release->fresh()->publishAttempts)->toBe(0)
        ->and(EntryElement::find()->id($canonical->id)->status(null)->one()->title)->toBe('Original content')
        ->and(EntryElement::find()->draftId($draft->draftId)->status(null)->one())->not()->toBeNull();
});
