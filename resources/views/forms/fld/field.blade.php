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
        @php
            $designerHtml = \CraftCms\Cms\Cp\FormFields::fieldHtml(
                fn (array $c) => $designer->html($fieldLayout, $c),
                [
                    'label' => \CraftCms\Cms\t('Field Layout'),
                    'labelSrOnly' => $withCardViewDesigner,
                    'fieldset' => true,
                    'errors' => $fieldLayout->errors->get('customFields'),
                    'data' => ['error-key' => 'fieldLayout.customFields'],
                    ...$config,
                ],
            );

            $generatedFieldsHtml = $withGeneratedFields
                ? \CraftCms\Cms\Cp\FormFields::fieldHtml(
                    fn (array $c) => $designer->generatedFieldsTableHtml($fieldLayout, $c),
                    [
                        'label' => \CraftCms\Cms\t('Generated Fields'),
                        'instructions' => \CraftCms\Cms\t('Define any additional values that should be saved on your elements.'),
                        'errors' => $fieldLayout->errors->get('generatedFields'),
                        'data' => ['error-key' => 'fieldLayout.generatedFields'],
                        'fieldLayout' => $fieldLayout,
                        'disabled' => $disabled,
                    ],
                )
                : null;
        @endphp

        @if ($withCardViewDesigner)
            @php
                $errorKeys = $fieldLayout->errors->keys();
                $hasErrors = fn (array $prefixes) => collect($errorKeys)->contains(
                    fn (string $key) => \Illuminate\Support\Str::startsWith($key, $prefixes),
                );
                $errorIcon = '<craft-icon name="circle-exclamation" slot="suffix" data-fld-tab-error label="'.e(\CraftCms\Cms\t('Errors')).'"></craft-icon>';
            @endphp
            <craft-tabs class="fld-view-tabs" label="{{ \CraftCms\Cms\t('Field Layout') }}">
                <craft-tab slot="tab" data-fld-tab="content">
                    {{ \CraftCms\Cms\t('Content') }}
                    @if ($hasErrors(['customFields', 'tabs.', 'generatedFields']))
                        {!! $errorIcon !!}
                    @endif
                </craft-tab>
                {{-- Generated fields are part of the content, so they're in its tab.
                     (A plain panel: the tabs hide inactive panels, and a field
                     group's own display would win over that.) --}}
                <div slot="panel">
                    <craft-field-group class="fld-content-panel">
                        {!! $designerHtml !!}
                        {!! $generatedFieldsHtml !!}
                    </craft-field-group>
                </div>
                <craft-tab slot="tab" data-fld-tab="appearance">
                    {{ \CraftCms\Cms\t('Appearance') }}
                    @if ($hasErrors(['cardView', 'thumbFieldKey']))
                        {!! $errorIcon !!}
                    @endif
                </craft-tab>
                <div slot="panel">{!! $cardDesigner->html($fieldLayout, ['disabled' => $disabled]) !!}</div>
            </craft-tabs>
        @else
            {!! $designerHtml !!}

            @if ($generatedFieldsHtml)
                <hr>
                {!! $generatedFieldsHtml !!}
            @endif
        @endif
    </craft-field-group>
</div>
