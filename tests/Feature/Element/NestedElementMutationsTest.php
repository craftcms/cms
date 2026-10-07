<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\SessionAuth;
use CraftCms\Cms\Element\Actions\Delete;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Http\Controllers\NestedElementsController;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());
    $this->freezeSecond();

    $childType = EntryType::factory()->create(['handle' => 'childBlock', 'hasTitleField' => true]);
    $innerField = Field::factory()->create([
        'handle' => 'innerMatrix',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$childType->id]],
    ]);
    $ownerType = EntryType::factory()->withField($innerField)->create(['handle' => 'ownerBlock', 'hasTitleField' => true]);
    $outerField = Field::factory()->create([
        'handle' => 'outerMatrix',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$ownerType->id]],
    ]);
    $pageType = EntryType::factory()->withField($outerField)->create(['hasTitleField' => true]);
    $section = Section::factory()->withEntryTypes($pageType)->create();
    Fields::refreshFields();
    EntryTypes::refreshEntryTypes();
    Sections::refreshSections();

    $page = EntryModel::factory()->forSection($section)->forEntryType($pageType)->createElement();
    $page->setFieldValue('outerMatrix', [
        'new1' => [
            'type' => 'ownerBlock',
            'title' => 'Owner',
            'fields' => ['innerMatrix' => [
                'new1' => ['type' => 'childBlock', 'title' => 'First'],
                'new2' => ['type' => 'childBlock', 'title' => 'Second'],
            ]],
        ],
    ]);
    expect(Elements::saveElement($page))->toBeTrue();

    $this->page = Entry::find()->id($page->id)->one();
    $this->owner = Entry::find()->ownerId($page->id)->fieldId($outerField->id)->one();
    $this->children = Entry::find()->ownerId($this->owner->id)->fieldId($innerField->id)->all();
    $this->innerField = $innerField;
    $this->childType = $childType;
});

it('makes owner and ancestor drafts outdated after nested content changes', function (string $operation) {
    $owner = $this->owner;
    $page = $this->page;
    [$first, $second] = $this->children;
    if (in_array($operation, ['detach', 'bulk detach'], true)) {
        $primaryOwner = Elements::duplicateElement($owner);
        $first->setPrimaryOwner($primaryOwner);
        expect(Elements::saveElement($first))->toBeTrue();
    }

    $this->travel(1)->seconds();
    $ownerDraft = app(Drafts::class)->createDraft($owner);
    $pageDraft = app(Drafts::class)->createDraft($page);
    Elements::mergeCanonicalChanges($ownerDraft);
    Elements::mergeCanonicalChanges($pageDraft);

    expect(ElementHelper::isOutdated($ownerDraft))->toBeFalse()
        ->and(ElementHelper::isOutdated($pageDraft))->toBeFalse();

    $this->travel(2)->seconds();

    if (in_array($operation, ['reorder', 'detach'], true)) {
        $this->withSession([
            SessionAuth::$authAccessParam => [
                "manageNestedElements::{$owner->id}::field:innerMatrix",
                "reorderNestedElements::{$owner->id}::field:innerMatrix",
            ],
        ])->postJson(action([NestedElementsController::class, $operation === 'reorder' ? 'reorder' : 'destroy']), [
            'ownerElementType' => Entry::class,
            'ownerId' => $owner->id,
            'ownerSiteId' => $owner->siteId,
            'attribute' => 'field:innerMatrix',
            'elementIds' => [$second->id],
            'offset' => 0,
            'elementId' => $first->id,
        ])->assertOk();
    } elseif (str_starts_with($operation, 'bulk')) {
        expect(new Delete(['elementType' => Entry::class])->performAction(
            Entry::find()->ownerId($owner->id)->fieldId($this->innerField->id)
                ->id($operation === 'bulk detach' ? [$first->id] : [$first->id, $second->id]),
        ))->toBeTrue();
    } else {
        expect(Elements::deleteElement($first, $operation === 'hard delete'))->toBeTrue();
    }

    $freshOwner = Entry::find()->id($owner->id)->one();
    $freshPage = Entry::find()->id($page->id)->one();
    $freshOwnerDraft = Entry::find()->id($ownerDraft->id)->drafts(true)->one();
    $freshPageDraft = Entry::find()->id($pageDraft->id)->drafts(true)->one();

    expect(ElementHelper::isOutdated($freshOwnerDraft))->toBeTrue()
        ->and(ElementHelper::isOutdated($freshPageDraft))->toBeTrue()
        ->and($freshOwner->dateUpdated->getTimestamp())->toBeGreaterThan($owner->dateUpdated->getTimestamp())
        ->and($freshPage->dateUpdated->getTimestamp())->toBeGreaterThan($page->dateUpdated->getTimestamp());

    $remainingIds = Entry::find()->ownerId($owner->id)->fieldId($this->innerField->id)->pluck('id')->all();
    expect($remainingIds)->toBe(match ($operation) {
        'reorder' => [$second->id, $first->id],
        'bulk delete' => [],
        default => [$second->id],
    });
})->with(['reorder', 'detach', 'delete', 'hard delete', 'bulk delete', 'bulk detach']);

