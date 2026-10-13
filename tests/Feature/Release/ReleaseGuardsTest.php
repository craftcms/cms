<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\SessionAuth;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Element\Operations\ElementCanonicalChanges;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Http\Controllers\Elements\ElementDraftsController;
use CraftCms\Cms\Http\Controllers\NestedElementsController;
use CraftCms\Cms\Release\Enums\ReleaseStatus;
use CraftCms\Cms\Release\Exceptions\ReleaseException;
use CraftCms\Cms\Release\Releases;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Tests\TestClasses\Release\ReleasePermissions;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\User\Models\UserGroup;
use CraftCms\Cms\Workflow\Data\WorkflowStageContext;
use CraftCms\Cms\Workflow\Data\WorkflowStageResult;
use CraftCms\Cms\Workflow\Enums\WorkflowStatus;
use CraftCms\Cms\Workflow\Models\Workflow;
use CraftCms\Cms\Workflow\UserReview\UserReviewDecision;
use CraftCms\Cms\Workflow\UserReview\UserReviewStage;
use CraftCms\Cms\Workflow\Workflows;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Workbench\App\Workflow\AutomaticApprovalStage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->actor = User::findOne();
    $this->releases = app(Releases::class);

    actingAs($this->actor);
});

afterEach(fn () => Date::setTestNow());

it('keeps edits in the same scheduled release and publishes the current draft content', function () {
    Date::setTestNow('2026-10-01 12:00:00');

    $canonical = EntryModel::factory()->createElement(['title' => 'Canonical title']);
    $draft = releaseGuardDraft($canonical, 'Prepared title', $this->actor);
    $release = $this->releases->create('Editable release', null, $this->actor);
    $item = $this->releases->attach($release, $draft, $this->actor);
    $release = $this->releases->schedule($release, now()->addMinute(), $this->actor);

    $draft->title = 'Updated release content';
    expect(Elements::saveElement($draft))->toBeTrue();

    $savedDraft = EntryElement::find()->draftId($draft->draftId)->status(null)->one();
    expect($savedDraft->id)->toBe($draft->id)
        ->and($savedDraft->title)->toBe('Updated release content')
        ->and($item->fresh()->draftId)->toBe($draft->draftId)
        ->and($this->releases->releaseForDraft($savedDraft)->id)->toBe($release->id)
        ->and($release->fresh()->status)->toBe(ReleaseStatus::Scheduled);

    Date::setTestNow('2026-10-01 12:05:00');

    expect($this->releases->publishDue())->toBe(1)
        ->and($release->fresh()->status)->toBe(ReleaseStatus::Published)
        ->and(EntryElement::find()->id($canonical->id)->status(null)->one()->title)->toBe('Updated release content');
});

it('removes a draft site while retaining its release membership', function () {
    $secondSite = Site::factory()->create();
    Sites::refreshSites();
    $entryType = EntryType::factory()->create();
    $section = Section::factory()->withEntryTypes($entryType)->withSites($secondSite)->create(['propagationMethod' => PropagationMethod::Custom]);
    Sections::refreshSections();
    EntryTypes::refreshEntryTypes();

    $canonical = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->createElement(['title' => 'Multi-site canonical']);
    $canonical->setEnabledForSite([$canonical->siteId => true, $secondSite->id => true]);
    expect(Elements::saveElement($canonical))->toBeTrue();

    $draft = releaseGuardDraft($canonical, 'Multi-site prepared draft', $this->actor);
    $release = $this->releases->create('Multi-site release', null, $this->actor);
    $item = $this->releases->attach($release, $draft, $this->actor);

    $secondSiteDraft = EntryElement::find()->draftId($draft->draftId)->siteId($secondSite->id)->status(null)->one();
    $secondSiteRow = fn (): bool => DB::table(Table::ELEMENTS_SITES)
        ->where('elementId', $draft->id)
        ->where('siteId', $secondSite->id)
        ->exists();

    expect($secondSiteRow())->toBeTrue();

    Elements::deleteElementsForSite([$secondSiteDraft]);

    expect($secondSiteRow())->toBeFalse()
        ->and($item->fresh()->draftId)->toBe($draft->draftId)
        ->and(EntryElement::find()->draftId($draft->draftId)->siteId($draft->siteId)->status(null)->one())->not()->toBeNull();
});

