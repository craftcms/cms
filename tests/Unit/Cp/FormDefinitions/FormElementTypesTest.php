<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\ComponentRegistry;
use CraftCms\Cms\Cp\Components\Field;
use CraftCms\Cms\Cp\Components\FormContainer;
use CraftCms\Cms\Cp\Components\ScalarInput;
use CraftCms\Cms\Cp\Components\TextInput;
use CraftCms\Cms\Cp\Components\ViewComponent;
use CraftCms\Cms\Cp\FormDefinitions\Contracts\ProjectableFormElement;
use CraftCms\Cms\Cp\FormDefinitions\Data\FormElementData;
use CraftCms\Cms\Cp\FormDefinitions\Data\PluginData;
use CraftCms\Cms\Cp\FormDefinitions\FormDefinition;
use CraftCms\Cms\Cp\FormDefinitions\FormElementTypes;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;

beforeEach(function () {
    app()->forgetInstance(ComponentRegistry::class);
    app()->forgetInstance(FormElementTypes::class);
    app()->forgetInstance(TestPlugin::class);
    app()->forgetInstance(OtherTestPlugin::class);
});

it('registers and projects a plugin CP UI Component with derived ownership', function () {
    $plugin = TestPlugin::create([
        'handle' => 'color-tools',
        'name' => 'Color Tools',
        'packageName' => 'vendor/color-tools',
    ]);
    $components = app(ComponentRegistry::class);

    $components->register('palette-picker', ColorMapComponent::class);
    $plugin->registerFormElementTypes(ColorMapComponent::class);

    expect($components->make('palette-picker')->toHtml())
        ->toContain('<color-tools-map')
        ->and(ColorMapComponent::formElementType())->toBe('color-tools:color-map')
        ->and(FormDefinition::make([
            Field::make()->input(
                ColorMapComponent::make()
                    ->name('palette')
                    ->colors(['red', 'blue']),
            ),
        ])->toArray())->toBe([
            'elements' => [[
                'type' => 'craft:field',
                'children' => [[
                    'type' => 'color-tools:color-map',
                    'name' => 'palette',
                    'props' => ['colors' => ['red', 'blue']],
                    'plugin' => [
                        'handle' => 'color-tools',
                        'name' => 'Color Tools',
                        'packageName' => 'vendor/color-tools',
                    ],
                ]],
            ]],
        ]);
});

it('validates a complete registration batch before committing it', function () {
    $plugin = TestPlugin::create([
        'handle' => 'color-tools',
        'name' => 'Color Tools',
        'packageName' => 'vendor/color-tools',
    ]);

    expect(fn () => $plugin->registerFormElementTypes(
        ColorMapComponent::class,
        NonProjectableComponent::class,
    ))->toThrow(InvalidArgumentException::class, 'must extend');

    expect(fn () => FormDefinition::make([
        Field::make()->input(ColorMapComponent::make()->name('palette')),
    ])->toArray())->toThrow(InvalidArgumentException::class, 'unknown or unregistered Form Element Type');
});

it('supports ownerless application component registrations', function () {
    app(FormElementTypes::class)->register(ApplicationComponent::class);

    expect(FormDefinition::make([
        Field::make()->input(ApplicationComponent::make()->name('setting')),
    ])->toArray())->toBe([
        'elements' => [[
            'type' => 'craft:field',
            'children' => [[
                'type' => 'application:control',
                'name' => 'setting',
            ]],
        ]],
    ]);
});

it('rejects ownerless registrations outside the CP UI Component catalog', function () {
    expect(fn () => app(FormElementTypes::class)->register(NonComponentProjectable::class))
        ->toThrow(
            InvalidArgumentException::class,
            sprintf(
                '%s must extend %s and implement %s.',
                NonComponentProjectable::class,
                ViewComponent::class,
                ProjectableFormElement::class,
            ),
        );
});

