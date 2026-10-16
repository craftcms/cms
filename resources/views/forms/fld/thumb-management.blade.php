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

    @php
        // dropdown field that contains all thumbable fields + the None/Default option
        $sourceHtml = \CraftCms\Cms\Cp\FormFields::selectFieldHtml([
            'label' => \CraftCms\Cms\t('Source'),
            'id' => 'thumb-source',
            'options' => $options,
            'value' => $thumbFieldKey,
            'disabled' => $disabled,
        ]);

        // button group that chooses whether the thumb alignment is start or end
        $alignmentHtml = \CraftCms\Cms\Cp\FormFields::buttonGroupFieldHtml([
            'label' => \CraftCms\Cms\t('Alignment'),
            'id' => 'thumb-alignment',
            'fieldClass' => $showThumb ? false : 'hidden',
            'options' => $alignmentOptions,
            'value' => $thumbAlignment,
            'disabled' => $disabled,
        ]);
    @endphp

    {{-- A labelled group, so the two fields get grouped-input sub-labels --}}
    {!! \CraftCms\Cms\Cp\Components\Field::make()
        ->label(\CraftCms\Cms\t('Thumbnail'))
        ->instructions(\CraftCms\Cms\t('Choose the field that chips and cards should source their thumbnail from.'))
        ->fieldset()
        ->input(
            \CraftCms\Cms\Cp\Components\FieldGroup::make()
                ->children([new \Illuminate\Support\HtmlString($sourceHtml.$alignmentHtml)])
                ->attributes(['class' => 'auto-widths']),
        )
        ->toHtml() !!}
</div>
