<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\ComponentRegistry;
use CraftCms\Cms\Cp\Components\Field;
use CraftCms\Cms\Cp\Components\FieldLayoutDesigner;
use CraftCms\Cms\Cp\FieldLayoutDesigner\FieldLayoutDesigner as Designer;
use CraftCms\Cms\Cp\Forms\Form;
use CraftCms\Cms\Cp\Forms\FormElementTypes;
use CraftCms\Cms\FieldLayout\FieldLayout;

it('projects the existing field layout designer into Forms', function () {
    $designer = new class extends Designer
    {
        public array $designerConfig = [];

        public array $generatedFieldsConfig = [];

        public function html(FieldLayout $fieldLayout, array $config = []): string
        {
            $this->designerConfig = $config;

            return '<craft-field-layout-designer></craft-field-layout-designer>';
        }

        public function generatedFieldsTableHtml(FieldLayout $fieldLayout, array $config = []): string
        {
            $this->generatedFieldsConfig = $config;

            return '<craft-generated-fields-table></craft-generated-fields-table>';
        }
    };
    app()->instance(Designer::class, $designer);
    $fieldLayout = new FieldLayout(['uid' => 'content-layout']);
    $form = Form::make([
        Field::make(FieldLayoutDesigner::make()
            ->name('fieldLayouts.content-layout')
            ->fieldLayout($fieldLayout)
            ->withGeneratedFields()
            ->readOnly())
            ->label('Field Layout'),
    ]);

    expect($form->toArray())->toBe([
        'elements' => [[
            'type' => 'craft:field',
            'props' => ['label' => 'Field Layout'],
            'children' => [[
                'type' => 'craft:field-layout-designer',
                'name' => 'fieldLayouts.content-layout',
                'props' => [
                    'designerHtml' => '<craft-field-layout-designer></craft-field-layout-designer>',
                    'generatedFieldsHtml' => '<craft-generated-fields-table></craft-generated-fields-table>',
                ],
            ]],
        ]],
    ])->and($designer->designerConfig)->toBe([
        'id' => 'fld-content-layout',
        'disabled' => true,
    ])->and($designer->generatedFieldsConfig)->toBe([
        'id' => 'generated-fields-table-fld-content-layout',
        'disabled' => true,
    ])->and(app(ComponentRegistry::class)->make('field-layout-designer'))->toBeInstanceOf(FieldLayoutDesigner::class)
        ->and(app(FormElementTypes::class)->isRegistered(FieldLayoutDesigner::formElementType()))->toBeTrue();
});

it('requires a name and field layout for Form output', function (FieldLayoutDesigner $component, string $option) {
    expect(fn () => Form::make([
        Field::make($component),
    ])->toArray())->toThrow(
        InvalidArgumentException::class,
        sprintf('%s option "%s" is not supported for Form output.', FieldLayoutDesigner::class, $option),
    );
})->with([
    'name' => [fn () => FieldLayoutDesigner::make()->fieldLayout(new FieldLayout), 'name'],
    'field layout' => [fn () => FieldLayoutDesigner::make()->name('fieldLayout'), 'fieldLayout'],
]);