it('allows an identical registration and rejects a different claimant', function () {
    $plugin = TestPlugin::create([
        'handle' => 'color-tools',
        'name' => 'Color Tools',
        'packageName' => 'vendor/color-tools',
    ]);

    $plugin->registerFormElementTypes(ColorMapComponent::class);

    expect(fn () => $plugin->registerFormElementTypes(ColorMapComponent::class))
        ->not->toThrow(Throwable::class);

    expect(fn () => $plugin->registerFormElementTypes(ConflictingColorMapComponent::class))
        ->toThrow(
            InvalidArgumentException::class,
            sprintf(
                'Form Element Type "color-tools:color-map" is already registered by %s for plugin color-tools; %s for plugin color-tools cannot claim it.',
                ColorMapComponent::class,
                ConflictingColorMapComponent::class,
            ),
        );
});

it('rejects the same class when a different plugin claims it', function () {
    $plugin = TestPlugin::create([
        'handle' => 'color-tools',
        'name' => 'Color Tools',
        'packageName' => 'vendor/color-tools',
    ]);
    $otherPlugin = OtherTestPlugin::create([
        'handle' => 'other-tools',
        'name' => 'Other Tools',
        'packageName' => 'vendor/other-tools',
    ]);

    $plugin->registerFormElementTypes(ColorMapComponent::class);

    expect(fn () => $otherPlugin->registerFormElementTypes(ColorMapComponent::class))
        ->toThrow(
            InvalidArgumentException::class,
            sprintf(
                'already registered by %s for plugin color-tools; %s for plugin other-tools cannot claim it',
                ColorMapComponent::class,
                ColorMapComponent::class,
            ),
        );
});

it('rejects projection through a type registered to another component class', function () {
    $plugin = TestPlugin::create([
        'handle' => 'color-tools',
        'name' => 'Color Tools',
        'packageName' => 'vendor/color-tools',
    ]);

    $plugin->registerFormElementTypes(ColorMapComponent::class);

    expect(fn () => FormDefinition::make([
        Field::make()->input(ConflictingColorMapComponent::make()->name('palette')),
    ])->toArray())->toThrow(
        InvalidArgumentException::class,
        sprintf(
            'Form Element Type "color-tools:color-map" is registered by %s for plugin color-tools; %s cannot project it.',
            ColorMapComponent::class,
            ConflictingColorMapComponent::class,
        ),
    );
});

it('rejects a component that projects a type other than its declared type', function () {
    $plugin = TestPlugin::create([
        'handle' => 'color-tools',
        'name' => 'Color Tools',
        'packageName' => 'vendor/color-tools',
    ]);

    $plugin->registerFormElementTypes(MismatchedTypeComponent::class);

    expect(fn () => FormDefinition::make([
        Field::make()->input(MismatchedTypeComponent::make()->name('palette')),
    ])->toArray())->toThrow(
        InvalidArgumentException::class,
        sprintf(
            '%s declares Form Element Type "color-tools:mismatch" but projected "craft:text-input".',
            MismatchedTypeComponent::class,
        ),
    );
});

it('identifies Craft when a component class attempts to project a core type', function () {
    expect(fn () => FormDefinition::make([
        Field::make()->input(CoreTextInputSubclass::make()->name('title')),
    ])->toArray())->toThrow(
        InvalidArgumentException::class,
        sprintf(
            'Form Element Type "craft:text-input" is registered by %s for Craft; %s cannot project it.',
            TextInput::class,
            CoreTextInputSubclass::class,
        ),
    );
});

it('projects registered plugin containers with children', function () {
    $plugin = TestPlugin::create([
        'handle' => 'color-tools',
        'name' => 'Color Tools',
        'packageName' => 'vendor/color-tools',
    ]);

    $plugin->registerFormElementTypes(ColorMapComponent::class, PaletteGroupComponent::class);

    expect(FormDefinition::make([
        PaletteGroupComponent::make([
            Field::make()->input(ColorMapComponent::make()->name('palette')),
        ]),
    ])->toArray())->toBe([
        'elements' => [[
            'type' => 'color-tools:palette-group',
            'children' => [[
                'type' => 'craft:field',
                'children' => [[
                    'type' => 'color-tools:color-map',
                    'name' => 'palette',
                    'plugin' => [
                        'handle' => 'color-tools',
                        'name' => 'Color Tools',
                        'packageName' => 'vendor/color-tools',
                    ],
                ]],
            ]],
            'plugin' => [
                'handle' => 'color-tools',
                'name' => 'Color Tools',
                'packageName' => 'vendor/color-tools',
            ],
        ]],
    ]);
});