it('blocks release and membership mutations while scheduled until a publisher unschedules it', function () {
    Date::setTestNow('2026-10-01 12:00:00');

    $draft = releaseGuardDraft(
        EntryModel::factory()->createElement(['title' => 'Scheduled original']),
        'Scheduled change',
        $this->actor,
    );
    $otherDraft = releaseGuardDraft(
        EntryModel::factory()->createElement(['title' => 'Other original']),
        'Other change',
        $this->actor,
    );
    $release = $this->releases->create('Scheduled release', null, $this->actor);
    $this->releases->attach($release, $draft, $this->actor);
    $release = $this->releases->schedule($release, now()->addHour(), $this->actor);

    expect(fn () => $this->releases->update($release, 'Renamed', null, $this->actor))
        ->toThrow(ReleaseException::class)
        ->and(fn () => $this->releases->attach($release, $otherDraft, $this->actor))
        ->toThrow(ReleaseException::class)
        ->and(fn () => $this->releases->detach($release, $draft, $this->actor))
        ->toThrow(ReleaseException::class)
        ->and(fn () => $this->releases->delete($release, $this->actor))
        ->toThrow(ReleaseException::class);

    postJson(action([ElementDraftsController::class, 'apply']), [
        'elementType' => EntryElement::class,
        'draftId' => $draft->draftId,
        'siteId' => $draft->siteId,
        'title' => 'Premature publication',
    ])->assertBadRequest();

    expect(EntryElement::find()->draftId($draft->draftId)->status(null)->one()->title)->toBe('Scheduled change')
        ->and($release->items()->count())->toBe(1)
        ->and($release->fresh()->status)->toBe(ReleaseStatus::Scheduled);

    $release = $this->releases->unschedule($release, $this->actor);
    $this->releases->detach($release, $draft, $this->actor);

    expect($release->status)->toBe(ReleaseStatus::Draft)
        ->and($release->publishedAt)->toBeNull()
        ->and($release->plannedAt)->toBeNull()
        ->and($this->releases->releaseForDraft($draft))->toBeNull();
});

it('publishes an attached draft early through the ordinary apply endpoint and detaches it', function () {
    $canonical = EntryModel::factory()->createElement([
        'title' => 'Canonical title',
        'slug' => 'canonical-title',
    ]);
    $draft = releaseGuardDraft($canonical, 'Prepared title', $this->actor);
    $release = $this->releases->create('Early publication release', null, $this->actor);
    $this->releases->attach($release, $draft, $this->actor);

    postJson(action([ElementDraftsController::class, 'apply']), [
        'elementType' => EntryElement::class,
        'draftId' => $draft->draftId,
        'siteId' => $draft->siteId,
        'title' => 'Published ahead of release',
        'slug' => 'published-ahead-of-release',
    ])->assertOk();

    $publishedEntry = EntryElement::find()->id($canonical->id)->status(null)->one();

    expect($publishedEntry->title)->toBe('Published ahead of release')
        ->and($publishedEntry->slug)->toBe('published-ahead-of-release')
        ->and(EntryElement::find()->draftId($draft->draftId)->status(null)->one())->toBeNull()
        ->and($release->items()->count())->toBe(0)
        ->and($release->fresh()->status)->toBe(ReleaseStatus::Draft);
});

it('publishes an approved workflow draft early when the apply request identifies its approval state', function () {
    Date::setTestNow('2026-10-01 12:00:00');

    $secondSite = Site::factory()->create();
    Sites::refreshSites();
    $workflow = releaseGuardAutomaticWorkflow('Early publication approval');
    $entryType = EntryType::factory()->create();
    $section = Section::factory()
        ->withEntryTypes($entryType)
        ->withSites($secondSite)
        ->create(['workflowId' => $workflow->id]);
    Sections::refreshSections();
    EntryTypes::refreshEntryTypes();
    $canonical = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->createElement(['title' => 'Workflow canonical']);
    $canonical->setAuthorIds([$this->actor->id]);
    expect(Elements::saveElement($canonical))->toBeTrue();

    Date::setTestNow('2026-10-01 12:00:01');

    $draft = releaseGuardDraft($canonical, 'Approved early publication', $this->actor);
    $draft->setEnabledForSite([
        $draft->siteId => false,
        $secondSite->id => true,
    ]);
    expect(Elements::saveElement($draft))->toBeTrue();

    $secondSiteDraft = EntryElement::find()->draftId($draft->draftId)->siteId($secondSite->id)->status(null)->one();
    $secondSiteDraft->title = 'Approved early publication';
    expect(Elements::saveElement($secondSiteDraft))->toBeTrue();

    $primaryDraft = EntryElement::find()->draftId($draft->draftId)->siteId($draft->siteId)->status(null)->one();
    $secondSiteDraft = EntryElement::find()->draftId($draft->draftId)->siteId($secondSite->id)->status(null)->one();
    expect($primaryDraft->getEnabledForSite())->toBeFalse()
        ->and($secondSiteDraft->getEnabledForSite())->toBeTrue()
        ->and($primaryDraft->title)->toBe('Approved early publication')
        ->and($secondSiteDraft->title)->toBe('Approved early publication');

    $run = app(Workflows::class)->submitForReview($secondSiteDraft);
    $release = $this->releases->create('Approved early release', null, $this->actor);
    $this->releases->attach($release, $primaryDraft, $this->actor);

    postJson(action([ElementDraftsController::class, 'apply']), [
        'elementType' => EntryElement::class,
        'draftId' => $draft->draftId,
        'siteId' => $primaryDraft->siteId,
        'title' => 'Unreviewed change',
        'enabledForSite' => true,
        'workflowRunId' => $run->id,
        'workflowCurrentStage' => 0,
    ])->assertOk();

    $publishedPrimary = EntryElement::find()->id($canonical->id)->siteId($primaryDraft->siteId)->status(null)->one();
    $publishedSecondSite = EntryElement::find()->id($canonical->id)->siteId($secondSite->id)->status(null)->one();

    expect($run->fresh()->status)->toBe(WorkflowStatus::Published)
        ->and($publishedPrimary->title)->toBe('Approved early publication')
        ->and($publishedPrimary->getEnabledForSite())->toBeFalse()
        ->and($publishedSecondSite->title)->toBe('Approved early publication')
        ->and($publishedSecondSite->getEnabledForSite())->toBeTrue()
        ->and(EntryElement::find()->draftId($draft->draftId)->status(null)->one())->toBeNull()
        ->and($release->items()->count())->toBe(0);
});

