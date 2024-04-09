<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements\entries;

use craft\base\ElementInterface;
use craft\base\Field;
use craft\elements\Entry;
use craft\fieldlayoutelements\TitleField;
use craft\helpers\Cp;
use craft\helpers\ElementHelper;
use craft\helpers\Html;
use yii\base\InvalidArgumentException;

/**
 * EntryTitleField represents a Title field that can be included within an entry type’s field layout designer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class EntryTitleField extends TitleField
{
    /**
     * @inheritdoc
     */
    public bool $required = false;

    /**
     * @inheritdoc
     */
    protected function selectorInnerHtml(): string
    {
        return
            Html::tag('div', Cp::iconSvg('shuteye'), [
                'class' => ['cp-icon', 'medium', 'gray', 'fld-title-field-icon', 'fld-field-hidden', 'hidden'],
            ]) .
            parent::selectorInnerHtml();
    }

    /**
     * @inheritdoc
     */
    protected function translatable(?ElementInterface $element = null, bool $static = false): bool
    {
        if (!$element instanceof Entry) {
            throw new InvalidArgumentException(sprintf('%s can only be used in entry field layouts.', __CLASS__));
        }

        return $element->getType()->titleTranslationMethod !== Field::TRANSLATION_METHOD_NONE;
    }

    /**
     * @inheritdoc
     */
    protected function translationDescription(?ElementInterface $element = null, bool $static = false): ?string
    {
        if (!$element instanceof Entry) {
            throw new InvalidArgumentException(sprintf('%s can only be used in entry field layouts.', __CLASS__));
        }

        return ElementHelper::translationDescription($element->getType()->titleTranslationMethod);
    }

    /**
     * @inheritdoc
     */
    public function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        if (!$element instanceof Entry) {
            throw new InvalidArgumentException(sprintf('%s can only be used in entry field layouts.', __CLASS__));
        }

        if (!$element->getType()->hasTitleField) {
            return null;
        }

        $this->required = true;

        return parent::inputHtml($element, $static);
    }
}
