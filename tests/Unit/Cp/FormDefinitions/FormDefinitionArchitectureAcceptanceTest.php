<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\FormDefinitions\Condition;
use CraftCms\Cms\Cp\FormDefinitions\Elements\FormElement;
use CraftCms\Cms\Cp\FormDefinitions\Elements\InputElement;
use CraftCms\Cms\Cp\FormDefinitions\Elements\TextInput;
use CraftCms\Cms\Cp\FormDefinitions\FormDefinition;
use CraftCms\Cms\Cp\FormDefinitions\FormElementTypes;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\FieldLayout\Contracts\FieldLayoutFormElementProviderInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutElement;
use CraftCms\Cms\FieldLayout\FieldLayoutFormDefinitionContext;
use CraftCms\Cms\FieldLayout\FieldLayoutFormDefinitionProjector;
use CraftCms\Cms\FieldLayout\FieldLayoutFormElementContext;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;

beforeEach(function () {
    app()->forgetInstance(FormElementTypes::class);
    app()->forgetInstance(TestPlugin::class);
});

it('matches the shared architecture fixture through public registration and projection', function () {
    $plugin = TestPlugin::create([
        'handle' => 'color-tools',
        'name' => 'Color Tools',
        'packageName' => 'vendor/color-tools',
    ]);
    $plugin->registerFormElementTypes(ArchitectureAcceptanceColorMap::class);
    app(FormElementTypes::class)->register(ArchitectureAcceptanceLegacyIsland::class);

    $layout = new FieldLayout(['uid' => 'article-layout']);
    $layout->setTabs([
        new FieldLayoutTab([
            'uid' => 'content-tab',
            'name' => 'Content',
            'layout' => $layout,
            'elements' => [
                new CustomField(new ArchitectureAcceptanceField([
                    'uid' => 'title-field',
                    'handle' => 'title',
                    'name' => 'Title',
                ]), [
                    'uid' => 'title-layout-element',
                    'width' => 50,
                ]),
                new ArchitectureAcceptanceLegacyLayoutElement([
                    'uid' => 'legacy-layout-element',
                ]),
                new CustomField(new ArchitectureAcceptanceField([
                    'uid' => 'summary-field',
                    'handle' => 'summary',
                    'name' => 'Summary',
                ]), [
                    'uid' => 'summary-layout-element',
                    'width' => 100,
                ]),
            ],
        ]),
    ]);

    $actual = [
        'ordinary' => FormDefinition::make([
            TextInput::make('title')
                ->key('ordinary-title')
                ->label('Title')
                ->instructions('Shown in article listings.')
                ->placeholder('Article title')
                ->width(50)
                ->attributes(['autocomplete' => 'off']),
        ])->toArray(),
        'conditionalVisibility' => FormDefinition::make([
            TextInput::make('mode')
                ->key('visibility-mode')
                ->label('Mode'),
            TextInput::make('details')
                ->key('visibility-details')
                ->label('Details')
                ->visibleWhen(Condition::equals('mode', 'advanced')),
        ])->toArray(),
        'plugin' => FormDefinition::make([
            ArchitectureAcceptanceColorMap::make('palette')
                ->key('plugin-palette')
                ->label('Palette'),
        ])->toArray(),
        'fieldLayout' => new FieldLayoutFormDefinitionProjector()->project(
            $layout,
            new FieldLayoutFormDefinitionContext,
        )->toArray(),
    ];
    $fixture = json_decode(
        file_get_contents(dirname(__DIR__, 4).'/resources/js/form-definitions/fixtures/architecture-acceptance.json'),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    expect($actual)->toBe($fixture);
});

class ArchitectureAcceptanceColorMap extends InputElement
{
    public static function type(): string
    {
        return 'color-tools:color-map';
    }

    #[Override]
    protected function props(): array
    {
        return ['colors' => ['red', 'blue']];
    }
}

class ArchitectureAcceptanceField extends Field implements FieldLayoutFormElementProviderInterface
{
    public function formElement(FieldLayoutFormElementContext $context): ?InputElement
    {
        return TextInput::make($context->inputName ?? throw new LogicException('Input Name is required.'))
            ->placeholder('Projected '.$this->handle);
    }
}

class ArchitectureAcceptanceLegacyIsland extends FormElement
{
    public static function make(): self
    {
        return new self;
    }

    public static function type(): string
    {
        return 'application:legacy-island';
    }

    #[Override]
    protected function props(): array
    {
        return ['label' => 'Legacy rating'];
    }
}

class ArchitectureAcceptanceLegacyLayoutElement extends FieldLayoutElement implements FieldLayoutFormElementProviderInterface
{
    public function selectorHtml(): string
    {
        return '';
    }

    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        return null;
    }

    public function formElement(FieldLayoutFormElementContext $context): ?FormElement
    {
        return ArchitectureAcceptanceLegacyIsland::make();
    }
}
