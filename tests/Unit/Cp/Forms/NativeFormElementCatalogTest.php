<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\ComponentRegistry;
use CraftCms\Cms\Cp\Components\ViewComponent;
use CraftCms\Cms\Cp\Forms\Contracts\FormElement;
use CraftCms\Cms\Cp\Forms\FormElementTypes;

it('locks every native Form Element Type to one CP UI Component', function () {
    $catalog = json_decode(
        file_get_contents(dirname(__DIR__, 4).'/resources/js/forms/native-form-element-catalog.json'),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );
    $components = app(ComponentRegistry::class)->nativeComponents();
    $registrations = app(FormElementTypes::class)->nativeRegistrations();
    $actual = [];

    expect(array_values(array_column($registrations, 'class')))->toBe(array_values(array_filter(
        $components,
        fn (string $class): bool => is_subclass_of($class, FormElement::class),
    )));

    foreach ($registrations as $type => $registration) {
        expect(is_subclass_of($registration['class'], ViewComponent::class))->toBeTrue()
            ->and(is_subclass_of($registration['class'], FormElement::class))->toBeTrue()
            ->and($registration['class']::formElementType())->toBe($type)
            ->and($registration['class']::isFormElementContainer())->toBe($registration['container']);

        $actual[] = [
            'type' => $type,
            'container' => $registration['container'],
        ];
    }

    usort($actual, fn (array $left, array $right): int => $left['type'] <=> $right['type']);

    expect($actual)->toBe($catalog);
});

it('does not expose the retired native Form Element authoring hierarchy', function (string $class) {
    expect(class_exists($class))->toBeFalse();
})->with([
    'Form Element' => 'CraftCms\\Cms\\Cp\\Forms\\Elements\\FormElement',
    'Input Element' => 'CraftCms\\Cms\\Cp\\Forms\\Elements\\InputElement',
    'Form Container' => 'CraftCms\\Cms\\Cp\\Forms\\Elements\\FormContainer',
    'Lightswitch Input' => 'CraftCms\\Cms\\Cp\\Forms\\Elements\\LightswitchInput',
]);
