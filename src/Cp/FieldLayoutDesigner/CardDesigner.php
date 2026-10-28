<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\FieldLayoutDesigner;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Cp\Cp;
use CraftCms\Cms\Cp\Html\StatusHtml;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\ContentBlock;
use CraftCms\Cms\Field\Exceptions\FieldNotFoundException;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Image\Images;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Container\Attributes\Singleton;
use Throwable;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
#[Singleton]
class CardDesigner
{
    /**
     * The luminance of `images/thumb-styles/preview.jpg`, the sample image cards are previewed with, as
     * {@see Images::sample()} measures it.
     *
     * The image is “Mt. Bachelor, Oregon, early morning” by Bonnie Moreland, in the public domain:
     * https://commons.wikimedia.org/wiki/File:Mt._Bachelor,_Oregon,_early_morning.jpg
     */
    private const array PREVIEW_COVER_LUMINANCE = [
        [[0.045, 0.121], [0.048, 0.132], [0.049, 0.138], [0.047, 0.133]],
        [[0.043, 0.12], [0.013, 0.135], [0.037, 0.138], [0.042, 0.128]],
        [[0.001, 0.2], [0.001, 0.263], [0.001, 0.299], [0.001, 0.268]],
    ];

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
        $cover = $showThumb && $this->showThumbStyle($fieldLayout) && $fieldLayout->getCardThumbStyle() === 'cover';
        $sampleElement = $this->sampleElement($fieldLayout);
        $sampleUser = $sampleElement instanceof User ? $sampleElement : null;
        $labelText = $sampleUser?->getName() ?? t('The Quick Brown Fox');

        // A sample user shows their default avatar, unless a field supplies the thumbnail.
        $avatarHtml = $showThumb && $sampleUser && ! $fieldLayout->hasThumbField()
            ? $sampleUser->getThumbHtml(120)
            : null;

        // get heading
        $headingHtml = Html::tag('craft-truncate',
            Html::tag('a', Html::tag('span', Html::encode($labelText)), [
                'class' => ['label-link'],
                'href' => '#',
                'aria-disabled' => 'true',
            ]),
            [
                'class' => 'label',
            ]
        );

        // User cards don't show a status badge elsewhere in the CP.
        $labels = [$sampleElement && ! $sampleUser ? app(StatusHtml::class)->componentStatusLabelHtml($sampleElement) : null];
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
                ->class('flex', 'gap-xs')
                ->render();
        }

        $bodyHtml .= Html::endTag('div');

        $previewSrc = Cp::publicAssetUrl('images/thumb-styles/preview.jpg');
        $thumbnailHtml = $showThumb ? Html::tag('img', '', ['src' => $previewSrc, 'alt' => '']) : '';

        return view('c::forms.fld.card-preview', [
            'cvd' => $this,
            'headingHtml' => $headingHtml,
            'bodyHtml' => $bodyHtml,
            'showThumb' => $showThumb,
            'thumbAlignment' => $thumbAlignment,
            'coverSrc' => $cover ? $previewSrc : null,
            'coverLuminance' => self::PREVIEW_COVER_LUMINANCE,
            'thumbnailHtml' => $thumbnailHtml,
            'avatarHtml' => $avatarHtml,
            'labelText' => $labelText,
            // The sample chip shows a status indicator, rather than a label.
            'statusIndicatorHtml' => $sampleElement ? app(StatusHtml::class)->componentStatusIndicatorHtml($sampleElement) : null,
        ])->render();

    }

    /**
     * Whether the Thumb Style setting applies: for asset volumes, whose own thumbnails are images, and for other
     * element types once a field is chosen as the thumb source, as that's the only way they get an asset's image.
     */
    private function showThumbStyle(FieldLayout $fieldLayout): bool
    {
        return $fieldLayout->type === Asset::class || $fieldLayout->hasThumbField();
    }

    /**
     * Returns a sample element to preview cards and chips with, if the element type has statuses: a live entry,
     * or an active user with a name (and so a default avatar).
     */
    private function sampleElement(FieldLayout $fieldLayout): ?ElementInterface
    {
        if (! $fieldLayout->type::hasStatuses()) {
            return null;
        }

        $element = new ($fieldLayout->type)();

        // Published, so it shows as live rather than pending.
        if (property_exists($element, 'postDate')) {
            $element->postDate = now();
        }

        if ($element instanceof User) {
            $element->firstName = 'Olive';
            $element->lastName = 'Craften';
            $element->fullName = 'Olive Craften';
            $element->active = true;
            // Fixed, so the avatar's colors (picked from its first two bytes) stay the same: teal to violet.
            $element->uid = '181d3c1a-0000-4000-8000-000000000000';
        }

        return $element;
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
            $option['value'] ??= $key;
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
            $option['value'] ??= $key;
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
        $showThumbStyle = $this->showThumbStyle($fieldLayout);
        $cover = $showThumbStyle && $fieldLayout->getCardThumbStyle() === 'cover';

        return view('c::forms.fld.thumb-management', [
            // dropdown field that contains all thumbable fields + the None/Default option
            'options' => $options,
            'thumbFieldKey' => $fieldLayout->thumbFieldKey,
            'thumbAlignment' => $fieldLayout->getCardThumbAlignment(),
            'thumbStyle' => $fieldLayout->getCardThumbStyle(),
            'showThumbAlignment' => $showThumb && ! $cover,
            'showThumbStyle' => $showThumbStyle,
            'styleOptions' => [
                ['value' => 'normal', 'label' => t('Normal')],
                ['value' => 'cover', 'label' => t('Cover')],
            ],
            'disabled' => $config['disabled'],
            'alignmentOptions' => [
                [
                    'value' => 'start',
                    // The family prefix is required for the client-side icon fetch;
                    // these live in custom-icons/, not the default solid/ folder.
                    'icon' => $orientation === 'ltr' ? 'custom-icons/slideout-left' : 'custom-icons/slideout-right',
                    'label' => $orientation === 'ltr' ? t('Left') : t('Right'),
                ],
                [
                    'value' => 'end',
                    'icon' => $orientation === 'ltr' ? 'custom-icons/slideout-right' : 'custom-icons/slideout-left',
                    'label' => $orientation === 'ltr' ? t('Right') : t('Left'),
                ],
            ],
        ])->render();
    }
}
