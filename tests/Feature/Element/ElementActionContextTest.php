<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Enums\ElementActionContext;
use CraftCms\Cms\Element\Enums\MenuItemType;
use CraftCms\Cms\Element\Events\ElementActionMenuDescriptorsResolving;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\Event;

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

// Craft 5 offers "Delete draft" when a saved draft is open in the editor.
it('offers draft deletion when editing a draft', function () {
    $user = User::first();
    $this->actingAs($user);
    $draft = app(Drafts::class)->createDraft(contextualEntry(), $user->id);

    $deleteDraft = collect($draft->actionMenuDescriptors())->firstWhere('label', 'Delete draft');

    expect($deleteDraft)->not->toBeNull()
        ->and($deleteDraft['destructive'])->toBeTrue()
        ->and($deleteDraft['behavior']['actionUrl'])->toContain('elements/delete-draft')
        ->and($deleteDraft['behavior']['params']['draftId'])->toBe($draft->draftId)
        ->and(descriptorLabels($draft, ElementActionContext::Editor))->not->toContain('Delete entry');
});

it('keeps the element’s own actions in a field', function () {
    $this->actingAs(User::first());

    expect(descriptorLabels(contextualEntry(), ElementActionContext::Field))->not->toBeEmpty();
});

// The flag is the extension point Craft 5 documents on `Actionable`: every
// non-destructive item shows by default, and any item can opt in or out.
it('honours showInChips over the destructive default', function (array $item, bool $expected) {
    $this->actingAs(User::first());
    $entry = contextualEntry();
    $element = new class(['id' => $entry->id, 'siteId' => $entry->siteId, 'sectionId' => $entry->sectionId, 'typeId' => $entry->typeId, 'title' => $entry->title]) extends EntryElement
    {
        /** @var list<array<string, mixed>> */
        public array $extraItems = [];

        protected function extraActionMenuDescriptors(ElementActionContext $context = ElementActionContext::Editor): array
        {
            return $this->extraItems;
        }
    };
    $element->extraItems = [$item];

    expect(in_array($item['label'], descriptorList($element, ElementActionContext::Field), true))->toBe($expected)
        ->and(descriptorList($element, ElementActionContext::Editor))->toContain($item['label']);
})->with([
    'plain item shows' => [['label' => 'View'], true],
    'destructive item hides' => [['label' => 'Delete', 'destructive' => true], false],
    'opted out hides' => [['label' => 'Replace file', 'showInChips' => false], false],
    'destructive but opted in shows' => [
        ['label' => 'Odd one', 'destructive' => true, 'showInChips' => true],
        true,
    ],
]);

it('includes items added by event listeners, filtered for the context', function () {
    $this->actingAs(User::first());
    $element = contextualEntry();

    Event::listen(function (ElementActionMenuDescriptorsResolving $event) {
        $event->items[] = ['label' => 'Sync', 'behavior' => ['type' => 'link', 'href' => 'https://example.com']];
        $event->items[] = ['label' => 'Purge', 'destructive' => true, 'behavior' => ['type' => 'link', 'href' => 'https://example.com']];
    });

    expect(descriptorList($element, ElementActionContext::Editor))->toContain('Sync', 'Purge')
        ->and(descriptorList($element, ElementActionContext::Field))->toContain('Sync')->not->toContain('Purge');
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
