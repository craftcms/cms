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

/**
 * Heading represents an `<h2>` UI element that can be included in field layouts.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class Heading extends FieldLayoutElement
{
    /**
     * @var string The heading text
     */
    public $heading;

    /**
     * @inheritdoc
     */
    public function selectorHtml(): string
    {
        $text = Html::tag('div', Html::encode($this->heading ?: Craft::t('app', 'Heading')), [
            'class' => 'fld-element-label',
        ]);

        return <<<HTML
<div class="fld-heading">
  <div class="fld-element-icon"></div>
  $text
</div>
HTML;
    }

    /**
     * @inheritdoc
     */
    public function settingsHtml()
    {
        return Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'textField', [
            [
                'label' => Craft::t('app', 'Heading'),
                'id' => 'heading',
                'name' => 'heading',
                'value' => $this->heading,
            ]
        ]);
    }

    /**
     * @inheritdoc
     */
    public function formHtml(ElementInterface $element = null, bool $static = false)
    {
        return Html::tag('h2', Html::encode(Craft::t('site', $this->heading)));
    }
}
