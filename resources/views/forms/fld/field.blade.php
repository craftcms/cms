@php
    /**
     * Field layout designer form field.
     *
     * Split out of the `forms.fieldLayoutDesignerField` Twig macro. The PHP method
     * (FieldLayoutDesigner::fieldLayoutDesignerFieldHtml) handles config defaulting
     * and passes everything here. The designer / generated fields table / card view
     * designer defer to their render methods so the markup stays exactly what the
     * legacy Craft.FieldLayoutDesigner JS expects.
     *
     * Declared in a PHP docblock (rather than a Blade comment) so PhpStorm types
     * these variables.
     *
     * @var \CraftCms\Cms\Cp\FieldLayoutDesigner\FieldLayoutDesigner $designer
     * @var \CraftCms\Cms\Cp\FieldLayoutDesigner\CardDesigner $cardDesigner
     * @var \CraftCms\Cms\FieldLayout\FieldLayout $fieldLayout
     * @var array $config
     * @var bool $withGeneratedFields
     * @var bool $withCardViewDesigner
     * @var bool $disabled
     */
@endphp
<div class="fld-cvd">
    <h2 class="sr-only">{{ \CraftCms\Cms\t('Field Layout') }}</h2>

    <craft-field-group style="--gap: var(--c-spacing-xl)">
        {!! \CraftCms\Cms\Cp\FormFields::fieldHtml(
            fn (array $c) => $designer->html($fieldLayout, $c),
            [
                'label' => \CraftCms\Cms\t('Field Layout'),
                'fieldset' => true,
                'errors' => $fieldLayout->errors->get('customFields'),
                'data' => ['error-key' => 'fieldLayout.customFields'],
                ...$config,
            ],
        ) !!}

        @if ($withGeneratedFields)
            {!! \CraftCms\Cms\Cp\FormFields::fieldHtml(
                fn (array $c) => $designer->generatedFieldsTableHtml($fieldLayout, $c),
                [
                    'label' => \CraftCms\Cms\t('Generated Fields'),
                    'instructions' => \CraftCms\Cms\t('Define any additional values that should be saved on your elements.'),
                    'errors' => $fieldLayout->errors->get('generatedFields'),
                    'data' => ['error-key' => 'fieldLayout.generatedFields'],
                    'fieldLayout' => $fieldLayout,
                    'disabled' => $disabled,
                ],
            ) !!}
        @endif

        @if ($withCardViewDesigner)
            {!! $cardDesigner->html($fieldLayout, ['disabled' => $disabled]) !!}
        @endif
    </craft-field-group>
</div>
