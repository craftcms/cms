<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Form\Controls\Matrix as MatrixControl;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;

/**
 * The per-block "⋮" menu is built server-side so both render paths get the same
 * items and the permission checks stay off the client.
 *
 * @see Matrix::blockActions()
 */

/** @return array{0: EntryElement, 1: string} */
function matrixActionsFixture(): array
{
    $innerField = Field::factory()->create([
        'name' => 'Inner Text',
        'handle' => 'actionsInnerText',
        'type' => PlainText::class,
    ]);

    $blockType = EntryType::factory()
        ->withField($innerField)
        ->create([
            'name' => 'Action Block',
            'handle' => 'actionBlock',
            'hasTitleField' => true,
        ]);

    $matrixField = Field::factory()->create([
        'name' => 'Actions Matrix',
        'handle' => 'actionsMatrix',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$blockType->id]],
    ]);

    $entryModel = Entry::factory()
        ->withFieldLayout(FieldLayout::factory()->forField($matrixField))
        ->create();

    /** @var EntryElement $entry */
    $entry = entryQuery()->id($entryModel->id)->one();
    $uid = Str::uuid()->toString();

    $entry->setFieldValueFromRequest('actionsMatrix', [
        'entries' => ["uid:$uid" => ['type' => $blockType->handle, 'title' => 'Block']],
        'sortOrder' => ["uid:$uid"],
    ]);
    Elements::saveElement($entry);

    /** @var EntryElement $reloaded */
    $reloaded = entryQuery()->id($entry->id)->status(null)->one();

    return [$reloaded, $uid];
}

function matrixActionsControl(EntryElement $owner): MatrixControl
{
    /** @var Matrix $field */
    $field = app(Fields::class)->getFieldByHandle('actionsMatrix');

    /** @var MatrixControl $control */
    $control = $field->formControl(new FieldContext(
        path: 'actionsMatrix',
        value: $owner->getFieldValue('actionsMatrix'),
        element: $owner,
    ));

    return $control;
}

/** @return list<string> */
function matrixActionLabels(MatrixControl $control, string $uid): array
{
    $actions = $control->props($control->getValue())['blocks'][$uid]['actions'] ?? [];

    return array_values(array_filter(array_map(
        fn (array $item): ?string => $item['label'] ?? null,
        $actions,
    )));
}

beforeEach(fn () => actingAs(User::findOne()));

it('ships each block with the state it needs to render', function () {
    [$owner, $uid] = matrixActionsFixture();

    expect(matrixActionsControl($owner)->getValue()['entries'][$uid])
        ->toBe(['type' => 'actionBlock', 'enabled' => true, 'collapsed' => false]);
});

it('builds a menu for each block', function () {
    [$owner, $uid] = matrixActionsFixture();
    $labels = matrixActionLabels(matrixActionsControl($owner), $uid);

    expect($labels)
        ->toContain('Collapse')
        ->toContain('Expand')
        ->toContain('Disable')
        ->toContain('Enable')
        ->toContain('Delete')
        ->toContain('Add Action Block above')
        // Needs a saved entry to have a CP edit URL to point at.
        ->toContain('Open in a new tab');
});

it('keeps a block collapsed across a save', function () {
    [$owner, $uid] = matrixActionsFixture();

    // `collapsed` has no column — it only survives if the posted value is echoed
    // back onto the entry, or an autosave springs every collapsed block open.
    $owner->setFieldValueFromRequest('actionsMatrix', [
        'entries' => [$uid => ['type' => 'actionBlock', 'collapsed' => '1']],
        'sortOrder' => [$uid],
    ]);
    Elements::saveElement($owner);

    expect(matrixActionsControl($owner)->getValue()['entries'][$uid]['collapsed'])
        ->toBeTrue();
});

it('disables a block when the posted value says so', function () {
    [$owner, $uid] = matrixActionsFixture();

    $owner->setFieldValueFromRequest('actionsMatrix', [
        'entries' => [$uid => ['type' => 'actionBlock', 'enabled' => '']],
        'sortOrder' => [$uid],
    ]);
    Elements::saveElement($owner);

    /** @var EntryElement $reloaded */
    $reloaded = entryQuery()->id($owner->id)->status(null)->one();

    expect(matrixActionsControl($reloaded)->getValue()['entries'][$uid]['enabled'])
        ->toBeFalse();
});
