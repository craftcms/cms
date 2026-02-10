<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements;

use craft\helpers\Cp;
use CraftCms\Cms\FieldLayout\FieldLayoutElement;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use Override;

use function CraftCms\Cms\t;

/**
 * BaseUiElement is the base class for UI elements that can be included in field layouts.
 */
abstract class BaseUiElement extends FieldLayoutElement
{
    /**
     * {@inheritdoc}
     */
    #[Override]
    public function isMultiInstance(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function selectorHtml(): string
    {
        $icon = $this->selectorIcon();
        $label = $this->selectorLabel();

        $indicatorHtml = $this->hasConditions() ? Html::tag('div', Cp::iconSvg('diamond'), [
            'class' => ['cp-icon', 'puny', 'orange'],
            'title' => t('This element is conditional'),
            'aria' => ['label' => t('This element is conditional')],
        ]) : '';

        return
            Html::beginTag('div', [
                'class' => 'fld-ui-element',
                'data' => [
                    'type' => str_replace('\\', '-', static::class),
                ],
            ]).
            Html::beginTag('div', ['class' => 'fld-element-icon']).
            ($icon ? Cp::iconSvg($icon, $label) : Cp::fallbackIconSvg($label)).
            Html::endTag('div'). // .fld-element-icon
            Html::beginTag('div', ['class' => 'field-name']).
            Html::beginTag('div', Arr::merge(
                ['class' => ['fld-element-label']],
                $this->selectorLabelAttributes(),
            )).
            Html::tag('h4', Html::encode($label)).
            $indicatorHtml.
            Html::endTag('div'). // .fld-element-label
            Html::endTag('div'). // .field-name
            Html::endTag('div'); // .fld-ui-element
    }

    /**
     * Returns the selector label.
     */
    abstract protected function selectorLabel(): string;

    /**
     * Returns the selector label HTML attributes.
     */
    protected function selectorLabelAttributes(): array
    {
        return [];
    }

    /**
     * Returns the UI element’s SVG icon, if it has one.
     *
     * The returned icon can be a system icon’s name (e.g. `'whiskey-glass-ice'`),
     * the path to an SVG file, or raw SVG markup.
     *
     * System icons can be found in `src/icons/solid/`.
     */
    protected function selectorIcon(): ?string
    {
        return null;
    }
}
