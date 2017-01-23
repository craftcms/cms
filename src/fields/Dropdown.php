<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\fields;

use Craft;
use craft\base\ElementInterface;

/**
 * Dropdown represents a Dropdown field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Dropdown extends BaseOptionsField
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Dropdown');
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        $options = $this->translatedOptions();

        // If this is a new entry, look for a default option
        if ($this->isFresh($element)) {
            $value = $this->defaultValue();
        }

        return Craft::$app->getView()->renderTemplate('_includes/forms/select',
            [
                'name' => $this->handle,
                'value' => $value,
                'options' => $options
            ]);
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function optionsSettingLabel(): string
    {
        return Craft::t('app', 'Dropdown Options');
    }
}
