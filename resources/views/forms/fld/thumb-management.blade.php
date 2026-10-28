@php
    /**
     * Thumbnail management markup for the card view designer.
     *
     * Split out of CardDesigner::thumbManagementHtml()'s Html::* chain. The PHP
     * method still computes the data (thumb source, alignment, and style
     * options, and the current values); the fields are rendered here by the
     * same FormFields helpers, so the markup stays exactly what the
     * Craft.FieldLayoutDesigner / CardViewDesigner JS expects:
     * `.thumb-management`, `select[id$="thumb-source"]` (with the `__none__` /
     * `__default__` option values), the `[id$="thumb-alignment"]` and
     * `[id$="thumb-style"]` radio groups (whose radios' values are the
     * alignments and styles), and the `[data-attribute="thumb-alignment"]` and
     * `[data-attribute="thumb-style"]` field wrappers it shows/hides.
     *
     * Declared in a PHP docblock (rather than a Blade comment) so PhpStorm types
     * these variables.
     *
     * @var array $options
     * @var ?string $thumbFieldKey
     * @var string $thumbAlignment
     * @var string $thumbStyle
     * @var bool $showThumbAlignment
     * @var bool $showThumbStyle
     * @var bool $disabled
     * @var array $alignmentOptions
     * @var array $styleOptions
     */
@endphp
<div class="thumb-management">
    <h2 class="sr-only">{{ \CraftCms\Cms\t('Manage element thumbnails') }}</h2>

    {{-- dropdown field that contains all thumbable fields + the None/Default option --}}
    {!! \CraftCms\Cms\Cp\FormFields::selectFieldHtml([
        'label' => \CraftCms\Cms\t('Thumb Source'),
        'instructions' => \CraftCms\Cms\t('Choose the field that chips and cards should source their thumbnail from.'),
        'id' => 'thumb-source',
        'options' => $options,
        'value' => $thumbFieldKey,
        'disabled' => $disabled,
    ]) !!}

    {{-- Illustrated radios, like a Matrix field's View Mode setting --}}
    {!! \CraftCms\Cms\Cp\FormFields::fieldHtml(
        function () use ($styleOptions, $thumbStyle, $disabled): string {
            return \CraftCms\Cms\Cp\Components\RadioGroup::make()
                ->id('thumb-style')
                ->options(array_map(
                    fn (array $option) => \CraftCms\Cms\Cp\Components\Radio::make()
                        ->id("thumb-style-{$option['value']}")
                        ->name('thumb-style')
                        ->value($option['value'])
                        ->checked($option['value'] === $thumbStyle)
                        ->disabled($disabled)
                        ->label($option['label'])
                        ->thumbnail([
                            'src' => \CraftCms\Cms\Cp\Cp::publicAssetUrl("images/thumb-styles/{$option['value']}.svg"),
                            'width' => 80,
                            'height' => 60,
                            'aspectRatio' => '80 / 60',
                        ]),
                    $styleOptions,
                ))
                ->toHtml();
        },
        [
            'label' => \CraftCms\Cms\t('Thumb Style'),
            'instructions' => \CraftCms\Cms\t('Choose whether image thumbnails should sit beside the card’s content, or cover the whole card.'),
            'id' => 'thumb-style',
            'fieldset' => true,
            'fieldClass' => $showThumbStyle ? false : 'hidden',
        ],
    ) !!}
    {{-- Radios shown as buttons, choosing whether the thumbnail is aligned to
         the start or the end: one tab stop, with the arrow keys moving the
         selection. The options are icon-only, so their names also show in
         tooltips, which label the radios rather than describing them. --}}
    {!! \CraftCms\Cms\Cp\FormFields::fieldHtml(
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
            'label' => \CraftCms\Cms\t('Thumb Alignment'),
            'id' => 'thumb-alignment',
            'fieldset' => true,
            'fieldClass' => $showThumbAlignment ? false : 'hidden',
        ],
    ) !!}

</div>