it('preserves early-publication membership and content when applying the draft fails validation', function () {
    $canonical = EntryModel::factory()->createElement([
        'title' => 'Canonical title',
        'slug' => 'canonical-title',
    ]);
    $draft = releaseGuardDraft($canonical, 'Prepared title', $this->actor);
    $release = $this->releases->create('Failed early publication release', null, $this->actor);
    $item = $this->releases->attach($release, $draft, $this->actor);

    postJson(action([ElementDraftsController::class, 'apply']), [
        'elementType' => EntryElement::class,
        'draftId' => $draft->draftId,
        'siteId' => $draft->siteId,
        'title' => '',
        'slug' => 'invalid-early-publication',
    ])->assertBadRequest()
        ->assertJsonValidationErrors('title');

    $savedCanonical = EntryElement::find()->id($canonical->id)->status(null)->one();
    $savedDraft = EntryElement::find()->draftId($draft->draftId)->status(null)->one();

    expect($savedCanonical->title)->toBe('Canonical title')
        ->and($savedCanonical->slug)->toBe('canonical-title')
        ->and($savedDraft)->not()->toBeNull()
        ->and($savedDraft->title)->toBe('Prepared title')
        ->and($item->fresh()->draftId)->toBe($draft->draftId)
        ->and($release->items()->count())->toBe(1);
});

it('offers eligible provisional drafts but requires promotion before attachment', function () {
    $entryType = EntryType::factory()->create();
    $section = Section::factory()->withEntryTypes($entryType)->create();
    $contributor = UserModel::factory()
        ->withPermissions(ReleasePermissions::forSection($section))
        ->createElement();
    $canonical = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->createElement(['title' => 'Provisional original']);
    $canonical->setAuthorIds([$contributor->id]);
    expect(Elements::saveElement($canonical))->toBeTrue();

    Sections::refreshSections();
    EntryTypes::refreshEntryTypes();

    $release = $this->releases->create('Provisional eligibility', null, $this->actor);
    actingAs($contributor);
    $provisionalDraft = app(Drafts::class)->createDraft(
        $canonical,
        $contributor->id,
        provisional: true,
    );

    expect($provisionalDraft->isProvisionalDraft)->toBeTrue()
        ->and($contributor->can('attach', [$release, $provisionalDraft]))->toBeTrue()
        ->and(fn () => $this->releases->attach($release, $provisionalDraft, $contributor))
        ->toThrow(ReleaseException::class, 'Only saved, named root drafts can be attached to a release.')
        ->and($release->items()->count())->toBe(0);

    $draftId = $provisionalDraft->draftId;
    $provisionalDraft->isProvisionalDraft = false;
    expect(Elements::saveElement($provisionalDraft))->toBeTrue();

    $savedDraft = EntryElement::find()->draftId($draftId)->status(null)->one();
    $item = $this->releases->attach($release, $savedDraft, $contributor);

    expect($savedDraft->isProvisionalDraft)->toBeFalse()
        ->and($savedDraft->draftId)->toBe($draftId)
        ->and($item->draftId)->toBe($draftId)
        ->and($release->items()->count())->toBe(1);
});

