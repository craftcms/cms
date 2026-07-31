@php
    /**
     * Field layout designer markup.
     *
     * Split out of FieldLayoutDesigner::html()'s Html::* helper chain. The PHP method
     * still does all the setup (tabs, settings, available fields, config) and passes
     * it here. Dynamic sections defer to the designer's render methods so the markup
     * stays exactly what the legacy Craft.FieldLayoutDesigner JS expects.
     *
     * Declared in a PHP docblock (rather than a Blade comment) so PhpStorm types
     * these variables and counts the $designer->… render calls as real usages.
     *
     * @var \CraftCms\Cms\Cp\FieldLayoutDesigner\FieldLayoutDesigner $designer
     * @var string $id
     * @var array $settings
     * @var array $fieldLayoutConfig
     * @var \CraftCms\Cms\FieldLayout\FieldLayout $fieldLayout
     * @var \CraftCms\Cms\FieldLayout\FieldLayoutTab[] $tabs
     * @var bool $customizableTabs
     * @var bool $customizableUi
     * @var bool $disabled
     * @var array $availableNativeFields
     * @var array $availableCustomFields
     * @var \CraftCms\Cms\FieldLayout\FieldLayoutElement[] $availableUiElements
     */
@endphp
<craft-field-layout-designer settings="{{ \CraftCms\Cms\Support\Json::encode($settings) }}">
<div
    id="{{ $id }}"
    class="layoutdesigner"
>
    <input
        type="hidden"
        name="fieldLayout"
        value="{{ \CraftCms\Cms\Support\Json::encode($fieldLayoutConfig) }}"
        data-config-input
    >

    <div class="fld-container">
        <div class="fld-workspace cp-workspace">
            <div class="fld-tabs">
                @foreach ($tabs as $tab)
                    {!! $designer->fldTabHtml($tab, $customizableTabs, $disabled) !!}
                @endforeach
            </div>

            @if ($customizableTabs)
                <craft-button
                    type="button"
                    command="--add-tab"
                    @disabled($disabled)
                >
                    <craft-icon name="plus" slot="prefix"></craft-icon>
                    {{ \CraftCms\Cms\t('New Tab') }}</craft-button>
            @endif
        </div>

        <div class="fld-library">
            @if ($customizableUi)
                <craft-button-group
                    class="btngroup btngroup--exclusive"
                    aria-label="{{ \CraftCms\Cms\t('Layout element types') }}"
                >
                    <craft-button
                        type="button"
                        aria-pressed="true"
                        variant="outline"
                        size="small"
                        data-library="field"
                        class="w-full"
                        active
                        @disabled($disabled)
                    >{{ \CraftCms\Cms\t('Fields') }}</craft-button>
                    <craft-button
                        type="button"
                        aria-pressed="false"
                        data-library="ui"
                        variant="outline"
                        size="small"
                        class="w-full"
                        @disabled($disabled)
                    >{{ \CraftCms\Cms\t('UI Elements') }}</craft-button>
                </craft-button-group>
            @endif

            <div class="fld-field-library">
                <div class="search">
                    <craft-input
                        label="{{ \CraftCms\Cms\t('Search') }}"
                        placeholder="{{ \CraftCms\Cms\t('Search') }}"
                        label-sr-only
                        type="search"
                        inputmode="search"
                        @disabled($disabled)
                    >
                    </craft-input>
                </div>

                {!! $designer->fldFieldSelectorsHtml(\CraftCms\Cms\t('Native Fields'), $availableNativeFields, $fieldLayout) !!}

                @foreach ($availableCustomFields as $groupName => $groupFields)
                    {!! $designer->fldFieldSelectorsHtml($groupName, $groupFields, $fieldLayout) !!}
                @endforeach
            </div>

            @if ($customizableUi)
                <div class="fld-ui-library hidden">
                    @foreach ($availableUiElements as $element)
                        {!! $designer->layoutElementSelectorHtml($element, true, [
                            'class' => array_filter([
                                ! $designer->showFldUiElementSelector($fieldLayout, $element) ? 'hidden' : null,
                            ]),
                        ]) !!}
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
</craft-field-layout-designer>
