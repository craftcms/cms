<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Html;

use CraftCms\Cms\Component\Contracts\Chippable;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Translation\I18N;
use Illuminate\Container\Attributes\Singleton;

#[Singleton]
readonly class PreviewHtml
{
    public function __construct(
        private ElementHtml $elementHtml,
        private I18N $i18N,
    ) {}

    /**
     * @param  ElementInterface[]  $elements
     */
    public function elementPreviewHtml(
        array $elements,
        string $size = ElementHtml::CHIP_SIZE_SMALL,
        bool $showStatus = true,
        bool $showThumb = true,
        bool $showLabel = true,
        bool $showDraftName = true,
    ): string {
        if (empty($elements)) {
            return '';
        }

        $first = array_shift($elements);
        $html = Html::beginTag('div', ['class' => ['inline-chips', 'no-truncate']]).
            $this->elementHtml->elementChipHtml($first, [
                'showDraftName' => $showDraftName,
                'showLabel' => $showLabel,
                'showStatus' => $showStatus,
                'showThumb' => $showThumb,
                'size' => $size,
            ]);

        if (! empty($elements)) {
            $otherHtml = '';
            foreach ($elements as $other) {
                $otherHtml .= $this->elementHtml->elementChipHtml($other, [
                    'showDraftName' => $showDraftName,
                    'showLabel' => $showLabel,
                    'showStatus' => $showStatus,
                    'showThumb' => $showThumb,
                    'size' => $size,
                ]);
            }
            $html .= Html::tag('span', '+'.$this->i18N->getFormatter()->asInteger(count($elements)), [
                'title' => implode(', ', array_map(fn (ElementInterface $element) => $element->id, $elements)),
                'class' => 'btn small',
                'role' => 'button',
                'tabindex' => 0,
                'data' => [
                    'other' => Json::encode($otherHtml),
                ],
                'aria-expanded' => 'false',
                'onkeydown' => 'Craft.cp.previewCountBadge(event, this, true)', // have to use keydown or the page will scroll
                'onclick' => 'Craft.cp.previewCountBadge(event, this, true)',
            ]);
        } // .inline-chips

        return $html.Html::endTag('div');
    }

    /**
     * @param  Chippable[]  $components
     */
    public function componentPreviewHtml(array $components, array $chipConfig = []): string
    {
        if (empty($components)) {
            return '';
        }

        $first = array_shift($components);
        $html = Html::beginTag('div', ['class' => 'inline-chips']).
            $this->elementHtml->chipHtml($first, $chipConfig);

        if (! empty($components)) {
            $otherHtml = '';
            foreach ($components as $other) {
                $otherHtml .= $this->elementHtml->chipHtml($other, $chipConfig);
            }
            $html .= Html::tag('span', '+'.$this->i18N->getFormatter()->asInteger(count($components)), [
                'title' => implode(', ', array_map(fn (Chippable $component) => $component->getId(), $components)),
                'class' => 'btn small',
                'role' => 'button',
                'tabindex' => '0',
                'data' => [
                    'other' => Json::encode($otherHtml),
                ],
                'onkeydown' => 'Craft.cp.previewCountBadge(event, this, false)', // have to use keydown or the page will scroll
                'onclick' => 'Craft.cp.previewCountBadge(event, this, false)',
            ]);
        } // .inline-chips

        return $html.Html::endTag('div');
    }
}
