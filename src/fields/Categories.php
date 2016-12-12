<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\fields;

use Craft;
use craft\base\ElementInterface;
use craft\elements\Category;
use craft\helpers\ElementHelper;

/**
 * Categories represents a Categories field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Categories extends BaseRelationField
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName()
    {
        return Craft::t('app', 'Categories');
    }

    /**
     * @inheritdoc
     */
    protected static function elementType()
    {
        return Category::class;
    }

    /**
     * @inheritdoc
     */
    public static function defaultSelectionLabel()
    {
        return Craft::t('app', 'Add a category');
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->allowMultipleSources = false;
        $this->inputTemplate = '_components/fieldtypes/Categories/input';
        $this->inputJsClass = 'Craft.CategorySelectInput';
        $this->sortable = false;
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, $element)
    {
        // Make sure the field is set to a valid category group
        if ($this->source) {
            $source = ElementHelper::findSource(static::elementType(), $this->source, 'field');
        }

        if (empty($source)) {
            return '<p class="error">'.Craft::t('app', 'This field is not set to a valid category group.').'</p>';
        }

        return parent::getInputHtml($value, $element);
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function afterElementSave(ElementInterface $element, $isNew)
    {
        $value = $this->getElementValue($element);

        // Make sure something was actually posted
        if ($value !== null) {
            $ids = $value->ids();

            // Fill in any gaps
            $ids = Craft::$app->getCategories()->fillGapsInCategoryIds($ids);

            Craft::$app->getRelations()->saveRelations($this, $element, $ids);
        }

        parent::afterElementSave($element, $isNew);
    }
}
