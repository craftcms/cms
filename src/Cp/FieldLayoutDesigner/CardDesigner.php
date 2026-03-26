<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\FieldLayoutDesigner;

use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Cp\Html\StatusHtml;
use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Field\ContentBlock;
use CraftCms\Cms\Field\Exceptions\FieldNotFoundException;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Html;
use Illuminate\Container\Attributes\Singleton;
use Throwable;

use function CraftCms\Cms\t;

#[Singleton]
class CardDesigner
{
    public function html(FieldLayout $fieldLayout, array $config = []): string
    {
        $config += [
            'id' => 'cvd'.mt_rand(),
            'disabled' => false,
        ];

        $allOptions = $this->previewOptions($fieldLayout);
        $selectedOptions = [];
        $remainingOptions = [...$allOptions];

        foreach ($fieldLayout->getCardView() as $key) {
            if (isset($allOptions[$key])) {
                $selectedOptions[$key] = $allOptions[$key];
                unset($remainingOptions[$key]);
            }
        }

        // sort the remaining attributes alphabetically, by label
        usort($remainingOptions, fn (array $a, array $b) => $a['label'] <=> $b['label']);

        $checkboxSelect = FormFields::checkboxSelectFieldHtml([
            'label' => t('Card Attributes'),
            'id' => $config['id'],
            'options' => [...$selectedOptions, ...$remainingOptions],
            'values' => array_keys($selectedOptions),
            'sortable' => true,
            'disabled' => $config['disabled'],
        ]);

        // js is initiated via Craft.FieldLayoutDesigner
        $showThumb = $fieldLayout->type::hasThumbs() || $fieldLayout->hasThumbField();
        $previewHtml = $this->previewHtml($fieldLayout, showThumb: $showThumb);

        return
            Html::beginTag('div', [
                'id' => $config['id'].'-container',
                'class' => 'card-view-designer',
            ]).
            Html::tag('h2', t('Card Layout Editor'), ['class' => 'visually-hidden']).
            Html::beginTag('div', ['class' => 'cvd-container']).
            Html::beginTag('div', ['class' => 'cvd-library']).
            $checkboxSelect.
            Html::endTag('div'). // .cvd-library
            Html::beginTag('div', ['class' => 'cvd-preview-container']).
            Html::beginTag('div', ['class' => 'cvd-preview']).
            Html::tag('h3', t('Card Layout Preview'), [
                'class' => 'visually-hidden',
            ]).
            Html::tag('p', t('The following content is for preview only.'), [
                'class' => 'visually-hidden',
            ]).
            $previewHtml.
            Html::endTag('div'). // .cvd-preview-container
            Html::endTag('div'). // .cvd-preview
            Html::endTag('div'). // .cvd-container
            $this->thumbManagementHtml($fieldLayout, $config).
            Html::endTag('div'); // .card-view-designer
    }

    /**
     * @return array{label:string,value:string}[]
     */
    public function previewOptions(FieldLayout $fieldLayout, bool $withAttributes = true): array
    {
        return $this->cardPreviewOptionsInternal($fieldLayout, '', '', $withAttributes);
    }

    /**
     * @return array{label:string,value:string}[]
     */
    public function thumbOptions(FieldLayout $fieldLayout): array
    {
        return $this->cardThumbOptionsInternal($fieldLayout, '', '');
    }

    /**
     * @throws Throwable
     */
    public function previewHtml(FieldLayout $fieldLayout, array $cardElements = [], ?bool $showThumb = null): string
    {
        $showThumb ??= $fieldLayout->type::hasThumbs() || $fieldLayout->hasThumbField();
        $thumbAlignment = $fieldLayout->getCardThumbAlignment();

        // get heading
        $heading = Html::tag('craft-element-label',
            Html::tag('a', Html::tag('span', t('Title')), [
                'class' => ['label-link'],
                'href' => '#',
                'aria-disabled' => 'true',
            ]),
            [
                'class' => 'label',
            ]
        );

        // get status label placeholder
        $labels = [$fieldLayout->type::hasStatuses() ? app(StatusHtml::class)->componentStatusLabelHtml(new ($fieldLayout->type)()) : null];

        $previewHtml =
            Html::beginTag('div', [
                'class' => array_filter([
                    'element',
                    'card',
                    $showThumb ? "thumb-$thumbAlignment" : null,
                ]),
            ]);

        $previewHtml .=
            Html::tag('div', attributes: ['class' => 'card-titlebar']).
            Html::beginTag('div', ['class' => 'card-main']).
            Html::beginTag('div', ['class' => 'card-content']).
            Html::tag('div', $heading, ['class' => 'card-heading']).
            Html::beginTag('div', ['class' => 'card-body']);

        // get body elements (fields and attributes)
        $cardElements = $fieldLayout->getCardBodyElements();

        foreach ($cardElements as $cardElement) {
            $previewHtml .= Html::tag('div', $cardElement, [
                'class' => 'card-attribute-preview',
            ]);
        }

        if (! empty(array_filter($labels))) {
            $previewHtml .= Html::ul()
                ->items(...array_map(fn ($label) => Html::li($label)->encode(false), $labels))
                ->class('flex', 'gap-xs')
                ->render();
        }

        $previewHtml .=
            Html::endTag('div'). // .card-body
            Html::endTag('div'); // .card-content

        // get thumb placeholder
        if ($showThumb) {
            $previewThumb = Html::tag('div',
                Html::tag('div', Icons::svg('image'), ['class' => 'cp-icon']),
                ['class' => 'cvd-thumbnail']
            );

            $previewHtml .= Html::tag('div', $previewThumb, ['class' => ['thumb']]);
        } // .element.card

        return $previewHtml.(Html::endTag('div').Html::tag('div', '', ['class' => 'spinner spinner-absolute']).Html::endTag('div'));
    }

