<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Actions\MergeCanonicalChangesAction;
use CraftCms\Cms\Element\Actions\SaveElementAction;
use CraftCms\Cms\Element\BulkOp\BulkOps;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Element\Events\AfterMergeCanonicalChanges;
use CraftCms\Cms\Element\Events\AfterPropagate;
use CraftCms\Cms\Element\Events\BeforeMergeCanonicalChanges;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Section\Models\SectionSiteSettings;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;

class TestMergeCanonicalChangesQuery extends ElementQuery
{
    /**
     * @param  array<int, Element>  $results
     */
    public function __construct(
        private readonly array $results = [],
    ) {
        parent::__construct(TestMergeCanonicalChangesElement::class);
    }

    #[Override]
    public function siteId($value): static
    {
        return $this;
    }

    #[Override]
    public function status(array|string|null $value): static
    {
        return $this;
    }

    #[Override]
    public function all($columns = ['*']): array
    {
        return $this->results;
    }
}

class TestMergeCanonicalChangesElement extends Element
{
    public static bool $trackChanges = true;

    public array $supportedSites = [1];

    public ?ElementQueryInterface $localizedQuery = null;

    public int $mergeCanonicalChangesCalls = 0;

    public int $afterPropagateCalls = 0;

    #[Override]
    public static function displayName(): string
    {
        return 'Test merge canonical changes element';
    }

    #[Override]
    public static function trackChanges(): bool
    {
        return static::$trackChanges;
    }

    #[Override]
    public function getSupportedSites(): array
    {
        return $this->supportedSites;
    }

    #[Override]
    public function getLocalizedQuery(): ElementQueryInterface
    {
        return $this->localizedQuery ?? new TestMergeCanonicalChangesQuery;
    }

    #[Override]
    public function mergeCanonicalChanges(): void
    {
        $this->mergeCanonicalChangesCalls++;
    }

    #[Override]
    public function afterPropagate(bool $isNew): void
    {
        $this->afterPropagateCalls++;

        parent::afterPropagate($isNew);
    }
}

beforeEach(function () {
    TestMergeCanonicalChangesElement::$trackChanges = true;
});

it('throws when the element is canonical', function () {
    $element = new TestMergeCanonicalChangesElement;
    $element->id = 1;
    $element->siteId = 1;

    expect(fn () => app(MergeCanonicalChangesAction::class)->handle($element))
        ->toThrow(InvalidArgumentException::class, 'Only a derivative element can be passed to CraftCms\\Cms\\Element\\Actions\\MergeCanonicalChangesAction::handle');
});

it('throws when the element type does not track changes', function () {
    TestMergeCanonicalChangesElement::$trackChanges = false;

    $element = new TestMergeCanonicalChangesElement;
    $element->id = 1;
    $element->siteId = 1;
    $element->setCanonicalId(2);

    expect(fn () => app(MergeCanonicalChangesAction::class)->handle($element))
        ->toThrow(InvalidArgumentException::class, TestMergeCanonicalChangesElement::class.' elements don’t track their changes');
});

it('throws when the derivative site is unsupported', function () {
    $element = new TestMergeCanonicalChangesElement;
    $element->id = 1;
    $element->siteId = 99;
    $element->setCanonicalId(2);
    $element->supportedSites = [1];

    expect(fn () => app(MergeCanonicalChangesAction::class)->handle($element))
        ->toThrow(Exception::class, 'Attempting to merge source changes for a draft in an unsupported site.');
});

