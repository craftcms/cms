<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements\assets;

use craft\base\ElementInterface;
use craft\base\Field;
use craft\elements\Asset;
use craft\fieldlayoutelements\TitleField;
use craft\helpers\ElementHelper;
use yii\base\InvalidArgumentException;

/**
 * AssetTitleField represents a Title field that can be included within a volume’s field layout designer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.0
 */
class AssetTitleField extends TitleField
{
    /**
     * @inheritdoc
     */
    protected function translatable(?ElementInterface $element = null, bool $static = false): bool
    {
        if (!$element instanceof Asset) {
            throw new InvalidArgumentException('AssetTitleField can only be used in asset field layouts.');
        }

        return $element->getVolume()->titleTranslationMethod !== Field::TRANSLATION_METHOD_NONE;
    }

    /**
     * @inheritdoc
     */
    protected function translationDescription(?ElementInterface $element = null, bool $static = false): ?string
    {
        if (!$element instanceof Asset) {
            throw new InvalidArgumentException('AssetTitleField can only be used in asset field layouts.');
        }

        return ElementHelper::translationDescription($element->getVolume()->titleTranslationMethod);
    }
}
