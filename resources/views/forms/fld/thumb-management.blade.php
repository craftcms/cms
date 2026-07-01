@php
    /**
     * Thumbnail management markup for the card view designer.
     *
     * Split out of CardDesigner::thumbManagementHtml()'s Html::* chain. The PHP
     * method still computes the data (thumb source options, alignment options,
     * current alignment / field key); the select + button-group fields are still
     * rendered here by the same FormFields helpers, so the markup stays exactly
     * what the Craft.FieldLayoutDesigner / CardViewDesigner JS expects:
     * `.thumb-management`, `select[id$="thumb-source"]` (with the `__none__` /
     * `__default__` option values), `div.btngroup[id$="thumb-alignment"] .btn`
     * (with `data-value`), and the `[data-attribute="thumb-alignment"]` field
     * wrapper it shows/hides.
     *
     * Declared in a PHP docblock (rather than a Blade comment) so PhpStorm types
     * these variables.
     *
     * @var array $options
     * @var ?string $thumbFieldKey
     * @var string $thumbAlignment
     * @var bool $showThumb
     * @var bool $disabled
     * @var array $alignmentOptions
     */
@endphp
<div class="thumb-management">
    <h2 class="sr-only">{{ \CraftCms\Cms\t('Manage element thumbnails') }}</h2>

    <div class="flex flex-nowrap items-start">
        {{-- dropdown field that contains all thumbable fields + the None/Default option --}}
        {!! \CraftCms\Cms\Cp\FormFields::selectFieldHtml([
            'label' => \CraftCms\Cms\t('Thumbnail Source'),
            'id' => 'thumb-source',
            'options' => $options,
            'value' => $thumbFieldKey,
            'disabled' => $disabled,
        ]) !!}

        {{-- button group that chooses whether the thumb alignment is start or end --}}
        {!! \CraftCms\Cms\Cp\FormFields::buttonGroupFieldHtml([
            'label' => \CraftCms\Cms\t('Thumbnail Alignment'),
            'id' => 'thumb-alignment',
            'fieldClass' => $showThumb ? false : 'hidden',
            'options' => $alignmentOptions,
            'value' => $thumbAlignment,
            'disabled' => $disabled,
        ]) !!}
    </div>
</div>
