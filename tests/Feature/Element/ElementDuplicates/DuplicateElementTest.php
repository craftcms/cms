<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Events\AfterPropagate;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Element\Exceptions\UnsupportedSiteException;
use CraftCms\Cms\Element\Operations\ElementDeletions;
use CraftCms\Cms\Element\Operations\ElementDuplicates;
use CraftCms\Cms\Element\Operations\ElementUris;
use CraftCms\Cms\Element\Operations\ElementWrites;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Section\Sections;
use CraftCms\Cms\Shared\Exceptions\OperationAbortedException;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Structure\Enums\Mode;
use CraftCms\Cms\Structure\Models\Structure as StructureModel;
use CraftCms\Cms\Structure\Models\StructureElement;
use CraftCms\Cms\Structure\Structures;
use CraftCms\Cms\Support\Facades\Fields as FieldsFacade;
use CraftCms\Cms\Tests\TestClasses\Element\TestDuplicateElementActionElement;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpKernel\Exception\HttpException;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());
});

test('throws for unsaved elements', function () {
    $element = TestDuplicateElementActionElement::create(['id' => null]);
    $action = duplicateAction();

    expect(fn () => $action->duplicateElement($element))
        ->toThrow(Exception::class, 'Attempting to duplicate an unsaved element.');
});

test('throws when duplicating into an unsupported site', function () {
    $element = TestDuplicateElementActionElement::create([
        'siteId' => 999,
        'supportedSitesOverride' => [
            ['siteId' => Site::first()->id],
        ],
    ]);
    $action = duplicateAction();

    expect(fn () => $action->duplicateElement($element))
        ->toThrow(UnsupportedSiteException::class, 'Attempting to duplicate an element in an unsupported site.');
});

test('throws when authorization fails', function () {
    $user = UserModel::factory()->createElement(['admin' => false]);
    actingAs($user);

    [$entry] = createDuplicateActionEntryWithFieldLayout(sectionHandle: 'auth-duplicate-test');

    expect(fn () => app(ElementDuplicates::class)->duplicateElement($entry, checkAuthorization: true))
        ->toThrow(HttpException::class, 'User not authorized to duplicate this element.');
});

test('disables clone and revalidates when uri is invalid', function () {
    Event::fake([AfterPropagate::class]);
    $saveCalls = [];
    $action = duplicateAction(writes: successfulElementWrites($saveCalls));

    $element = TestDuplicateElementActionElement::create([
        'returnUriErrorOnFirstValidate' => true,
    ]);

    $clone = $action->duplicateElement($element);

    expect($clone->enabled)->toBeFalse()
        ->and($clone->validateCallCount)->toBe(2);

    Event::assertDispatched(fn (AfterPropagate $event) => $event->element === $clone && $event->isNew);
});

test('throws when validation still fails', function () {
    $element = TestDuplicateElementActionElement::create();
    $element->forcedValidationAttribute = 'title';
    $element->forcedValidationMessage = 'Title is invalid.';
    $action = duplicateAction();

    expect(fn () => $action->duplicateElement($element))
        ->toThrow(InvalidElementException::class, "Element {$element->id} could not be duplicated because it doesn't validate.");
});

test('creates an unpublished draft and deletes provisional source draft', function () {
    $insertedDraftRows = [];
    $deletedElements = [];
    $saveCalls = [];

    $deletions = Mockery::mock(ElementDeletions::class);
    $deletions->shouldReceive('deleteElementById')
        ->once()
        ->andReturnUsing(function (
            int $elementId,
            ?string $elementType = null,
            ?int $siteId = null,
            bool $hardDelete = false,
        ) use (&$deletedElements): bool {
            $deletedElement = new stdClass;
            $deletedElement->id = $elementId;
            $deletedElement->siteId = $siteId;
            $deletedElement->hardDelete = $hardDelete;
            $deletedElements[] = $deletedElement;

            return true;
        });

    $drafts = Mockery::mock(Drafts::class);
    $drafts->shouldReceive('insertDraftRow')
        ->once()
        ->andReturnUsing(function (
            ?string $name,
            ?string $notes = null,
            ?int $creatorId = null,
            ?int $canonicalId = null,
            bool $trackChanges = false,
            bool $provisional = false,
        ) use (&$insertedDraftRows): int {
            $insertedDraftRows[] = compact('name', 'creatorId', 'canonicalId', 'trackChanges', 'provisional');

            return count($insertedDraftRows);
        });

    $action = duplicateAction(
        drafts: $drafts,
        deletions: $deletions,
        writes: successfulElementWrites($saveCalls),
    );

    $element = TestDuplicateElementActionElement::create([
        'isProvisionalDraft' => true,
    ]);

    $clone = $action->duplicateElement($element, asUnpublishedDraft: true);

    expect($clone->draftId)->toBe(1)
        ->and($clone->draftName)->toBe('First draft')
        ->and($insertedDraftRows)->toHaveCount(1)
        ->and($deletedElements)->toHaveCount(1)
        ->and($deletedElements[0]->id)->toBe($element->id);
});

