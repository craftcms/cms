<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Html;

/**
 * Heading represents an `<h2>` UI element that can be included in field layouts.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class Heading extends BaseUiElement
{
    /**
     * @var string The heading text
     */
    public $heading;

    /**
     * @inheritdoc
     */
    protected function selectorLabel(): string
    {
        return $this->heading ?: Craft::t('app', 'Heading');
    }

    /**
     * @inheritdoc
     */
    protected function selectorIcon()
    {
        return '@app/icons/hash.svg';
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
