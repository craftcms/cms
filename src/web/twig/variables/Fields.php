<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\web\twig\variables;

use Craft;
use craft\app\base\FieldInterface;
use craft\app\models\FieldGroup;
use craft\app\models\FieldLayout;

/**
 * Fields provides an API for accessing information about fields. It is accessible from templates via `craft.fields`.
 *
 * @author     Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since      3.0
 * @deprecated in 3.0
 */
class Fields
{
    // Public Methods
    // =========================================================================

    // Groups
    // -------------------------------------------------------------------------

    /**
     * Returns all field groups.
     *
     * @param string|null $indexBy The attribute to index the field groups by
     *
     * @return FieldGroup[] The field groups
     */
    public function getAllGroups($indexBy = null)
    {
        Craft::$app->getDeprecator()->log('craft.fields.getAllGroups()', 'craft.fields.getAllGroups() has been deprecated. Use craft.app.fields.getAllGroups() instead.');

        return Craft::$app->getFields()->getAllGroups($indexBy);
    }

    /**
     * Returns a field group by its ID.
     *
     * @param integer $groupId The field group’s ID
     *
     * @return FieldGroup|null The field group, or null if it doesn’t exist
     */
    public function getGroupById($groupId)
    {
        Craft::$app->getDeprecator()->log('craft.fields.getGroupById()', 'craft.fields.getGroupById() has been deprecated. Use craft.app.fields.getGroupById() instead.');

        return Craft::$app->getFields()->getGroupById($groupId);
    }

    // Fields
    // -------------------------------------------------------------------------

    /**
     * Creates a field with a given config.
     *
     * @param mixed $config The field’s class name, or its config, with a `type` value and optionally a `settings` value
     *
     * @return FieldInterface The field
     */
    public function createField($config)
    {
        Craft::$app->getDeprecator()->log('craft.fields.createField()', 'craft.fields.createField() has been deprecated. Use craft.app.fields.createField() instead.');

        return Craft::$app->getFields()->createField($config);
    }

    /**
     * Returns a field by its ID.
     *
     * @param integer $fieldId The field’s ID
     *
     * @return FieldInterface|null The field, or null if it doesn’t exist
     */
    public function getFieldById($fieldId)
    {
        Craft::$app->getDeprecator()->log('craft.fields.getFieldById()', 'craft.fields.getFieldById() has been deprecated. Use craft.app.fields.getFieldById() instead.');

        return Craft::$app->getFields()->getFieldById($fieldId);
    }

    /**
     * Returns a field by its handle.
     *
     * @param string $handle The field’s handle
     *
     * @return FieldInterface|null The field, or null if it doesn’t exist
     */
    public function getFieldByHandle($handle)
    {
        Craft::$app->getDeprecator()->log('craft.fields.getFieldByHandle()', 'craft.fields.getFieldByHandle() has been deprecated. Use craft.app.fields.getFieldByHandle() instead.');

        return Craft::$app->getFields()->getFieldByHandle($handle);
    }

    /**
     * Returns all fields.
     *
     * @param string|null $indexBy The attribute to index the fields by
     *
     * @return FieldInterface[] The fields
     */
    public function getAllFields($indexBy = null)
    {
        Craft::$app->getDeprecator()->log('craft.fields.getAllFields()', 'craft.fields.getAllFields() has been deprecated. Use craft.app.fields.getAllFields() instead.');

        return Craft::$app->getFields()->getAllFields($indexBy);
    }

    /**
     * Returns all the fields in a given group.
     *
     * @param integer     $groupId The field group’s ID
     * @param string|null $indexBy The attribute to index the fields by
     *
     * @return FieldInterface[] The fields
     */
    public function getFieldsByGroupId($groupId, $indexBy = null)
    {
        Craft::$app->getDeprecator()->log('craft.fields.getFieldsByGroupId()', 'craft.fields.getFieldsByGroupId() has been deprecated. Use craft.app.fields.getFieldsByGroupId() instead.');

        return Craft::$app->getFields()->getFieldsByGroupId($groupId, $indexBy);
    }

    /**
     * Returns a field layout by its ID.
     *
     * @param integer $layoutId The field layout’s ID
     *
     * @return FieldLayout|null The field layout, or null if it doesn’t exist
     */
    public function getLayoutById($layoutId)
    {
        Craft::$app->getDeprecator()->log('craft.fields.getLayoutById()', 'craft.fields.getLayoutById() has been deprecated. Use craft.app.fields.getLayoutById() instead.');

        return Craft::$app->getFields()->getLayoutById($layoutId);
    }

    /**
     * Returns a field layout by its associated element type.
     *
     * @param string $type The associated element type
     *
     * @return FieldLayout The field layout
     */
    public function getLayoutByType($type)
    {
        Craft::$app->getDeprecator()->log('craft.fields.getLayoutByType()', 'craft.fields.getLayoutByType() has been deprecated. Use craft.app.fields.getLayoutByType() instead.');

        return Craft::$app->getFields()->getLayoutByType($type);
    }

    /**
     * Returns all available field type classes.
     *
     * @return FieldInterface[] The available field type classes
     */
    public function getAllFieldTypes()
    {
        Craft::$app->getDeprecator()->log('craft.fields.getAllFieldTypes()', 'craft.fields.getAllFieldTypes() has been deprecated. Use craft.app.fields.allFieldTypes instead.');

        return Craft::$app->getFields()->getAllFieldTypes();
    }
}
