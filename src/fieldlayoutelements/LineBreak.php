<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldLayoutElement;
use craft\helpers\Cp;
use craft\helpers\Html;

/**
 * LineBreak represents a line break UI element can be included in field layouts.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.1.0
 */
class LineBreak extends FieldLayoutElement
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
        $label = Craft::t('app', 'Line Break');
        $indicatorHtml = $this->hasConditions() ? Html::tag('div', Cp::iconSvg('diamond'), [
            'class' => ['cp-icon', 'puny', 'orange'],
            'title' => Craft::t('app', 'This element is conditional'),
            'aria' => ['label' => Craft::t('app', 'This element is conditional')],
        ]) : '';

        return <<<HTML
<div>
  <div class="fld-br">
    <div class="smalltext light flex flex-nowrap gap-xs">
      <span>$label</span>
      $indicatorHtml
    </div>
  </div>
</div>
HTML;
    }

    /**
     * @inheritdoc
     */
    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Html::tag('div', '', ['class' => 'line-break']);
    }
}