it('requires a persisted saved unpublished draft before attachment', function () {
    $entryType = EntryType::factory()->create();
    $section = Section::factory()->withEntryTypes($entryType)->create();
    $draft = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->createElement(['title' => 'Unsaved unpublished draft']);
    $draft->setAuthorIds([$this->actor->id]);
    expect(Elements::saveElement($draft))->toBeTrue();

    expect(app(Drafts::class)->saveElementAsDraft(
        $draft,
        $this->actor->id,
        'Unpublished draft',
        markAsSaved: false,
    ))->toBeTrue();

    $release = $this->releases->create('Unpublished draft release', null, $this->actor);
    $freshDraft = EntryElement::find()->draftId($draft->draftId)->status(null)->one();

    expect(fn () => $this->releases->attach($release, $freshDraft, $this->actor))
        ->toThrow(ReleaseException::class, 'Only saved, named root drafts can be attached to a release.')
        ->and($release->items()->count())->toBe(0);

    $freshDraft->markDraftAsSaved = true;
    expect(Elements::saveElement($freshDraft))->toBeTrue();

    $savedDraft = EntryElement::find()->draftId($draft->draftId)->status(null)->one();
    $item = $this->releases->attach($release, $savedDraft, $this->actor);

    expect($item->draftId)->toBe($draft->draftId)
        ->and($release->items()->count())->toBe(1);
});

it('allows contributors to attach publishable drafts but requires release publication permission to schedule', function () {
    $firstType = EntryType::factory()->create();
    $firstSection = Section::factory()->withEntryTypes($firstType)->create();
    $secondType = EntryType::factory()->create();
    $secondSection = Section::factory()->withEntryTypes($secondType)->create();

    $contributor = UserModel::factory()
        ->withPermissions(ReleasePermissions::forSection($firstSection))
        ->createElement();
    $manager = UserModel::factory()
        ->withPermissions(ReleasePermissions::forSection($firstSection, manageReleases: true))
        ->createElement();

    $firstDraft = releaseGuardDraft(
        EntryModel::factory()->forSection($firstSection)->forEntryType($firstType)->createElement(['title' => 'First original']),
        'First change',
        $contributor,
    );
    $secondDraft = releaseGuardDraft(
        EntryModel::factory()->forSection($secondSection)->forEntryType($secondType)->createElement(['title' => 'Second original']),
        'Second change',
        $this->actor,
    );
    $release = $this->releases->create('Permission release', null, $this->actor);

    $firstItem = $this->releases->attach($release, $firstDraft, $contributor);
    $this->releases->attach($release, $secondDraft, $this->actor);
    $readiness = $this->releases->checkReadiness($release, $manager);

    expect($firstItem->draftId)->toBe($firstDraft->draftId)
        ->and($contributor->can('manageReleases'))->toBeFalse()
        ->and($readiness->ready)->toBeTrue()
        ->and($readiness->itemErrors)->toBeEmpty()
        ->and($manager->can('manageReleases'))->toBeTrue();

    $scheduleException = releaseGuardException(
        fn () => $this->releases->schedule($release, now()->addHour(), $manager),
    );
    $releaseAfterSchedule = $release->fresh();

    expect($scheduleException->getStatusCode())->toBe(403)
        ->and($releaseAfterSchedule->status)->toBe(ReleaseStatus::Draft)
        ->and($releaseAfterSchedule->publishedAt)->toBeNull()
        ->and($releaseAfterSchedule->publishAttempts)->toBe(0)
        ->and($releaseAfterSchedule->lastError)->toBeNull();
});

