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
     * `__default__` option values), the `[id$="thumb-alignment"]` radio group
     * (whose radios' values are the alignments), and the
     * `[data-attribute="thumb-alignment"]` field wrapper it shows/hides.
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

        // Radios shown as buttons, choosing whether the thumbnail is aligned to
        // the start or the end: one tab stop, with the arrow keys moving the
        // selection. The options are icon-only, so their names also show in
        // tooltips, which label the radios rather than describing them.
        $alignmentHtml = \CraftCms\Cms\Cp\FormFields::fieldHtml(
            function () use ($alignmentOptions, $thumbAlignment, $disabled): string {
                $radios = [];
                $tooltips = '';

                foreach ($alignmentOptions as $option) {
                    $radioId = "thumb-alignment-{$option['value']}";
                    $radios[] = \CraftCms\Cms\Cp\Components\Radio::make()
                        ->id($radioId)
                        ->name('thumb-alignment')
                        ->value($option['value'])
                        ->checked($option['value'] === $thumbAlignment)
                        ->disabled($disabled)
                        ->label(\CraftCms\Cms\Cp\Components\Icon::make()->name($option['icon'])->label($option['label']));
                    $tooltips .= \CraftCms\Cms\Support\Html::tag('craft-tooltip', \CraftCms\Cms\Support\Html::encode($option['label']), [
                        'for' => $radioId,
                        'invoker-relation' => 'label',
                    ]);
                }

                return \CraftCms\Cms\Cp\Components\RadioGroup::make()
                    ->id('thumb-alignment')
                    ->buttons()
                    ->options($radios)
                    ->toHtml().$tooltips;
            },
            [
                'label' => \CraftCms\Cms\t('Alignment'),
                'id' => 'thumb-alignment',
                'fieldset' => true,
                'fieldClass' => $showThumb ? false : 'hidden',
            ],
        );
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
