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
 * Checkboxes represents a Checkboxes field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Checkboxes extends BaseOptionsField
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Checkboxes');
    }

    /**
     * @inheritdoc
     */
    public static function valueType(): string
    {
        return MultiOptionsFieldData::class;
    }

    // Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public $multi = true;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->multi = true;
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        return Craft::$app->getView()->renderTemplate('_includes/forms/checkboxGroup', [
            'name' => $this->handle,
            'values' => $value,
            'options' => $this->translatedOptions(),
        ]);
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function optionsSettingLabel(): string
    {
        return Craft::t('app', 'Checkbox Options');
    }
}
