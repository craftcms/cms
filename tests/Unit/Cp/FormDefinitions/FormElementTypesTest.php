<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\FormDefinitions\Data\FormElementData;
use CraftCms\Cms\Cp\FormDefinitions\Data\PluginData;
use CraftCms\Cms\Cp\FormDefinitions\Elements\FormElement;
use CraftCms\Cms\Cp\FormDefinitions\Elements\InputElement;
use CraftCms\Cms\Cp\FormDefinitions\FormDefinition;
use CraftCms\Cms\Cp\FormDefinitions\FormElementTypes;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;

beforeEach(function () {
    app()->forgetInstance(FormElementTypes::class);
    app()->forgetInstance(TestPlugin::class);
    app()->forgetInstance(OtherTestPlugin::class);
});

it('registers and projects a plugin Form Element with derived ownership', function () {
    $plugin = TestPlugin::create([
        'handle' => 'color-tools',
        'name' => 'Color Tools',
        'packageName' => 'vendor/color-tools',
    ]);

    $plugin->registerFormElementTypes(ColorMapElement::class);

    expect(FormDefinition::make([ColorMapElement::make('palette')])->toArray())
        ->toBe([
            'elements' => [[
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
        ]);
});

it('validates a complete registration batch before committing it', function () {
    $plugin = TestPlugin::create([
        'handle' => 'color-tools',
        'name' => 'Color Tools',
        'packageName' => 'vendor/color-tools',
    ]);

    expect(fn () => $plugin->registerFormElementTypes(
        ColorMapElement::class,
        InvalidFormElement::class,
    ))->toThrow(InvalidArgumentException::class, 'must extend');

    expect(fn () => FormDefinition::make([ColorMapElement::make('palette')])->toArray())
        ->toThrow(InvalidArgumentException::class, 'unknown or unregistered Form Element Type');
});

it('supports ownerless application registrations', function () {
    app(FormElementTypes::class)->register(ApplicationFormElement::class);

    expect(FormDefinition::make([ApplicationFormElement::make('setting')])->toArray())
        ->toBe([
            'elements' => [[
                'type' => 'craft:field',
                'children' => [[
                    'type' => 'application:control',
                    'name' => 'setting',
                ]],
            ]],
        ]);
});

it('allows an identical registration and rejects a different claimant', function () {
    $plugin = TestPlugin::create([
        'handle' => 'color-tools',
        'name' => 'Color Tools',
        'packageName' => 'vendor/color-tools',
    ]);

    $plugin->registerFormElementTypes(ColorMapElement::class);

    expect(fn () => $plugin->registerFormElementTypes(ColorMapElement::class))
        ->not->toThrow(Throwable::class);

    expect(fn () => $plugin->registerFormElementTypes(ConflictingColorMapElement::class))
        ->toThrow(
            InvalidArgumentException::class,
            sprintf(
                'Form Element Type "color-tools:color-map" is already registered by %s for plugin color-tools; %s for plugin color-tools cannot claim it.',
                ColorMapElement::class,
                ConflictingColorMapElement::class,
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

    $plugin->registerFormElementTypes(ColorMapElement::class);

    expect(fn () => $otherPlugin->registerFormElementTypes(ColorMapElement::class))
        ->toThrow(
            InvalidArgumentException::class,
            sprintf(
                'already registered by %s for plugin color-tools; %s for plugin other-tools cannot claim it',
                ColorMapElement::class,
                ColorMapElement::class,
            ),
        );
});

it('projects registered plugin containers with children', function () {
    $plugin = TestPlugin::create([
        'handle' => 'color-tools',
        'name' => 'Color Tools',
        'packageName' => 'vendor/color-tools',
    ]);

    $plugin->registerFormElementTypes(ColorMapElement::class, PaletteGroupElement::class);

    expect(FormDefinition::make([
        PaletteGroupElement::make([ColorMapElement::make('palette')]),
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

it('reserves core types and rejects malformed public types', function (string $class, string $message) {
    $plugin = TestPlugin::create([
        'handle' => 'color-tools',
        'name' => 'Color Tools',
        'packageName' => 'vendor/color-tools',
    ]);

    expect(fn () => $plugin->registerFormElementTypes($class))
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'core namespace' => [ReservedFormElement::class, 'The "craft" Form Element namespace is reserved.'],
    'uppercase type' => [UppercaseFormElement::class, 'must be a lowercase namespaced identifier'],
]);

class ColorMapElement extends InputElement
{
    public static function type(): string
    {
        return 'color-tools:color-map';
    }

    #[Override]
    public function toData(): FormElementData
    {
        $data = parent::toData();

        return new FormElementData(
            type: 'craft:field',
            width: $data->width,
            props: $data->props,
            children: [new FormElementData(
                type: static::type(),
                name: $this->name,
                plugin: new PluginData('spoofed', 'Spoofed', 'spoofed/package'),
            )],
            visibleWhen: $data->visibleWhen,
        );
    }
}

class ConflictingColorMapElement extends FormElement
{
    public static function type(): string
    {
        return 'color-tools:color-map';
    }
}

class ReservedFormElement extends FormElement
{
    public static function type(): string
    {
        return 'craft:plugin-control';
    }
}

class UppercaseFormElement extends FormElement
{
    public static function type(): string
    {
        return 'ColorTools:color-map';
    }
}

class InvalidFormElement
{
    public static function type(): string
    {
        return 'color-tools:invalid';
    }
}

class ApplicationFormElement extends ColorMapElement
{
    #[Override]
    public static function type(): string
    {
        return 'application:control';
    }
}

class PaletteGroupElement extends FormElement
{
    /** @param list<FormElement> $elements */
    private function __construct(private readonly array $elements)
    {
        parent::__construct();
    }

    /** @param list<FormElement> $elements */
    public static function make(array $elements): self
    {
        return new self($elements);
    }

    public static function type(): string
    {
        return 'color-tools:palette-group';
    }

    #[Override]
    public static function isContainer(): bool
    {
        return true;
    }

    #[Override]
    protected function children(): array
    {
        return $this->elements;
    }
}

class OtherTestPlugin extends TestPlugin {}
