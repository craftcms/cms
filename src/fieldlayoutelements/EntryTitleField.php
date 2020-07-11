<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements;

use craft\base\ElementInterface;
use craft\elements\Entry;
use craft\helpers\Html;
use yii\base\InvalidArgumentException;

/**
 * EntryTitleField represents a Title field that is available to be placed within an entry type’s field layout designer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class EntryTitleField extends TitleField
{

    /**
     * @inheritdoc
     */
    protected function selectorInnerHtml(): string
    {
        return
            Html::tag('span', '', [
                'class' => ['fld-title-field-icon', 'fld-field-hidden', 'hidden'],
            ]) .
            parent::selectorInnerHtml();
    }

    /**
     * @inheritdoc
     */
    public function inputHtml(ElementInterface $element = null, bool $static = false)
    {
        if (!$element instanceof Entry) {
            throw new InvalidArgumentException('EntryTitleField can only be used in entry field layouts.');
        }

        if (!$element->getType()->hasTitleField && !$element->hasErrors('title')) {
            return null;
        }

        return parent::inputHtml($element, $static);
    }
}
