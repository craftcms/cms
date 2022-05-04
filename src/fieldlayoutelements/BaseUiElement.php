<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements;

use craft\base\FieldLayoutElement;
use craft\helpers\ArrayHelper;
use craft\helpers\Component;
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
    public function selectorHtml(): string
    {
        $label = $this->selectorLabel();

        return
            Html::beginTag('div', [
                'class' => 'fld-ui-element',
                'data' => [
                    'type' => str_replace('\\', '-', static::class),
                    'foo' => 'bar',
                ],
            ]) .
            Html::beginTag('div', ['class' => 'fld-element-icon']) .
            Component::iconSvg($this->selectorIcon(), $label) .
            Html::endTag('div') . // .fld-element-icon
            Html::beginTag('div', ['class' => 'field-name']) .
            Html::beginTag('div', ArrayHelper::merge(
                ['class' => 'fld-element-label'],
                $this->selectorLabelAttributes(),
            )) .
            Html::tag('h4', Html::encode($label)) .
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
     * Returns the path to the widget’s SVG icon, or the actual SVG contents.
     *
     * @return string|null
     */
    protected function selectorIcon(): ?string
    {
        return null;
    }
}