it('requires current workflow approval when the first draft site is disabled and another is enabled', function () {
    Date::setTestNow('2026-10-01 12:00:00');

    $secondSite = Site::factory()->create();
    Sites::refreshSites();
    $reviewer = UserModel::factory()->admin()->createElement();
    $group = UserGroup::factory()->create();
    $group->users()->sync([$reviewer->id]);
    $workflow = Workflow::query()->create([
        'name' => 'Multi-site release approval',
        'uid' => Str::uuid7()->toString(),
        'stages' => [[
            'uid' => Str::uuid7()->toString(),
            'name' => 'Editorial review',
            'type' => UserReviewStage::class,
            'settings' => ['userGroups' => [$group->uid]],
        ]],
    ]);
    $entryType = EntryType::factory()->create();
    $section = Section::factory()
        ->withEntryTypes($entryType)
        ->withSites($secondSite)
        ->create(['workflowId' => $workflow->id]);
    Sections::refreshSections();
    EntryTypes::refreshEntryTypes();

    $canonical = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->createElement(['title' => 'Multi-site workflow canonical']);
    $canonical->setAuthorIds([$this->actor->id]);
    expect(Elements::saveElement($canonical))->toBeTrue();

    Date::setTestNow('2026-10-01 12:00:01');

    $draft = releaseGuardDraft($canonical, 'Multi-site approved change', $this->actor);
    $draft->setEnabledForSite([
        $draft->siteId => false,
        $secondSite->id => true,
    ]);
    expect(Elements::saveElement($draft))->toBeTrue();

    $release = $this->releases->create('Multi-site workflow release', null, $this->actor);
    $primaryDraft = EntryElement::find()->draftId($draft->draftId)->siteId($draft->siteId)->status(null)->one();
    $secondSiteDraft = EntryElement::find()->draftId($draft->draftId)->siteId($secondSite->id)->status(null)->one();

    expect($primaryDraft->getEnabledForSite())->toBeFalse()
        ->and($secondSiteDraft->getEnabledForSite())->toBeTrue()
        ->and(fn () => $this->releases->attach($release, $primaryDraft, $this->actor))
        ->toThrow(ReleaseException::class, 'complete its approval workflow')
        ->and($release->items()->count())->toBe(0);

    $workflows = app(Workflows::class);
    $run = $workflows->submitForReview($secondSiteDraft);
    $workflows->reportStageResult(
        runId: $run->id,
        stageUid: $workflow->stages->sole()->uid,
        result: fn (WorkflowStageContext $context): WorkflowStageResult => $context->stage->component()->decide(
            UserReviewDecision::Approved, null, $context, $reviewer,
        ),
        actor: $reviewer,
    );
    expect($run->fresh()->status)->toBe(WorkflowStatus::Approved);
    $item = $this->releases->attach($release, $primaryDraft, $this->actor);
    $secondSiteDraft->title = 'Edited release draft';
    expect(Elements::saveElement($secondSiteDraft))->toBeTrue();

    postJson(action([ElementDraftsController::class, 'apply']), [
        'elementType' => EntryElement::class,
        'draftId' => $draft->draftId,
        'siteId' => $primaryDraft->siteId,
        'title' => 'Unapproved early publication',
        'workflowRunId' => $run->id,
        'workflowCurrentStage' => 0,
    ])->assertBadRequest()
        ->assertJsonPath('message', 'The draft must complete its approval workflow before it can be added or published.');

    $canonicalAfterEarlyPublication = EntryElement::find()->id($canonical->id)->status(null)->one();

    expect($run->fresh()->status)->toBe(WorkflowStatus::Pending)
        ->and($canonicalAfterEarlyPublication->title)->toBe('Multi-site workflow canonical')
        ->and(EntryElement::find()->draftId($draft->draftId)->siteId($secondSite->id)->status(null)->one()->title)->toBe('Edited release draft')
        ->and($item->fresh()->draftId)->toBe($draft->draftId)
        ->and(fn () => $this->releases->publish($release, $this->actor))
        ->toThrow(ReleaseException::class, 'complete its approval workflow')
        ->and(EntryElement::find()->id($canonical->id)->status(null)->one()->title)->toBe('Multi-site workflow canonical')
        ->and(EntryElement::find()->draftId($draft->draftId)->status(null)->one())->not()->toBeNull()
        ->and($item->fresh()->draftId)->toBe($draft->draftId);
});

