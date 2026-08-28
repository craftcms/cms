<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\FieldLayoutDesigner;

use CraftCms\Cms\Cp\Html\StatusHtml;
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
    /** @param array<string, mixed> $config */
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

        // js is initiated via Craft.FieldLayoutDesigner
        $showThumb = $fieldLayout->type::hasThumbs() || $fieldLayout->hasThumbField();

        return view('c::forms.fld.card-view-designer', [
            'id' => $config['id'],
            'disabled' => $config['disabled'],
            'attributeOptions' => [...$selectedOptions, ...$remainingOptions],
            'attributeValues' => array_keys($selectedOptions),
            'previewHtml' => $this->previewHtml($fieldLayout, showThumb: $showThumb),
            'thumbManagement' => $this->thumbManagementHtml($fieldLayout, $config),
        ])->render();
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
    /** @param list<string> $cardElements */
    public function previewHtml(FieldLayout $fieldLayout, array $cardElements = [], ?bool $showThumb = null): string
    {
        $showThumb ??= $fieldLayout->type::hasThumbs() || $fieldLayout->hasThumbField();
        $thumbAlignment = $fieldLayout->getCardThumbAlignment();

        // get heading
        $headingHtml = Html::tag('craft-truncate',
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
        $bodyHtml = Html::beginTag('div');

        // get body elements (fields and attributes)
        $cardElements = $fieldLayout->getCardBodyElements();

        foreach ($cardElements as $cardElement) {
            $bodyHtml .= Html::tag('div', $cardElement, [
                'class' => 'card-attribute-preview',
            ]);
        }

        if (! empty(array_filter($labels))) {
            $bodyHtml .= Html::ul()
                ->items(...array_map(fn ($label) => Html::li($label)->encode(false), $labels))
                ->class('cp:flex', 'cp:gap-sm')
                ->render();
        }

        $bodyHtml .= Html::endTag('div');

        // get thumb placeholder
        $thumbnailHtml = $showThumb ? Html::tag('div',
            Html::tag('craft-icon', '', ['name' => 'image']),
        ) : '';

        return view('c::forms.fld.card-preview', [
            'cvd' => $this,
            'headingHtml' => $headingHtml,
            'bodyHtml' => $bodyHtml,
            'showThumb' => $showThumb,
            'thumbAlignment' => $thumbAlignment,
            'thumbnailHtml' => $thumbnailHtml,
        ])->render();

    }

    /** @return array<string, array<string, string|null>> */
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

    /** @return array<string, array{label: string, value: string}> */
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

    /** @param array<string, mixed> $config */
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

        // radio button switch that lets you choose whether the thumb alignment should be start or end
        $orientation = I18N::getLocale()->getOrientation();
        $showThumb = $fieldLayout->type::hasThumbs() || $fieldLayout->hasThumbField();

        return view('c::forms.fld.thumb-management', [
            // dropdown field that contains all thumbable fields + the None/Default option
            'options' => $options,
            'thumbFieldKey' => $fieldLayout->thumbFieldKey,
            'thumbAlignment' => $fieldLayout->getCardThumbAlignment(),
            'showThumb' => $showThumb,
            'disabled' => $config['disabled'],
            'alignmentOptions' => [
                [
                    'value' => 'start',
                    // The family prefix is required for the client-side icon fetch;
                    // these live in custom-icons/, not the default solid/ folder.
                    'icon' => $orientation === 'ltr' ? 'custom-icons/slideout-left' : 'custom-icons/slideout-right',
                    'attributes' => [
                        'aria' => [
                            'label' => $orientation == 'ltr' ? t('Left') : t('Right'),
                        ],
                    ],
                ],
                [
                    'value' => 'end',
                    'icon' => $orientation === 'ltr' ? 'custom-icons/slideout-right' : 'custom-icons/slideout-left',
                    'attributes' => [
                        'aria' => [
                            'label' => $orientation == 'ltr' ? t('Right') : t('Left'),
                        ],
                    ],
                ],
            ],
        ])->render();
    }
}
