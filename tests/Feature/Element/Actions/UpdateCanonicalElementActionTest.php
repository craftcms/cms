<?php

declare(strict_types=1);

use craft\base\ElementInterface;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Actions\UpdateCanonicalElementAction;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\EntryTypes as EntryTypesService;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout as FieldLayoutModel;
use CraftCms\Cms\Section\Sections;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());
});

it('throws when the element is already canonical', function () {
    $result = EntryModel::factory()
        ->withField('testField', PlainText::class)
        ->createElementWithFields(save: false);

    /** @var EntryElement $entry */
    $entry = $result->element;

    [$action, $state] = createActionSpy();

    expect(fn () => $action->handle($entry))
        ->toThrow(InvalidArgumentException::class, 'Element was already canonical');

    expect($state->duplicateCall)->toBeNull();
});

it('throws when the derivative entry type is no longer allowed in its section', function () {
    $entryModel = EntryModel::factory()->create();
    $entry = EntryElement::find()->id($entryModel->id)->one();
    $draft = app(Drafts::class)->createDraft($entry, User::findOne()->id);

    $entryModel->section->entryTypes()->detach($entryModel->typeId);
    app(EntryTypesService::class)->refreshEntryTypes();
    app(Sections::class)->refreshSections();

    $draft = EntryElement::find()
        ->drafts()
        ->draftId($draft->draftId)
        ->id($draft->id)
        ->status(null)
        ->one();

    [$action, $state] = createActionSpy();

    expect(fn () => $action->handle($draft))
        ->toThrow(InvalidArgumentException::class, 'Entry Type is no longer allowed in this section.');

    expect($state->duplicateCall)->toBeNull();
});

it('prepares duplicate attributes and defers canonical change tracking updates for drafts', function () {
    $result = EntryModel::factory()
        ->withField('testField', PlainText::class)
        ->createElementWithFields(save: false);

    /** @var EntryElement $entry */
    $entry = $result->element;
    $field = $result->field('testField');

    $validLayoutElementUid = Str::uuid()->toString();
    $missingFieldLayoutElementUid = Str::uuid()->toString();
    $unknownLayoutElementUid = Str::uuid()->toString();

    $fieldLayout = FieldLayoutModel::factory()->create([
        'type' => EntryElement::class,
        'config' => [
            'tabs' => [[
                'uid' => Str::uuid()->toString(),
                'name' => 'Content',
                'elements' => [
                    [
                        'type' => CustomField::class,
                        'uid' => $validLayoutElementUid,
                        'fieldUid' => $field->uid,
                    ],
                    [
                        'type' => CustomField::class,
                        'uid' => $missingFieldLayoutElementUid,
                        'fieldUid' => Str::uuid()->toString(),
                    ],
                ],
            ]],
        ],
    ]);

    DB::table(Table::ELEMENTS)->where('id', $entry->id)->update([
        'fieldLayoutId' => $fieldLayout->id,
    ]);

    DB::table(Table::ENTRYTYPES)->where('id', $entry->typeId)->update([
        'fieldLayoutId' => $fieldLayout->id,
    ]);

    app(EntryTypesService::class)->refreshEntryTypes();
    Fields::invalidateCaches();
    Fields::refreshFields();

    $entry = EntryElement::find()->id($entry->id)->one();
    $draft = app(Drafts::class)->createDraft($entry, User::findOne()->id);
    $canonical = clone $entry;
    $canonical->oldStatus = EntryElement::STATUS_DISABLED;
    $draft->setCanonical($canonical);

    DB::table(Table::CHANGEDATTRIBUTES)->insert([
        'elementId' => $draft->id,
        'siteId' => $draft->siteId,
        'attribute' => 'title',
        'dateUpdated' => now(),
        'propagated' => true,
        'userId' => User::findOne()->id,
    ]);

    DB::table(Table::CHANGEDFIELDS)->insert([
        [
            'elementId' => $draft->id,
            'siteId' => $draft->siteId,
            'fieldId' => $field->id,
            'layoutElementUid' => $validLayoutElementUid,
            'dateUpdated' => now(),
            'propagated' => true,
            'userId' => User::findOne()->id,
        ],
        [
            'elementId' => $draft->id,
            'siteId' => $draft->siteId,
            'fieldId' => $field->id,
            'layoutElementUid' => $missingFieldLayoutElementUid,
            'dateUpdated' => now(),
            'propagated' => false,
            'userId' => User::findOne()->id,
        ],
        [
            'elementId' => $draft->id,
            'siteId' => $draft->siteId,
            'fieldId' => $field->id,
            'layoutElementUid' => $unknownLayoutElementUid,
            'dateUpdated' => now(),
            'propagated' => true,
            'userId' => User::findOne()->id,
        ],
    ]);

    $updatedCanonical = clone $canonical;
    $updatedCanonical->dateUpdated = Date::parse('2026-01-02 03:04:05');

    [$action, $state] = createActionSpy($updatedCanonical);

    expect($action->handle($draft, ['custom' => 'value']))->toBe($updatedCanonical);

    expect($state->duplicateCall)->not->toBeNull();
    expect($state->duplicateCall['element'])->toBe($draft);
    expect($state->duplicateCall['newAttributes'])
        ->toMatchArray([
            'custom' => 'value',
            'id' => $canonical->id,
            'uid' => $canonical->uid,
            'canonicalId' => $canonical->getCanonicalId(),
            'root' => $canonical->root,
            'lft' => $canonical->lft,
            'rgt' => $canonical->rgt,
            'level' => $canonical->level,
            'dateCreated' => $canonical->dateCreated,
            'dateDeleted' => null,
            'draftId' => null,
            'revisionId' => null,
            'isProvisionalDraft' => false,
            'updatingFromDerivative' => true,
            'dirtyAttributes' => [],
            'dirtyFields' => [],
            'oldStatus' => EntryElement::STATUS_DISABLED,
        ])
        ->and($state->duplicateCall['newAttributes']['siteAttributes'][$draft->siteId]['dirtyAttributes'])
        ->toBe(['title'])
        ->and($state->duplicateCall['newAttributes']['siteAttributes'][$draft->siteId]['dirtyFields'])
        ->toBe([$field->handle]);

    expect(DB::table(Table::CHANGEDATTRIBUTES)->where('elementId', $canonical->id)->count())->toBe(0)
        ->and(DB::table(Table::CHANGEDFIELDS)->where('elementId', $canonical->id)->count())->toBe(0);

    app()->terminate();

    expect(DB::table(Table::CHANGEDATTRIBUTES)
        ->where('elementId', $canonical->id)
        ->where('siteId', $draft->siteId)
        ->where('attribute', 'title')
        ->count())->toBe(1)
        ->and(DB::table(Table::CHANGEDFIELDS)
            ->where('elementId', $canonical->id)
            ->where('siteId', $draft->siteId)
            ->count())
        ->toBe(3);
});

