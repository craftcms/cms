<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\SessionAuth;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\FieldLayoutCompiler;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;

/**
 * The browser posts a Matrix field as an `{entries, sortOrder}` envelope, but the two
 * control panel stacks don't agree on where the `uid:` prefix goes: `block.twig` writes
 * prefixed `entries` keys and bare `sortOrder` values, while the Form controls prefix
 * both. Every shape below is something a real client sends.
 *
 * @see ElementHelper::nestedElementDelta()
 */

/** @return array{0: EntryElement, 1: EntryType} */
function matrixShapeFixture(): array
{
    $innerField = Field::factory()->create([
        'name' => 'Inner Text',
        'handle' => 'shapeInnerText',
        'type' => PlainText::class,
    ]);

    $blockType = EntryType::factory()
        ->withField($innerField)
        ->create([
            'name' => 'Shape Block',
            'handle' => 'shapeBlock',
            'hasTitleField' => true,
        ]);

    $matrixField = Field::factory()->create([
        'name' => 'Shape Matrix',
        'handle' => 'shapeMatrix',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$blockType->id]],
    ]);

    $entryModel = Entry::factory()
        ->withFieldLayout(FieldLayout::factory()->forField($matrixField))
        ->create();

    /** @var EntryElement $entry */
    $entry = entryQuery()->id($entryModel->id)->one();

    return [$entry, $blockType];
}

/** @param array<string, mixed> $value */
function saveMatrixShape(EntryElement $entry, array $value): void
{
    $entry->setFieldValueFromRequest('shapeMatrix', $value);
    Elements::saveElement($entry);
}

/** @return list<string> */
function matrixShapeBlockUids(EntryElement $entry): array
{
    /** @var EntryElement $reloaded */
    $reloaded = entryQuery()->id($entry->id)->status(null)->one();

    return array_map(
        fn (EntryElement $block): string => $block->uid,
        array_values($reloaded->getFieldValue('shapeMatrix')->all()),
    );
}

beforeEach(fn () => actingAs(User::findOne()));

it('creates a block when the Form control prefixes both halves of the envelope', function () {
    [$entry, $blockType] = matrixShapeFixture();
    $uid = Str::uuid()->toString();

    // What NestedElementBlocksControl.vue and matrix-input.ce.ts post for a brand new block.
    saveMatrixShape($entry, [
        'entries' => ["uid:$uid" => ['type' => $blockType->handle, 'title' => 'Fresh']],
        'sortOrder' => ["uid:$uid"],
    ]);

    expect(matrixShapeBlockUids($entry))->toBe([$uid]);
});

it('creates a block when only the entries keys are prefixed', function () {
    [$entry, $blockType] = matrixShapeFixture();
    $uid = Str::uuid()->toString();

    // What `_components/fieldtypes/Matrix/block.twig` emits: prefixed entries keys,
    // bare sortOrder values.
    saveMatrixShape($entry, [
        'entries' => ["uid:$uid" => ['type' => $blockType->handle, 'title' => 'Fresh']],
        'sortOrder' => [$uid],
    ]);

    expect(matrixShapeBlockUids($entry))->toBe([$uid]);
});

it('keeps existing blocks while adding a new one', function () {
    [$entry, $blockType] = matrixShapeFixture();
    $existing = Str::uuid()->toString();

    saveMatrixShape($entry, [
        'entries' => ["uid:$existing" => ['type' => $blockType->handle, 'title' => 'First']],
        'sortOrder' => ["uid:$existing"],
    ]);

    $added = Str::uuid()->toString();

    saveMatrixShape($entry, [
        'entries' => [
            "uid:$existing" => ['type' => $blockType->handle, 'title' => 'First'],
            "uid:$added" => ['type' => $blockType->handle, 'title' => 'Second'],
        ],
        'sortOrder' => ["uid:$existing", "uid:$added"],
    ]);

    expect(matrixShapeBlockUids($entry))->toBe([$existing, $added]);
});

it('reorders blocks when only a prefixed sortOrder is posted', function () {
    [$entry, $blockType] = matrixShapeFixture();
    [$first, $second] = [Str::uuid()->toString(), Str::uuid()->toString()];

    saveMatrixShape($entry, [
        'entries' => [
            "uid:$first" => ['type' => $blockType->handle, 'title' => 'First'],
            "uid:$second" => ['type' => $blockType->handle, 'title' => 'Second'],
        ],
        'sortOrder' => ["uid:$first", "uid:$second"],
    ]);

    // A drag-sort posts the order without re-sending each block's data. Treating these
    // identities as element IDs would drop every block.
    saveMatrixShape($entry, ['sortOrder' => ["uid:$second", "uid:$first"]]);

    expect(matrixShapeBlockUids($entry))->toBe([$second, $first]);
});

it('retains nested cards when only a sibling field in an outer block is edited', function () {
    actingAs(User::findOne());

    $text = Field::factory()->create(['handle' => 'siblingText', 'type' => PlainText::class]);
    $cardType = EntryType::factory()->create(['handle' => 'nestedCardType', 'hasTitleField' => true]);
    $cards = Field::factory()->create([
        'handle' => 'nestedCards',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$cardType->id], 'viewMode' => Matrix::VIEW_MODE_CARDS],
    ]);
    $blockLayout = FieldLayout::factory()->withContentTab([
        new CustomField(config: ['fieldUid' => $text->uid]),
        new CustomField(config: ['fieldUid' => $cards->uid]),
    ]);
    $blockType = EntryType::factory()->withFieldLayout($blockLayout)
        ->create(['handle' => 'outerBlockType', 'hasTitleField' => true]);
    $outer = Field::factory()->create([
        'handle' => 'outerBlocks',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$blockType->id], 'viewMode' => Matrix::VIEW_MODE_BLOCKS],
    ]);
    $owner = entryQuery()->id(Entry::factory()
        ->withFieldLayout(FieldLayout::factory()->forField($outer))
        ->create()->id)->one();

    $blockUid = Str::uuid()->toString();
    $cardUid = Str::uuid()->toString();
    $owner->setFieldValueFromRequest('outerBlocks', [
        'entries' => ["uid:$blockUid" => [
            'type' => $blockType->handle,
            'title' => 'Outer block',
            'fields' => [
                'siblingText' => 'Before',
                'nestedCards' => [
                    'entries' => ["uid:$cardUid" => ['type' => $cardType->handle, 'title' => 'Keep me']],
                    'sortOrder' => [$cardUid],
                ],
            ],
        ]],
        'sortOrder' => [$blockUid],
    ]);
    expect(Elements::saveElement($owner))->toBeTrue();

    $owner = entryQuery()->id($owner->id)->one();
    $owner->setFieldValueFromRequest('outerBlocks', [
        'entries' => ["uid:$blockUid" => [
            'type' => $blockType->handle,
            'fields' => ['siblingText' => 'After'],
        ]],
        'sortOrder' => [$blockUid],
    ]);
    expect(Elements::saveElement($owner))->toBeTrue();

    $block = entryQuery()->fieldId($outer->id)->ownerId($owner->id)->status(null)->one();
    expect($block->getFieldValue('siblingText'))->toBe('After')
        ->and($block->getFieldValue('nestedCards')->status(null)->all())->toHaveCount(1);

    app(FieldLayoutCompiler::class)->compile($owner->getFieldLayout(), $owner, new UiContext(mode: ControlMode::ReadOnly));
    expect(SessionAuth::checkAuthorization("manageNestedElements::{$block->id}::field:nestedCards"))->toBeFalse();
});
