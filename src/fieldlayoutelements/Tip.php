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
use craft\helpers\Html;
use yii\helpers\Markdown;

/**
 * Tip represents an author tip UI element that can be included in field layouts.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class Tip extends FieldLayoutElement
{
    const STYLE_TIP = 'tip';
    const STYLE_WARNING = 'warning';

    /**
     * @var string The tip text
     */
    public $tip;

    /**
     * @var string The tip style (`tip` or `warning`)
     */
    public $style = self::STYLE_TIP;

    /**
     * @inheritdoc
     */
    public function selectorHtml(): string
    {
        $icon = Html::tag('div', '', [
            'class' => array_filter([
                'fld-element-icon',
                !$this->_isTip() ? 'fld-tip-warning' : null,
            ]),
        ]);

        if ($this->tip) {
            $label = Html::encode($this->tip);
        } else {
            $label = $this->_isTip() ? Craft::t('app', 'Tip') : Craft::t('app', 'Warning');
        }

        $text = Html::tag('div', $label, [
            'class' => 'fld-element-label',
        ]);

        return <<<HTML
<div class="fld-tip">
  $icon
  $text
</div>
HTML;
    }

    /**
     * @inheritdoc
     */
    public function settingsHtml()
    {
        return Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'textareaField', [
            [
                'label' => $this->_isTip() ? Craft::t('app', 'Tip') : Craft::t('app', 'Warning'),
                'instructions' => Craft::t('app', 'Can contain Markdown formatting.'),
                'class' => 'nicetext',
                'id' => 'tip',
                'name' => 'tip',
                'value' => $this->tip,
            ]
        ]);
    }

    /**
     * @inheritdoc
     */
    public function formHtml(ElementInterface $element = null, bool $static = false)
    {
        $noteClass = $this->_isTip() ? self::STYLE_TIP : self::STYLE_WARNING;
        $tip = Markdown::process(Html::encode(Craft::t('site', $this->tip)));

        return <<<HTML
<div class="readable">
  <blockquote class="note $noteClass">
    $tip
  </blockquote>
</div>
HTML;
    }

    /**
     * Returns whether this should have a tip style.
     *
     * @return bool
     */
    private function _isTip(): bool
    {
        return $this->style !== self::STYLE_WARNING;
    }
}
