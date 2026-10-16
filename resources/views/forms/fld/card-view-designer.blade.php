@php
    /**
     * Card view designer markup.
     *
     * Split out of CardDesigner::html()'s Html::* helper chain. The PHP method
     * still does all the setup (selected/remaining options, preview, thumb
     * management) and passes the rendered partials here so the markup stays
     * exactly what the legacy Craft.FieldLayoutDesigner JS expects.
     *
     * Declared in a PHP docblock (rather than a Blade comment) so PhpStorm types
     * these variables.
     *
     * @var string $id
     * @var bool $disabled
     * @var array $attributeOptions
     * @var string[] $attributeValues
     * @var string $previewHtml
     * @var string $thumbManagement
     */
@endphp
<div id="{{ $id }}-container" class="card-view-designer">
    <h2 class="sr-only">{{ \CraftCms\Cms\t('Card Layout Editor') }}</h2>

    <div class="cvd-container">
        <craft-field-group class="cvd-library">
            {{-- Where the owner's own display settings (an entry type's icon and
                 color, say) render, via `Ui\Nodes\Group::outlet()`. --}}
            <div data-ui-outlet="field-layout-designer.display"></div>

            {!! \CraftCms\Cms\Cp\FormFields::checkboxSelectFieldHtml([
                'label' => \CraftCms\Cms\t('Card Attributes'),
                'id' => $id,
                'options' => $attributeOptions,
                'values' => $attributeValues,
                'sortable' => true,
                'disabled' => $disabled,
            ]) !!}

            {!! $thumbManagement !!}
        </craft-field-group>

        <div>

            <h3 class="sr-only">{{ \CraftCms\Cms\t('Card Layout Preview') }}</h3>
            <p class="sr-only">{{ \CraftCms\Cms\t('The following content is for preview only.') }}</p>
            <div class="cvd-preview-container cp-workspace">
                <div class="cvd-preview">
                    {!! $previewHtml !!}
                </div>
            </div>
        </div>
    </div>
</div>
