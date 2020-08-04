<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\base\ElementInterface;
use craft\fields\data\MultiOptionsFieldData;

/**
 * MultiSelect represents a Multi-select field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class MultiSelect extends BaseOptionsField
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Multi-select');
    }

    /**
     * @inheritdoc
     */
    public static function valueType(): string
    {
        return MultiOptionsFieldData::class;
    }

    /**
     * @inheritdoc
     */
    public $multi = true;

    /**
     * @inheritdoc
     */
    public $optgroups = true;

    /**
     * @inheritdoc
     */
    protected function inputHtml($value, ElementInterface $element = null): string
    {
        return Craft::$app->getView()->renderTemplate('_includes/forms/multiselect', [
            'name' => $this->handle,
            'values' => $value,
            'options' => $this->translatedOptions(),
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function optionsSettingLabel(): string
    {
        return Craft::t('app', 'Multi-select Options');
    }
}