    private function cardPreviewOptionsInternal(
        FieldLayout $fieldLayout,
        string $keyPrefix,
        string $labelPrefix,
        bool $withAttributes,
    ): array {
        $allOptions = [];

        if ($withAttributes) {
            foreach ($fieldLayout->type::cardAttributes($fieldLayout) as $key => $attribute) {
                $allOptions[$keyPrefix.$key] = [
                    'label' => $labelPrefix.$attribute['label'],
                    'placeholder' => $attribute['placeholder'] ?? null,
                ];
            }
        }

        foreach ($fieldLayout->getAllElements() as $layoutElement) {
            if ($layoutElement instanceof CustomField) {
                try {
                    $field = $layoutElement->getField();
                } catch (FieldNotFoundException) {
                    continue;
                }
                if ($field instanceof ContentBlock) {
                    $allOptions += $this->cardPreviewOptionsInternal(
                        $field->getFieldLayout(),
                        "{$keyPrefix}contentBlock:$layoutElement->uid.",
                        sprintf('%s%s → ', $labelPrefix, $layoutElement->label()),
                        false,
                    );

                    continue;
                }
            }

            if ($layoutElement instanceof BaseField && $layoutElement->previewable()) {
                $allOptions[$keyPrefix.$layoutElement->key()] = [
                    'label' => sprintf('%s%s', $labelPrefix, $layoutElement->label()),
                ];
            }
        }

        foreach ($fieldLayout->getGeneratedFields() as $field) {
            if (($field['name'] ?? '') !== '') {
                $allOptions["generatedField:{$field['uid']}"] = [
                    'label' => $field['name'],
                ];
            }
        }

        foreach ($allOptions as $key => &$option) {
            if (! isset($option['value'])) {
                $option['value'] = $key;
            }
        }

        return $allOptions;
    }

    private function cardThumbOptionsInternal(
        FieldLayout $fieldLayout,
        string $keyPrefix,
        string $labelPrefix,
    ): array {
        $allOptions = [];

        foreach ($fieldLayout->getAllElements() as $layoutElement) {
            if ($layoutElement instanceof CustomField) {
                try {
                    $field = $layoutElement->getField();
                } catch (FieldNotFoundException) {
                    continue;
                }
                if ($field instanceof ContentBlock) {
                    $allOptions += $this->cardThumbOptionsInternal(
                        $field->getFieldLayout(),
                        "{$keyPrefix}contentBlock:$layoutElement->uid.",
                        sprintf('%s%s → ', $labelPrefix, $layoutElement->label()),
                    );

                    continue;
                }
            }

            if ($layoutElement instanceof BaseField && $layoutElement->thumbable()) {
                $allOptions[$keyPrefix.$layoutElement->key()] = [
                    'label' => sprintf('%s%s', $labelPrefix, $layoutElement->label()),
                ];
            }
        }

        foreach ($allOptions as $key => &$option) {
            if (! isset($option['value'])) {
                $option['value'] = $key;
            }
        }

        return $allOptions;
    }

    private function thumbManagementHtml(FieldLayout $fieldLayout, array $config): string
    {
        $config += [
            'disabled' => false,
        ];

        if ($fieldLayout->type::hasThumbs()) {
            $options = [
                ['label' => t('Default'), 'value' => '__default__'],
            ];
        } else {
            $options = [
                ['label' => t('None'), 'value' => '__none__'],
            ];
        }

        $thumbOptions = array_values($this->thumbOptions($fieldLayout));
        usort($thumbOptions, fn (array $a, array $b) => $a['label'] <=> $b['label']);
        array_push($options, ...$thumbOptions);

        $thumbnailAlignment = $fieldLayout->getCardThumbAlignment();

        $thumbHtml = Html::beginTag('div', ['class' => 'thumb-management']).
            Html::tag('h2', t('Manage element thumbnails'), ['class' => 'visually-hidden']).
            Html::beginTag('div', ['class' => ['flex', 'flex-nowrap', 'items-start']]);

        // dropdown field that contains all thumbable fields + 'None' option
        $thumbHtml .= FormFields::selectFieldHtml([
            'label' => t('Thumbnail Source'),
            'id' => 'thumb-source',
            'options' => $options,
            'value' => $fieldLayout->thumbFieldKey,
            'disabled' => $config['disabled'],
        ]);

        // radio button switch that lets you choose whether the thumb alignment should be start or end
        $orientation = I18N::getLocale()->getOrientation();
        $showThumb = $fieldLayout->type::hasThumbs() || $fieldLayout->hasThumbField();
        $thumbHtml .= FormFields::buttonGroupFieldHtml([
            'label' => t('Thumbnail Alignment'),
            'id' => 'thumb-alignment',
            'fieldClass' => $showThumb ? false : 'hidden',
            'options' => [
                [
                    'icon' => $orientation == 'ltr' ? 'slideout-left' : 'slideout-right',
                    'value' => 'start',
                    'attributes' => [
                        'title' => $orientation == 'ltr' ? t('Left') : t('Right'),
                        'aria' => [
                            'label' => $orientation == 'ltr' ? t('Left') : t('Right'),
                        ],
                    ],
                ],
                [
                    'icon' => $orientation == 'ltr' ? 'slideout-right' : 'slideout-left',
                    'value' => 'end',
                    'attributes' => [
                        'title' => $orientation == 'ltr' ? t('Right') : t('Left'),
                        'aria' => [
                            'label' => $orientation == 'ltr' ? t('Right') : t('Left'),
                        ],
                    ],
                ],
            ],
            'value' => $thumbnailAlignment,
            'disabled' => $config['disabled'],
        ]); // .thumb-management

        return $thumbHtml.(Html::endTag('div').Html::endTag('div'));
    }
}