test('clones object field values without mutating the source', function () {
    $saveCalls = [];
    $action = duplicateAction(writes: successfulElementWrites($saveCalls));

    $field = Field::factory()->create([
        'handle' => 'testField',
        'type' => PlainText::class,
    ]);
    $fieldLayout = FieldLayout::factory()->forField($field)->create();

    $value = new stdClass;
    $value->nested = 'value';

    $element = TestDuplicateElementActionElement::create([
        'useMockFieldValues' => true,
    ]);
    $element->setFieldValue('testField', $value);
    $element->setDirtyFields(['testField'], false);

    $clone = $action->duplicateElement($element);

    expect($clone->getFieldValue('testField'))->not->toBe($value)
        ->and($clone->getDirtyFields())->toBe(['testField']);
});

test('copies modified attributes and fields to changed data tables', function () {
    [$entry, $field] = createDuplicateActionEntryWithFieldLayout(sectionHandle: 'copy-modified-fields-test');

    $entry->title = 'Changed title';
    $entry->setDirtyAttributes(['title'], false);
    $entry->setFieldValue('testField', 'Field change');
    $entry->setDirtyFields(['testField'], false);

    $clone = app(ElementDuplicates::class)->duplicateElement($entry, copyModifiedFields: true);

    expect(DB::table(Table::CHANGEDATTRIBUTES)
        ->where('elementId', $clone->id)
        ->where('siteId', $clone->siteId)
        ->pluck('attribute')
        ->all())->toContain('title');

    expect(DB::table(Table::CHANGEDFIELDS)
        ->where('elementId', $clone->id)
        ->where('siteId', $clone->siteId)
        ->pluck('fieldId')
        ->all())->toContain($field->id);
});

test('also copies modified changes from duplicated draft source', function () {
    [$entry, $field] = createDuplicateActionEntryWithFieldLayout(sectionHandle: 'copy-duplicate-of-draft-test');

    $draftSource = clone $entry;
    $draftSource->draftId = 10;
    $draftSource->setDirtyAttributes(['slug'], false);
    $draftSource->setDirtyFields(['testField'], false);

    $entry->duplicateOf = $draftSource;
    $entry->setDirtyAttributes(['title'], false);

    $clone = app(ElementDuplicates::class)->duplicateElement($entry, copyModifiedFields: true);

    expect(DB::table(Table::CHANGEDATTRIBUTES)
        ->where('elementId', $clone->id)
        ->pluck('attribute')
        ->all())->toContain('title', 'slug');

    expect(DB::table(Table::CHANGEDFIELDS)
        ->where('elementId', $clone->id)
        ->pluck('fieldId')
        ->all())->toContain($field->id);
});

test('moves canonical clones after the source element in a structure', function () {
    $structure = StructureModel::factory()->create();
    $structure->structureElements()->delete();

    $sourceModel = Entry::factory()->create();
    $otherModel = Entry::factory()->create();

    $root = new StructureElement([
        'structureId' => $structure->id,
        'elementId' => $sourceModel->id,
    ]);
    $root->makeRoot();

    $otherNode = new StructureElement([
        'structureId' => $structure->id,
        'elementId' => $otherModel->id,
    ]);
    $otherNode->appendTo($root);

    $source = entryQuery()->id($otherModel->id)->structureId($structure->id)->status(null)->one();
    $clone = app(ElementDuplicates::class)->duplicateElement($source);
    $reloadedClone = entryQuery()->id($clone->id)->structureId($structure->id)->status(null)->one();

    expect($reloadedClone)->not->toBeNull()
        ->and($reloadedClone->structureId)->toBe($structure->id)
        ->and($reloadedClone->lft)->toBeGreaterThan($source->lft);
});

