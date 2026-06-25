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
        <div class="cvd-library">
            {!! \CraftCms\Cms\Cp\FormFields::checkboxSelectFieldHtml([
                'label' => \CraftCms\Cms\t('Card Attributes'),
                'id' => $id,
                'options' => $attributeOptions,
                'values' => $attributeValues,
                'sortable' => true,
                'disabled' => $disabled,
            ]) !!}
        </div>

        <div>

            <h3 class="sr-only">{{ \CraftCms\Cms\t('Card Layout Preview') }}</h3>
            <p class="sr-only">{{ \CraftCms\Cms\t('The following content is for preview only.') }}</p>
            <div class="cvd-preview-container cp-workspace">
                <div class="cvd-preview" data-color="white">
                    {!! $previewHtml !!}
                </div>
            </div>
        </div>
    </div>

    {!! $thumbManagement !!}
</div>
