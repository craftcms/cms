<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\SortableFieldInterface;
use craft\fields\data\SingleOptionFieldData;

/**
 * RadioButtons represents a Radio Buttons field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class RadioButtons extends BaseOptionsField implements SortableFieldInterface
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Radio Buttons');
    }

    /**
     * @inheritdoc
     */
    public static function valueType(): string
    {
        return SingleOptionFieldData::class;
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml($value, ElementInterface $element = null): string
    {
        return Craft::$app->getView()->renderTemplate('_includes/forms/radioGroup', [
            'name' => $this->handle,
            'value' => $value,
            'options' => $this->translatedOptions(),
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function optionsSettingLabel(): string
    {
        return Craft::t('app', 'Radio Button Options');
    }
}