it('merges and saves localized derivatives before the requested site', function () {
    Event::fake([
        AfterMergeCanonicalChanges::class,
        AfterPropagate::class,
        BeforeMergeCanonicalChanges::class,
    ]);

    $primarySite = Site::firstOrFail();
    $secondarySite = Site::factory()->create();
    Sites::refreshSites();

    actingAs(User::findOne());

    $section = Section::factory()->withEntryTypes(
        $entryType = EntryType::factory()->create()
    )->create([
        'propagationMethod' => PropagationMethod::Custom,
    ]);

    SectionSiteSettings::factory()->create([
        'sectionId' => $section->id,
        'siteId' => $secondarySite->id,
        'hasUrls' => true,
        'dateCreated' => $section->dateCreated,
        'dateUpdated' => $section->dateUpdated,
    ]);

    $canonical = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->createElement(['title' => 'Canonical title']);

    $canonical->setEnabledForSite([
        $primarySite->id => true,
        $secondarySite->id => true,
    ]);
    Elements::saveElement($canonical);

    $draft = app(Drafts::class)->createDraft($canonical, User::findOne()->id);

    $draft->title = 'Primary draft title';
    Elements::saveElement($draft, false, false);

    $secondaryDraft = Entry::find()
        ->id($draft->id)
        ->drafts(true)
        ->siteId($secondarySite->id)
        ->status(null)
        ->one();

    expect($secondaryDraft)->not->toBeNull();

    $secondaryDraft->title = 'Secondary draft title';
    Elements::saveElement($secondaryDraft, false, false);

    $saveCalls = [];
    $saveElementAction = Mockery::mock(SaveElementAction::class);
    $saveElementAction->shouldReceive('handle')
        ->twice()
        ->andReturnUsing(function (Entry $element, bool $runValidation, bool $propagate, ?bool $updateSearchIndex = null, ?array $supportedSites = null) use (&$saveCalls) {
            $saveCalls[] = [
                'id' => $element->id,
                'siteId' => $element->siteId,
                'runValidation' => $runValidation,
                'propagate' => $propagate,
                'supportedSiteIds' => array_keys($supportedSites ?? []),
                'mergingCanonicalChanges' => $element->mergingCanonicalChanges,
                'dateLastMerged' => $element->dateLastMerged,
                'duplicateOf' => $element->duplicateOf,
            ];

            return true;
        });

    $action = new MergeCanonicalChangesAction(app(BulkOps::class), $saveElementAction);

    $originalDuplicate = new Entry;
    $draft->duplicateOf = $originalDuplicate;

    $action->handle($draft);

    expect($saveCalls)->toHaveCount(2)
        ->and($saveCalls[0]['id'])->toBe($secondaryDraft->id)
        ->and($saveCalls[0]['siteId'])->toBe($secondarySite->id)
        ->and($saveCalls[0]['runValidation'])->toBeFalse()
        ->and($saveCalls[0]['propagate'])->toBeFalse()
        ->and($saveCalls[0]['supportedSiteIds'])->toBe([$primarySite->id, $secondarySite->id])
        ->and($saveCalls[0]['mergingCanonicalChanges'])->toBeTrue()
        ->and($saveCalls[0]['dateLastMerged'])->toBeNull()
        ->and($saveCalls[1]['id'])->toBe($draft->id)
        ->and($saveCalls[1]['siteId'])->toBe($primarySite->id)
        ->and($saveCalls[1]['runValidation'])->toBeFalse()
        ->and($saveCalls[1]['propagate'])->toBeFalse()
        ->and($saveCalls[1]['supportedSiteIds'])->toBe([$primarySite->id, $secondarySite->id])
        ->and($saveCalls[1]['mergingCanonicalChanges'])->toBeTrue()
        ->and($saveCalls[1]['dateLastMerged'])->not->toBeNull()
        ->and($saveCalls[1]['duplicateOf'])->toBeNull()
        ->and($draft->duplicateOf)->toBe($originalDuplicate)
        ->and($draft->mergingCanonicalChanges)->toBeFalse();

    Event::assertDispatchedTimes(BeforeMergeCanonicalChanges::class, 1);
    Event::assertDispatchedTimes(AfterMergeCanonicalChanges::class, 1);
    Event::assertDispatched(fn (BeforeMergeCanonicalChanges $event) => $event->element === $draft);
    Event::assertDispatched(fn (AfterMergeCanonicalChanges $event) => $event->element === $draft);
    Event::assertDispatched(fn (AfterPropagate $event) => $event->element === $draft && $event->isNew === false);
});

it('merges localized elements, sets dateLastMerged, and resets the merging flag', function () {
    Event::fake([
        AfterMergeCanonicalChanges::class,
        AfterPropagate::class,
        BeforeMergeCanonicalChanges::class,
    ]);

    $primarySite = Site::firstOrFail();
    $secondarySite = Site::factory()->create();
    Sites::refreshSites();

    $currentSiteElement = new TestMergeCanonicalChangesElement;
    $currentSiteElement->id = 10;
    $currentSiteElement->siteId = $primarySite->id;
    $currentSiteElement->setCanonicalId(5);
    $currentSiteElement->supportedSites = [$primarySite->id, $secondarySite->id];
    $currentSiteElement->duplicateOf = new TestMergeCanonicalChangesElement;

    $otherSiteElement = new TestMergeCanonicalChangesElement;
    $otherSiteElement->id = 10;
    $otherSiteElement->siteId = $secondarySite->id;
    $otherSiteElement->setCanonicalId(5);
    $otherSiteElement->supportedSites = [$primarySite->id, $secondarySite->id];

    $currentSiteElement->localizedQuery = new TestMergeCanonicalChangesQuery([$otherSiteElement]);

    $saveElementAction = Mockery::mock(SaveElementAction::class);
    $saveElementAction->shouldReceive('handle')
        ->twice()
        ->andReturnUsing(fn () => true);

    $action = new MergeCanonicalChangesAction(app(BulkOps::class), $saveElementAction);

    $action->handle($currentSiteElement);

    expect($otherSiteElement->mergeCanonicalChangesCalls)->toBe(1)
        ->and($otherSiteElement->mergingCanonicalChanges)->toBeTrue()
        ->and($currentSiteElement->mergeCanonicalChangesCalls)->toBe(1)
        ->and($currentSiteElement->dateLastMerged)->not->toBeNull()
        ->and($currentSiteElement->mergingCanonicalChanges)->toBeFalse()
        ->and($currentSiteElement->afterPropagateCalls)->toBe(1);

    Event::assertDispatchedOnce(BeforeMergeCanonicalChanges::class);
    Event::assertDispatchedOnce(AfterMergeCanonicalChanges::class);
    Event::assertDispatchedOnce(AfterPropagate::class);
});