test('uses auto mode when forcing an id in new attributes for structure placement', function () {
    $saveCalls = [];
    $structure = StructureModel::factory()->create();
    $structure->structureElements()->delete();

    $source = TestDuplicateElementActionElement::create([
        'structureId' => $structure->id,
        'root' => 1,
        'lft' => 2,
        'rgt' => 3,
        'level' => 1,
    ]);
    $source->canonicalOverride = $source;

    $writes = successfulElementWrites($saveCalls, idsToAssign: [777]);

    $structureCalls = [];

    $mockStructures = Mockery::mock(Structures::class);
    $mockStructures->shouldReceive('moveAfter')
        ->once()
        ->andReturnUsing(function (int $structureId, ElementInterface $element, ElementInterface|int $prevElement, Mode $mode = Mode::Auto) use (&$structureCalls): bool {
            $structureCalls[] = compact('structureId', 'mode');
            $element->structureId = $structureId;
            $element->root = 1;

            return true;
        });

    $action = new ElementDuplicates(
        $writes,
        Mockery::mock(ElementUris::class),
        Mockery::mock(ElementDeletions::class),
        drafts: Mockery::mock(Drafts::class),
        structures: $mockStructures,
    );

    $action->duplicateElement($source, ['id' => 777]);

    expect($structureCalls[0]['mode'])->toBe(Mode::Auto);
});

test('throws when a localized site clone has an invalid slug', function () {
    $saveCalls = [];
    $action = duplicateAction(
        writes: successfulElementWrites($saveCalls),
    );

    $site = Site::factory()->create();
    app(Sites::class)->refreshSites();

    $element = TestDuplicateElementActionElement::create([
        'supportedSitesOverride' => [
            ['siteId' => Site::first()->id, 'propagate' => true],
            ['siteId' => $site->id, 'propagate' => true],
        ],
    ]);

    $siteElement = TestDuplicateElementActionElement::create([
        'siteId' => $site->id,
        'supportedSitesOverride' => $element->supportedSitesOverride,
        'throwSlugErrorWhenValidatingSlug' => true,
    ]);

    $element->localizedElements = [$siteElement];

    expect(fn () => $action->duplicateElement($element))
        ->toThrow(InvalidElementException::class, "Element {$element->id} could not be duplicated for site {$site->id}: Slug is invalid.");
});

test('continues when setting uri for a localized clone is aborted', function () {
    $saveCalls = [];

    $uris = Mockery::mock(ElementUris::class);
    $uris->shouldReceive('setElementUri')
        ->once()
        ->andReturnUsing(function (ElementInterface $element): void {
            throw new OperationAbortedException('URI aborted.');
        });

    $action = duplicateAction(
        uris: $uris,
        writes: successfulElementWrites($saveCalls, expectedSaveCalls: 2),
    );

    $site = Site::factory()->create();
    app(Sites::class)->refreshSites();

    $element = TestDuplicateElementActionElement::create([
        'supportedSitesOverride' => [
            ['siteId' => Site::first()->id, 'propagate' => true],
            ['siteId' => $site->id, 'propagate' => true],
        ],
    ]);

    $siteElement = TestDuplicateElementActionElement::create([
        'siteId' => $site->id,
        'supportedSitesOverride' => $element->supportedSitesOverride,
    ]);

    $element->localizedElements = [$siteElement];

    $clone = $action->duplicateElement($element);

    expect($clone->id)->not->toBeNull()
        ->and($saveCalls)->toHaveCount(2);
});

test('propagates to supported sites the source element does not exist in', function () {
    $saveCalls = [];
    $propagateCalls = [];
    $action = duplicateAction(
        writes: elementWritesForMissingSitePropagation($saveCalls, $propagateCalls),
    );

    $site = Site::factory()->create();
    app(Sites::class)->refreshSites();

    $element = TestDuplicateElementActionElement::create([
        'supportedSitesOverride' => [
            ['siteId' => Site::first()->id, 'propagate' => true],
            ['siteId' => $site->id, 'propagate' => true],
        ],
    ]);

    $clone = $action->duplicateElement($element);

    expect($propagateCalls[0]['siteId'])->toBe($site->id)
        ->and($clone->newSiteIds)->toContain($site->id);
});

