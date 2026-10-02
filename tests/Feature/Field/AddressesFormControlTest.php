<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Field\Addresses;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Form\Controls\NestedElements;
use CraftCms\Cms\Http\Controllers\Elements\ElementIndex\ElementIndexController;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

it('supports only cards and index view modes', function () {
    foreach ([Addresses::VIEW_MODE_CARDS, Addresses::VIEW_MODE_INDEX] as $viewMode) {
        $field = new Addresses([
            'name' => 'Addresses',
            'handle' => 'addresses',
            'viewMode' => $viewMode,
        ]);

        expect($field->validate())->toBeTrue();
    }

    $field = new Addresses([
        'name' => 'Addresses',
        'handle' => 'addresses',
        'viewMode' => 'blocks',
    ]);

    expect($field->validate())->toBeFalse()
        ->and($field->errors()->has('viewMode'))->toBeTrue();
});

/**
 * @return array{owner: EntryElement, field: Addresses}
 */
function addressesFormControlFixture(string $viewMode): array
{
    $fixture = Entry::factory()
        ->withField('addressesField', Addresses::class, ['viewMode' => $viewMode])
        ->createElementWithFields();

    /** @var EntryElement $owner */
    $owner = EntryElement::find()->id($fixture->element->id)->status(null)->one();
    /** @var Addresses $field */
    $field = Fields::getFieldById($fixture->field('addressesField')->id);

    return ['owner' => $owner, 'field' => $field];
}

it('manages addresses as cards outside the owner form', function () {
    ['owner' => $owner, 'field' => $field] = addressesFormControlFixture(Addresses::VIEW_MODE_CARDS);

    $control = $field->formControl(new FieldContext(path: 'addressesField', element: $owner));
    $props = $control->props();

    expect($control)->toBeInstanceOf(NestedElements::class)
        ->and($control->component())->toBe('craft:nested-elements')
        ->and($props['viewMode'])->toBe('cards-grid')
        ->and($props['unavailableMessage'])->toBeNull()
        ->and($props['manager']['elementType'])->toBe(Address::class)
        ->and($props['manager']['attribute'])->toBe('field:addressesField')
        ->and($props['manager']['showInGrid'])->toBeTrue()
        ->and($props['manager']['canCreate'])->toBeTrue()
        ->and($props['manager']['sortable'])->toBeTrue()
        ->and($props['cards'])->toBe([]);
});

it('manages addresses in an embedded index with the field’s own index config', function () {
    ['owner' => $owner, 'field' => $field] = addressesFormControlFixture(Addresses::VIEW_MODE_INDEX);

    $props = $field->formControl(new FieldContext(path: 'addressesField', element: $owner))->props();

    expect($props['viewMode'])->toBe('index')
        ->and($props['index'])->toHaveKeys(['indexSettings', 'initial'])
        ->and($props['manager'])->not->toHaveKey('indexSettings');

    // Later index requests resolve the same config through the field.
    $response = postJson(action([ElementIndexController::class, 'getElements']), [
        ...$props['manager'],
        'elementType' => Address::class,
        'context' => ElementSources::CONTEXT_EMBEDDED_INDEX,
        'source' => '__IMP__',
    ])->assertOk();

    expect(array_column($response->json('viewModes'), 'mode'))->toBe(['cards']);
});

it('explains that addresses need a saved owner', function () {
    ['field' => $field] = addressesFormControlFixture(Addresses::VIEW_MODE_CARDS);

    $props = $field->formControl(new FieldContext(path: 'addressesField', element: new EntryElement))->props();

    expect($props['manager'])->toBeNull()
        ->and($props['unavailableMessage'])->toBe('Addresses can only be created after the entry has been saved.');
});