it('merges live nested changes before publishing the root draft', function (bool $prepareNestedChange, string $expectedTitle, string $expectedValue) {
    Date::setTestNow('2026-10-01 12:00:00');

    $innerField = Field::factory()->create([
        'name' => 'Freshness inner text',
        'handle' => 'freshnessInnerText',
        'type' => PlainText::class,
    ]);
    $matrixEntryType = EntryType::factory()
        ->withField($innerField)
        ->create([
            'name' => 'Freshness Matrix block',
            'handle' => 'freshnessMatrixBlock',
            'hasTitleField' => true,
        ]);
    $matrixField = Field::factory()->create([
        'name' => 'Freshness Matrix field',
        'handle' => 'freshnessMatrixField',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$matrixEntryType->id]],
    ]);
    $canonical = EntryModel::factory()
        ->withFieldLayout(FieldLayout::factory()->forField($matrixField))
        ->createElement(['title' => 'Live nested owner']);
    $blockUid = Str::uuid()->toString();
    $canonical->setFieldValueFromRequest($matrixField->handle, [
        'entries' => [
            "uid:$blockUid" => [
                'type' => $matrixEntryType->handle,
                'title' => 'Nested canonical block',
                'enabled' => true,
                'fields' => [$innerField->handle => 'Nested canonical value'],
            ],
        ],
        'sortOrder' => [$blockUid],
    ]);
    expect(Elements::saveElement($canonical))->toBeTrue();

    $draft = releaseGuardDraft($canonical, 'Prepared nested draft', $this->actor);
    if ($prepareNestedChange) {
        $draft->setFieldValueFromRequest($matrixField->handle, [
            'entries' => [
                "uid:$blockUid" => [
                    'type' => $matrixEntryType->handle,
                    'title' => 'Reviewed nested title',
                    'enabled' => true,
                    'fields' => [$innerField->handle => 'Reviewed nested value'],
                ],
            ],
            'sortOrder' => [$blockUid],
        ]);
        expect(Elements::saveElement($draft))->toBeTrue();
        $preparedBlock = $draft->getFieldValue($matrixField->handle)->status(null)->one();
        expect($preparedBlock->getCanonicalId())->not->toBe($preparedBlock->id)
            ->and($preparedBlock->getPrimaryOwnerId())->toBe($draft->id);
    }

    app(ElementCanonicalChanges::class)->mergeCanonicalChanges($draft);
    $draft = EntryElement::find()->draftId($draft->draftId)->status(null)->one();
    $release = $this->releases->create('Live nested release', null, $this->actor);
    $item = $this->releases->attach($release, $draft, $this->actor);

    expect($this->releases->checkReadiness($release, $this->actor)->ready)->toBeTrue();

    Date::setTestNow(now()->addMinute());

    $liveOwner = EntryElement::find()->id($canonical->id)->status(null)->one();
    $liveBlock = $liveOwner->getFieldValue($matrixField->handle)->status(null)->one();
    $liveBlock->title = 'New live nested title';
    $liveBlock->slug = 'live-nested-slug';
    $liveBlock->setFieldValue($innerField->handle, 'Live nested change');
    expect(Elements::saveElement($liveBlock))->toBeTrue();

    $readiness = $this->releases->checkReadiness($release, $this->actor);
    $savedLiveOwner = EntryElement::find()->id($canonical->id)->status(null)->one();
    $savedLiveBlock = $savedLiveOwner->getFieldValue($matrixField->handle)->status(null)->one();
    expect($savedLiveBlock->getFieldValue($innerField->handle))->toBe('Live nested change')
        ->and($readiness->ready)->toBeTrue();

    $published = $this->releases->publish($release, $this->actor);

    $savedLiveOwner = EntryElement::find()->id($canonical->id)->status(null)->one();
    $savedLiveBlock = $savedLiveOwner->getFieldValue($matrixField->handle)->status(null)->one();
    expect($published->status)->toBe(ReleaseStatus::Published)
        ->and($savedLiveOwner->title)->toBe('Prepared nested draft')
        ->and($savedLiveBlock->title)->toBe($expectedTitle)
        ->and($savedLiveBlock->slug)->toBe('live-nested-slug')
        ->and($savedLiveBlock->getFieldValue($innerField->handle))->toBe($expectedValue)
        ->and(EntryElement::find()->draftId($draft->draftId)->status(null)->one())->toBeNull()
        ->and($item->fresh()->draftId)->toBeNull();
})->with([
    'shared live block' => [false, 'New live nested title', 'Live nested change'],
    'draft-owned block' => [true, 'Reviewed nested title', 'Reviewed nested value'],
]);