it('marks all custom fields as dirty when updating from a revision', function () {
    $result = EntryModel::factory()
        ->withField('revisionField', PlainText::class)
        ->createElementWithFields(save: false);

    /** @var EntryElement $entry */
    $entry = $result->element;
    $field = $result->field('revisionField');

    $revisionId = app(Revisions::class)->createRevision(
        canonical: $entry,
        notes: 'Some notes',
    );

    $revision = EntryElement::find()
        ->revisions()
        ->id($revisionId)
        ->status(null)
        ->one();

    $updatedCanonical = clone $entry;
    $updatedCanonical->dateUpdated = now();

    [$action, $state] = createActionSpy($updatedCanonical);

    $action->handle($revision, ['dirtyFields' => ['ignoredField']]);

    expect($state->duplicateCall)->not->toBeNull();
    expect($state->duplicateCall['newAttributes']['dirtyFields'])->toBe([$field->handle]);
});

function createActionSpy(?ElementInterface $duplicateResult = null): array
{
    $state = new class
    {
        public ?array $duplicateCall = null;
    };

    $elements = Mockery::mock(Elements::class);
    $elements->shouldReceive('duplicateElement')
        ->andReturnUsing(function (
            ElementInterface $element,
            array $newAttributes = [],
            bool $placeInStructure = true,
            bool $asUnpublishedDraft = false,
            bool $checkAuthorization = false,
            bool $copyModifiedFields = false,
        ) use ($state, $duplicateResult): ElementInterface {
            $state->duplicateCall = [
                'element' => $element,
                'newAttributes' => $newAttributes,
                'placeInStructure' => $placeInStructure,
                'asUnpublishedDraft' => $asUnpublishedDraft,
                'checkAuthorization' => $checkAuthorization,
                'copyModifiedFields' => $copyModifiedFields,
            ];

            return $duplicateResult ?? $element;
        });

    return [new UpdateCanonicalElementAction($elements), $state];
}
