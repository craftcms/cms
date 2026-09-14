<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Enums\ElementActionContext;
use CraftCms\Cms\Element\Enums\MenuItemType;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\User\Models\User;

/** @return list<string> Labels, with separators spelled out. */
function descriptorList(ElementInterface $element, ElementActionContext $context): array
{
    return array_map(
        fn (array $item): string => ($item['type'] ?? null) === MenuItemType::HR->value
            ? '---'
            : (string) $item['label'],
        $element->actionMenuDescriptors($context),
    );
}

function descriptorLabels(ElementInterface $element, ElementActionContext $context): string
{
    return implode('|', descriptorList($element, $context));
}

function contextualEntry(): EntryElement
{
    return EntryElement::find()->id(Entry::factory()->title('Contextual')->create()->id)->one();
}

// Deleting the element is managing the element; inside a relation field the
// destructive action that belongs there is the field's own Remove.
it('offers deletion on the element’s own screen but not inside a field', function () {
    $this->actingAs(User::first());
    $element = contextualEntry();

    expect(descriptorLabels($element, ElementActionContext::Editor))->toContain('Delete')
        ->and(descriptorLabels($element, ElementActionContext::Field))->not->toContain('Delete');
});

it('keeps the element’s own actions in a field', function () {
    $this->actingAs(User::first());

    expect(descriptorLabels(contextualEntry(), ElementActionContext::Field))->not->toBeEmpty();
});

it('defaults to the editor context', function () {
    $this->actingAs(User::first());
    $element = contextualEntry();

    expect($element->actionMenuDescriptors())
        ->toBe($element->actionMenuDescriptors(ElementActionContext::Editor));
});

// Craft 5 shows these only when the asset is the element the editor has open,
// so they're absent from an index and a relation field alike.
it('drops volume and filesystem settings for an asset outside its editor', function () {
    $this->actingAs(User::first());
    $asset = Asset::factory()->createElement();

    $inEditor = descriptorLabels($asset, ElementActionContext::Editor);

    expect($inEditor)->toContain('Volume settings');

    foreach ([ElementActionContext::Index, ElementActionContext::Field] as $context) {
        expect(descriptorLabels($asset, $context))
            ->not->toContain('Volume settings')
            ->not->toContain('Filesystem settings');
    }
});

// The asset's own actions still belong in a field.
it('keeps an asset’s own actions inside a field', function () {
    $this->actingAs(User::first());
    $asset = Asset::factory()->createElement();

    expect(descriptorLabels($asset, ElementActionContext::Field))->toContain('Download');
});

// Craft 5 keeps Replace file out of chips and cards via the same flag.
it('drops Replace file for an asset inside a field', function () {
    $this->actingAs(User::first());
    $asset = Asset::factory()->createElement();

    expect(descriptorLabels($asset, ElementActionContext::Editor))->toContain('Replace file')
        ->and(descriptorLabels($asset, ElementActionContext::Field))->not->toContain('Replace file');
});

// Editing the image is a step up from previewing or downloading it.
it('separates Open in Image Editor from the actions above it', function () {
    $this->actingAs(User::first());
    $asset = Asset::factory()->createElement();

    $labels = descriptorList($asset, ElementActionContext::Field);
    $editor = array_search('Open in Image Editor', $labels, true);

    expect($editor)->not->toBeFalse()
        // A rule directly above it, and something for it to separate.
        ->and($labels[$editor - 1])->toBe('---')
        ->and($editor - 1)->toBeGreaterThan(0);
});