it('edits draft-owned Matrix content while retaining membership and merging live reordering', function () {
    Date::setTestNow('2026-10-01 12:00:00');

    $innerField = Field::factory()->create([
        'name' => 'Inner text',
        'handle' => 'innerText',
        'type' => PlainText::class,
    ]);
    $matrixEntryType = EntryType::factory()
        ->withField($innerField)
        ->create([
            'name' => 'Matrix block',
            'handle' => 'matrixBlock',
            'hasTitleField' => true,
        ]);
    $matrixField = Field::factory()->create([
        'name' => 'Matrix field',
        'handle' => 'matrixField',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$matrixEntryType->id]],
    ]);
    $canonical = EntryModel::factory()
        ->withFieldLayout(FieldLayout::factory()->forField($matrixField))
        ->createElement(['title' => 'Matrix owner']);
    $firstBlockUid = Str::uuid()->toString();
    $secondBlockUid = Str::uuid()->toString();
    $canonical->setFieldValueFromRequest('matrixField', [
        'entries' => [
            "uid:$firstBlockUid" => [
                'type' => $matrixEntryType->handle,
                'title' => 'First canonical block',
                'enabled' => true,
                'fields' => ['innerText' => 'First canonical value'],
            ],
            "uid:$secondBlockUid" => [
                'type' => $matrixEntryType->handle,
                'title' => 'Second canonical block',
                'enabled' => true,
                'fields' => ['innerText' => 'Second canonical value'],
            ],
        ],
        'sortOrder' => [$firstBlockUid, $secondBlockUid],
    ]);
    expect(Elements::saveElement($canonical))->toBeTrue();

    [$firstCanonicalBlock, $secondCanonicalBlock] = $canonical
        ->getFieldValue('matrixField')
        ->status(null)
        ->all();
    $draft = app(Drafts::class)->createDraft($canonical, $this->actor->id, name: 'Matrix release draft');
    $draft->setFieldValueFromRequest('matrixField', [
        'entries' => [
            "uid:$firstCanonicalBlock->uid" => [
                'type' => $matrixEntryType->handle,
                'title' => 'First draft block',
                'enabled' => true,
                'fields' => ['innerText' => 'First draft value'],
            ],
            "uid:$secondCanonicalBlock->uid" => [
                'type' => $matrixEntryType->handle,
                'title' => 'Second draft block',
                'enabled' => true,
                'fields' => ['innerText' => 'Second draft value'],
            ],
        ],
        'sortOrder' => [$firstCanonicalBlock->uid, $secondCanonicalBlock->uid],
    ]);

    $draftMatrixQuery = $draft->getFieldValue('matrixField');
    [$firstDraftBlock, $secondDraftBlock] = $draftMatrixQuery->getResultOverride();

    expect($firstDraftBlock->id)->not()->toBe($firstCanonicalBlock->id)
        ->and($firstDraftBlock->draftId)->not()->toBeNull()
        ->and($firstDraftBlock->draftId)->not()->toBe($draft->draftId)
        ->and($firstDraftBlock->getOwnerId())->toBe($draft->id);

    expect(Elements::saveElement($draft))->toBeTrue();
    $draft = EntryElement::find()->draftId($draft->draftId)->status(null)->one();
    $draftMatrixQuery = $draft->getFieldValue('matrixField');
    [$firstDraftBlock, $secondDraftBlock] = $draftMatrixQuery->status(null)->all();

    $release = $this->releases->create('Matrix release', null, $this->actor);
    $item = $this->releases->attach($release, $draft, $this->actor);

    $firstDraftBlock->setFieldValue('innerText', 'Updated nested content');
    expect(Elements::saveElement($firstDraftBlock))->toBeTrue();

    Elements::reorderNestedElements(
        $draft,
        $draftMatrixQuery,
        [$secondDraftBlock->id],
        0,
    );
    expect(array_keys(releaseGuardOwnerSortOrders($draft->id)))->toBe([
        $secondDraftBlock->id,
        $firstDraftBlock->id,
    ]);

    expect(Elements::deleteElement($secondDraftBlock, true))->toBeTrue();

    $savedBlock = EntryElement::find()->id($firstDraftBlock->id)->status(null)->one();
    expect($savedBlock->getFieldValue('innerText'))->toBe('Updated nested content')
        ->and(array_keys(releaseGuardOwnerSortOrders($draft->id)))->toBe([$firstDraftBlock->id])
        ->and($item->fresh()->draftId)->toBe($draft->draftId);

    Date::setTestNow('2026-10-01 12:01:00');

    $liveOwner = EntryElement::find()->id($canonical->id)->status(null)->one();
    $liveBlocks = $liveOwner->getFieldValue('matrixField')->status(null)->all();

    Elements::reorderNestedElements(
        $liveOwner,
        $liveOwner->getFieldValue('matrixField'),
        [$liveBlocks[1]->id],
        0,
    );

    $readiness = $this->releases->checkReadiness($release, $this->actor);

    expect(array_keys(releaseGuardOwnerSortOrders($canonical->id)))->toBe([
        $liveBlocks[1]->id,
        $liveBlocks[0]->id,
    ])
        ->and($readiness->ready)->toBeTrue()
        ->and($readiness->itemWarnings[$item->id])->toContain('Live changes will be merged into this draft before publication.');
});

it('removes shared Matrix ownership from an attached draft while retaining its release membership', function () {
    ['owner' => $owner, 'child' => $child, 'field' => $field] = releaseGuardSharedMatrixFixture();
    $draft = app(Drafts::class)->createDraft($owner, $this->actor->id, name: 'Shared child release draft');
    $release = $this->releases->create('Shared child release', null, $this->actor);
    $item = $this->releases->attach($release, $draft, $this->actor);

    expect(DB::table(Table::ELEMENTS_OWNERS)
        ->where('ownerId', $draft->id)
        ->where('elementId', $child->id)
        ->exists())->toBeTrue();

    $this->withSession([
        SessionAuth::$authAccessParam => [sprintf('manageNestedElements::%s::%s', $owner->id, "field:$field->handle")],
    ])->postJson(action([NestedElementsController::class, 'destroy']), [
        'ownerElementType' => $draft::class,
        'ownerId' => $draft->id,
        'ownerSiteId' => $draft->siteId,
        'attribute' => "field:$field->handle",
        'elementId' => $child->id,
    ])->assertOk();

    expect(DB::table(Table::ELEMENTS_OWNERS)
        ->where('ownerId', $draft->id)
        ->where('elementId', $child->id)
        ->exists())->toBeFalse()
        ->and(DB::table(Table::ELEMENTS)->where('id', $child->id)->value('dateDeleted'))->toBeNull()
        ->and($item->fresh()->draftId)->toBe($draft->draftId);
});

