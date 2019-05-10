<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\base\ElementInterface;
use craft\elements\Category;
use craft\elements\db\CategoryQuery;
use craft\helpers\ElementHelper;

/**
 * Categories represents a Categories field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Categories extends BaseRelationField
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Categories');
    }

    /**
     * @inheritdoc
     */
    protected static function elementType(): string
    {
        return Category::class;
    }

    /**
     * @inheritdoc
     */
    public static function defaultSelectionLabel(): string
    {
        return Craft::t('app', 'Add a category');
    }

    /**
     * @inheritdoc
     */
    public static function valueType(): string
    {
        return CategoryQuery::class;
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->allowLimit = false;
        $this->allowMultipleSources = false;
        $this->allowRelateParents = false;
        $this->relateParents = true;
        $this->settingsTemplate = '_components/fieldtypes/Categories/settings';
        $this->structure = true;

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        // Make sure the field is set to a valid category group
        if ($this->source) {
            $source = ElementHelper::findSource(static::elementType(), $this->source, 'field');
        }

        if (empty($source)) {
            return '<p class="error">' . Craft::t('app', 'This field is not set to a valid category group.') . '</p>';
        }

        return parent::getInputHtml($value, $element);
    }
}