it('keeps nested draft and revision reorders separate from canonical ancestors', function (string $type) {
    $owner = $this->owner;
    $page = $this->page;
    $derivative = $type === 'revision'
        ? Elements::getElementById(app(Revisions::class)->createRevision($owner, force: true))
        : app(Drafts::class)->createDraft($owner, provisional: $type === 'provisional draft');
    $children = Entry::find()->ownerId($derivative->id)->fieldId($this->innerField->id)->revisions(null)->all();
    $originalDateUpdated = $derivative->dateUpdated->getTimestamp();
    $this->travel(2)->seconds();

    Elements::reorderNestedElements($derivative, new ElementCollection($children), [$children[1]->id], 0);

    $freshDerivative = Entry::find()->id($derivative->id)->drafts(null)->provisionalDrafts(null)->revisions(null)->one();
    expect($freshDerivative->dateUpdated->getTimestamp())->toBeGreaterThan($originalDateUpdated)
        ->and(Entry::find()->id($owner->id)->one()->dateUpdated->getTimestamp())->toBe($owner->dateUpdated->getTimestamp())
        ->and(Entry::find()->id($page->id)->one()->dateUpdated->getTimestamp())->toBe($page->dateUpdated->getTimestamp());
})->with(['draft', 'provisional draft', 'revision']);

it('does not touch ancestors when a nested reorder changes nothing', function () {
    $this->travel(2)->seconds();

    Elements::reorderNestedElements($this->owner, new ElementCollection($this->children), [$this->children[0]->id], 0);

    expect(Entry::find()->id($this->owner->id)->one()->dateUpdated->getTimestamp())->toBe($this->owner->dateUpdated->getTimestamp())
        ->and(Entry::find()->id($this->page->id)->one()->dateUpdated->getTimestamp())->toBe($this->page->dateUpdated->getTimestamp());
});

it('does not mark canonical content changed when deleting a nested derivative', function (string $type) {
    $child = $this->children[0];
    if ($type === 'unpublished draft') {
        $derivative = new Entry([
            'fieldId' => $this->innerField->id,
            'ownerId' => $this->owner->id,
            'typeId' => $this->childType->id,
            'siteId' => $this->owner->siteId,
        ]);
        $derivative->ruleset->useScenario(ElementRules::SCENARIO_ESSENTIALS);
        expect(app(Drafts::class)->saveElementAsDraft($derivative, markAsSaved: false))->toBeTrue();
    } else {
        $derivative = $type === 'revision'
            ? Elements::getElementById(app(Revisions::class)->createRevision($child, force: true))
            : app(Drafts::class)->createDraft($child, provisional: $type === 'provisional draft');
    }
    $this->travel(2)->seconds();

    expect(Elements::deleteElement($derivative, true))->toBeTrue()
        ->and(Entry::find()->id($this->owner->id)->one()->dateUpdated->getTimestamp())->toBe($this->owner->dateUpdated->getTimestamp())
        ->and(Entry::find()->id($this->page->id)->one()->dateUpdated->getTimestamp())->toBe($this->page->dateUpdated->getTimestamp());
})->with(['draft', 'provisional draft', 'revision', 'unpublished draft']);

it('does not touch ancestors again when repeating a delete or purging a trashed child', function (bool $hardDelete) {
    $child = $this->children[0];
    expect(Elements::deleteElement($child))->toBeTrue();
    $owner = Entry::find()->id($this->owner->id)->one();
    $page = Entry::find()->id($this->page->id)->one();
    $this->travel(2)->seconds();

    expect(Elements::deleteElement($child, $hardDelete))->toBeTrue()
        ->and(Entry::find()->id($owner->id)->one()->dateUpdated->getTimestamp())->toBe($owner->dateUpdated->getTimestamp())
        ->and(Entry::find()->id($page->id)->one()->dateUpdated->getTimestamp())->toBe($page->dateUpdated->getTimestamp());
})->with([false, true]);

it('touches a draft owner when its own nested draft is deleted without touching canonical content', function () {
    $ownerDraft = app(Drafts::class)->createDraft($this->owner);
    $childDraft = app(Drafts::class)->createDraft($this->children[0], newAttributes: [
        'ownerId' => $ownerDraft->id,
        'primaryOwnerId' => $ownerDraft->id,
    ]);
    expect($childDraft->getOwner()->id)->toBe($ownerDraft->id);
    $this->travel(2)->seconds();

    expect(Elements::deleteElement($childDraft))->toBeTrue()
        ->and(Entry::find()->id($ownerDraft->id)->drafts(true)->one()->dateUpdated->getTimestamp())
        ->toBeGreaterThan($ownerDraft->dateUpdated->getTimestamp())
        ->and(Entry::find()->id($this->owner->id)->one()->dateUpdated->getTimestamp())->toBe($this->owner->dateUpdated->getTimestamp())
        ->and(Entry::find()->id($this->page->id)->one()->dateUpdated->getTimestamp())->toBe($this->page->dateUpdated->getTimestamp());
});

it('can cascade a hard delete after the owner row has gone', function () {
    expect(Elements::deleteElement($this->page, true))->toBeTrue()
        ->and(Entry::find()->id($this->owner->id)->trashed(null)->one())->toBeNull()
        ->and(Entry::find()->id($this->children[0]->id)->trashed(null)->one())->toBeNull();
});

it('rejects cyclic ownership before writing a nested reorder', function () {
    $owner = $this->owner;
    $owner->setOwner($owner);
    $this->travel(2)->seconds();

    expect(fn () => Elements::reorderNestedElements($owner, new ElementCollection($this->children), [$this->children[1]->id], 0))
        ->toThrow(RuntimeException::class, 'Cyclic element ownership.');

    expect(Entry::find()->ownerId($owner->id)->fieldId($this->innerField->id)->pluck('id')->all())
        ->toBe(array_map(fn (Entry $child): int => $child->id, $this->children))
        ->and(Entry::find()->id($owner->id)->one()->dateUpdated->getTimestamp())->toBe($owner->dateUpdated->getTimestamp());
});