it('allows shared Matrix ownership removal from a live owner and keeps its release ready', function () {
    Date::setTestNow('2026-10-01 12:00:00');

    ['owner' => $owner, 'child' => $child, 'field' => $field] = releaseGuardSharedMatrixFixture();
    $draft = app(Drafts::class)->createDraft($owner, $this->actor->id, name: 'Live shared child draft');
    $release = $this->releases->create('Live shared child release', null, $this->actor);
    $item = $this->releases->attach($release, $draft, $this->actor);

    Date::setTestNow('2026-10-01 12:01:00');

    $this->withSession([
        SessionAuth::$authAccessParam => [sprintf('manageNestedElements::%s::%s', $owner->id, "field:$field->handle")],
    ])->postJson(action([NestedElementsController::class, 'destroy']), [
        'ownerElementType' => $owner::class,
        'ownerId' => $owner->id,
        'ownerSiteId' => $owner->siteId,
        'attribute' => "field:$field->handle",
        'elementId' => $child->id,
    ])->assertOk();

    $readiness = $this->releases->checkReadiness($release, $this->actor);

    expect(DB::table(Table::ELEMENTS_OWNERS)
        ->where('ownerId', $owner->id)
        ->where('elementId', $child->id)
        ->exists())->toBeFalse()
        ->and(DB::table(Table::ELEMENTS)->where('id', $child->id)->value('dateDeleted'))->toBeNull()
        ->and($readiness->ready)->toBeTrue()
        ->and($readiness->itemWarnings[$item->id])->toContain('Live changes will be merged into this draft before publication.');
});

function releaseGuardDraft(EntryElement $canonical, string $title, User $actor): EntryElement
{
    Sections::refreshSections();
    EntryTypes::refreshEntryTypes();

    $draft = app(Drafts::class)->createDraft(
        canonical: $canonical,
        creatorId: $actor->id,
        name: "$title draft",
    );
    $draft->title = $title;

    if (! Elements::saveElement($draft)) {
        throw new RuntimeException('The release guard test draft could not be saved.');
    }

    return $draft;
}

function releaseGuardAutomaticWorkflow(string $name): Workflow
{
    return Workflow::query()->create([
        'name' => $name,
        'uid' => Str::uuid7()->toString(),
        'stages' => [[
            'uid' => Str::uuid7()->toString(),
            'name' => 'Automatic approval',
            'type' => AutomaticApprovalStage::class,
            'settings' => [],
        ]],
    ]);
}

function releaseGuardOwnerSortOrders(int $ownerId): array
{
    return DB::table(Table::ELEMENTS_OWNERS)
        ->where('ownerId', $ownerId)
        ->orderBy('sortOrder')
        ->pluck('sortOrder', 'elementId')
        ->map(fn (mixed $sortOrder): int => (int) $sortOrder)
        ->all();
}

function releaseGuardSharedMatrixFixture(): array
{
    $matrixEntryType = EntryType::factory()->create([
        'name' => 'Shared Matrix block',
        'handle' => 'sharedMatrixBlock',
        'hasTitleField' => true,
    ]);
    $matrixField = Field::factory()->create([
        'name' => 'Shared Matrix field',
        'handle' => 'sharedMatrixField',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$matrixEntryType->id]],
    ]);
    $primaryOwner = EntryModel::factory()
        ->withFieldLayout(FieldLayout::factory()->forField($matrixField))
        ->createElement(['title' => 'Primary owner']);
    $childUid = Str::uuid()->toString();
    $primaryOwner->setFieldValueFromRequest($matrixField->handle, [
        'entries' => [
            "uid:$childUid" => [
                'type' => $matrixEntryType->handle,
                'title' => 'Shared child',
                'enabled' => true,
            ],
        ],
        'sortOrder' => [$childUid],
    ]);
    expect(Elements::saveElement($primaryOwner))->toBeTrue();

    $child = $primaryOwner->getFieldValue($matrixField->handle)->status(null)->one();
    $owner = EntryModel::factory()
        ->withFieldLayout(FieldLayout::factory()->forField($matrixField))
        ->createElement(['title' => 'Secondary owner']);
    DB::table(Table::ELEMENTS_OWNERS)->insert([
        'elementId' => $child->id,
        'ownerId' => $owner->id,
        'sortOrder' => 1,
    ]);
    Sections::refreshSections();

    return [
        'owner' => EntryElement::find()->id($owner->id)->status(null)->one(),
        'child' => $child,
        'field' => $matrixField,
    ];
}

function releaseGuardException(Closure $callback): ReleaseException
{
    try {
        $callback();
    } catch (ReleaseException $exception) {
        return $exception;
    }

    throw new RuntimeException('Expected a release exception.');
}
