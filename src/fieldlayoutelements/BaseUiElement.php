<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements;

use Craft;
use craft\base\FieldLayoutElement;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\Html;

/**
 * BaseUiElement is the base class for UI elements that can be included in field layouts.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
abstract class BaseUiElement extends FieldLayoutElement
{
    /**
     * @inheritdoc
     */
    public function isMultiInstance(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function selectorHtml(): string
    {
        $icon = $this->selectorIcon();
        $label = $this->selectorLabel();

        $indicatorHtml = $this->hasConditions() ? Html::tag('div', Cp::iconSvg('diamond'), [
            'class' => ['cp-icon', 'puny', 'orange'],
            'title' => Craft::t('app', 'This element is conditional'),
            'aria' => ['label' => Craft::t('app', 'This element is conditional')],
        ]) : '';

        return
            Html::beginTag('div', [
                'class' => 'fld-ui-element',
                'data' => [
                    'type' => str_replace('\\', '-', static::class),
                ],
            ]) .
            Html::beginTag('div', ['class' => 'fld-element-icon']) .
            ($icon ? Cp::iconSvg($icon, $label) : Cp::fallbackIconSvg($label)) .
            Html::endTag('div') . // .fld-element-icon
            Html::beginTag('div', ['class' => 'field-name']) .
            Html::beginTag('div', ArrayHelper::merge(
                ['class' => ['fld-element-label']],
                $this->selectorLabelAttributes(),
            )) .
            Html::tag('h4', Html::encode($label)) .
            $indicatorHtml .
            Html::endTag('div') . // .fld-element-label
            Html::endTag('div') . // .field-name
            Html::endTag('div'); // .fld-ui-element
    }

    /**
     * Returns the selector label.
     *
     * @return string
     */
    abstract protected function selectorLabel(): string;

    /**
     * Returns the selector label HTML attributes.
     *
     * @return array
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
     *
     * @return string|null
     */
    protected function selectorIcon(): ?string
    {
        return null;
    }
}