test('throws when propagation to a missing source site fails', function () {
    $saveCalls = [];
    $propagateCalls = [];
    $action = duplicateAction(
        writes: elementWritesForMissingSitePropagation($saveCalls, $propagateCalls, false),
    );

    $site = Site::factory()->create();
    app(Sites::class)->refreshSites();

    $element = TestDuplicateElementActionElement::create([
        'supportedSitesOverride' => [
            ['siteId' => Site::first()->id, 'propagate' => true],
            ['siteId' => $site->id, 'propagate' => true],
        ],
    ]);

    expect(fn () => $action->duplicateElement($element))
        ->toThrow(InvalidElementException::class, 'could not be propagated to site');
});

function createDuplicateActionEntryWithFieldLayout(string $sectionHandle = 'duplicate-test', ?SectionType $type = null): array
{
    $field = Field::factory()->create([
        'handle' => 'testField',
        'type' => PlainText::class,
    ]);

    $fieldLayout = FieldLayout::factory()->forField($field)->create();

    $section = Section::factory()->create([
        'handle' => $sectionHandle,
        'type' => $type ?? SectionType::Channel,
    ]);

    $entryModel = Entry::factory()
        ->forSection($section)
        ->create();

    $entryModel->element->update([
        'fieldLayoutId' => $fieldLayout->id,
    ]);

    $entryModel->entryType->update([
        'fieldLayoutId' => $fieldLayout->id,
    ]);

    FieldsFacade::invalidateCaches();
    FieldsFacade::refreshFields();
    app(Sections::class)->refreshSections();

    $entry = entryQuery()->id($entryModel->id)->status(null)->one();

    return [$entry, $field, $fieldLayout, $section];
}

function duplicateAction(
    ?Drafts $drafts = null,
    ?ElementWrites $writes = null,
    ?ElementUris $uris = null,
    ?ElementDeletions $deletions = null,
    ?Structures $structures = null,
): ElementDuplicates {
    return new ElementDuplicates(
        $writes ?? Mockery::mock(ElementWrites::class),
        $uris ?? Mockery::mock(ElementUris::class),
        $deletions ?? Mockery::mock(ElementDeletions::class),
        $drafts ?? Mockery::mock(Drafts::class),
        $structures ?? Mockery::mock(Structures::class),
    );
}

function successfulElementWrites(array &$calls, array $idsToAssign = [], int $expectedSaveCalls = 1): ElementWrites
{
    $writes = Mockery::mock(ElementWrites::class);
    $writes->shouldReceive('save')
        ->times($expectedSaveCalls)
        ->andReturnUsing(function (
            ElementInterface $element,
            bool $runValidation = true,
            bool $propagate = true,
            ?bool $updateSearchIndex = null,
            ?array $supportedSites = null,
            bool $forceTouch = false,
            bool $crossSiteValidate = false,
            bool $saveContent = false,
            mixed &$siteSettingsRecord = null,
        ) use (&$calls, &$idsToAssign): bool {
            $calls[] = [
                'element' => clone $element,
                'supportedSites' => $supportedSites,
            ];

            if (! $element->id) {
                $element->id = array_shift($idsToAssign) ?? 1000 + count($calls);
            }

            $element->dateCreated ??= now();
            $element->dateUpdated ??= now();

            return true;
        });

    return $writes;
}

function elementWritesForMissingSitePropagation(array &$saveCalls, array &$propagateCalls, bool $result = true): ElementWrites
{
    $writes = successfulElementWrites($saveCalls);
    $writes->shouldReceive('propagate')
        ->once()
        ->andReturnUsing(function (
            ElementInterface $element,
            array $supportedSites,
            int $siteId,
            ElementInterface|false|null &$siteElement = null,
            bool $crossSiteValidate = false,
            bool $saveContent = true,
            mixed &$siteSettingsRecord = null,
        ) use (&$propagateCalls, $result): bool {
            $propagateCalls[] = [
                'siteId' => $siteId,
                'siteClone' => $siteElement,
            ];

            if (! $result) {
                return false;
            }

            if ($siteElement === false || $siteElement === null) {
                $siteElement = clone $element;
                $siteElement->siteId = $siteId;
            }

            return true;
        });

    return $writes;
}