it('reserves core types and rejects malformed or invalid component classes', function (string $class, string $message) {
    $plugin = TestPlugin::create([
        'handle' => 'color-tools',
        'name' => 'Color Tools',
        'packageName' => 'vendor/color-tools',
    ]);

    expect(fn () => $plugin->registerFormElementTypes($class))
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'core namespace' => [ReservedComponent::class, 'The "craft" Form Element namespace is reserved.'],
    'uppercase type' => [UppercaseComponent::class, 'must be a lowercase namespaced identifier'],
    'non-projectable component' => [NonProjectableComponent::class, 'must extend'],
    'projectable non-component' => [NonComponentProjectable::class, 'must extend'],
]);

it('rejects incomplete plugin ownership metadata', function (array $config, string $message) {
    $plugin = TestPlugin::create($config);

    expect(fn () => $plugin->registerFormElementTypes(ColorMapComponent::class))
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'missing name' => [[
        'handle' => 'color-tools',
        'packageName' => 'vendor/color-tools',
    ], 'must define its name and Composer package'],
    'missing package' => [[
        'handle' => 'color-tools',
        'name' => 'Color Tools',
        'packageName' => null,
    ], 'must define its name and Composer package'],
]);

class ColorMapComponent extends ScalarInput
{
    private array $colors = [];

    public static function formElementType(): string
    {
        return 'color-tools:color-map';
    }

    public function colors(array $colors): static
    {
        $this->colors = $colors;

        return $this;
    }

    #[Override]
    public function toFormElementData(): FormElementData
    {
        $data = parent::toFormElementData();

        return new FormElementData(
            type: $data->type,
            name: $data->name,
            props: $data->props,
            attributes: $data->attributes,
            plugin: new PluginData('spoofed', 'Spoofed', 'spoofed/package'),
        );
    }

    #[Override]
    protected function tagName(): string
    {
        return 'color-tools-map';
    }

    #[Override]
    protected function formElementProps(): array
    {
        return $this->colors === [] ? [] : ['colors' => $this->colors];
    }
}

class ConflictingColorMapComponent extends ColorMapComponent {}

class MismatchedTypeComponent extends ColorMapComponent
{
    #[Override]
    public static function formElementType(): string
    {
        return 'color-tools:mismatch';
    }

    #[Override]
    public function toFormElementData(): FormElementData
    {
        $data = parent::toFormElementData();

        return new FormElementData(
            type: 'craft:text-input',
            name: $data->name,
        );
    }
}

class CoreTextInputSubclass extends TextInput {}

class ReservedComponent extends ColorMapComponent
{
    #[Override]
    public static function formElementType(): string
    {
        return 'craft:plugin-control';
    }
}

class UppercaseComponent extends ColorMapComponent
{
    #[Override]
    public static function formElementType(): string
    {
        return 'ColorTools:color-map';
    }
}

class NonProjectableComponent extends ViewComponent
{
    #[Override]
    protected function tagName(): string
    {
        return 'color-tools-invalid';
    }
}

class NonComponentProjectable implements ProjectableFormElement
{
    public static function formElementType(): string
    {
        return 'color-tools:invalid';
    }

    public static function isFormElementContainer(): bool
    {
        return false;
    }

    public function toFormElementData(): FormElementData
    {
        return new FormElementData(type: self::formElementType());
    }
}

class ApplicationComponent extends ColorMapComponent
{
    #[Override]
    public static function formElementType(): string
    {
        return 'application:control';
    }
}

class PaletteGroupComponent extends FormContainer
{
    public static function make(iterable $children = []): static
    {
        return parent::make()->children($children);
    }

    public static function formElementType(): string
    {
        return 'color-tools:palette-group';
    }

    #[Override]
    protected function tagName(): string
    {
        return 'color-tools-palette-box';
    }
}

class OtherTestPlugin extends TestPlugin {}
