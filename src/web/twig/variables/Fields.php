<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\variables;

use Craft;
use craft\base\FieldInterface;
use craft\helpers\ArrayHelper;
use craft\models\FieldGroup;
use craft\models\FieldLayout;

/**
 * Fields provides an API for accessing information about fields. It is accessible from templates via `craft.fields`.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 3.0.0
 */
class Fields
{
    // Groups
    // -------------------------------------------------------------------------

    /**
     * Returns all field groups.
     *
     * @param string|null $indexBy The attribute to index the field groups by
     * @return FieldGroup[] The field groups
     */
    public function getAllGroups(string $indexBy = null): array
    {
        Craft::$app->getDeprecator()->log('craft.fields.getAllGroups()', '`craft.fields.getAllGroups()` has been deprecated. Use `craft.app.fields.allGroups` instead.');

        $groups = Craft::$app->getFields()->getAllGroups();

        return $indexBy ? ArrayHelper::index($groups, $indexBy) : $groups;
    }

    /**
     * Returns a field group by its ID.
     *
     * @param int $groupId The field group’s ID
     * @return FieldGroup|null The field group, or null if it doesn’t exist
     */
    public function getGroupById(int $groupId)
    {
        Craft::$app->getDeprecator()->log('craft.fields.getGroupById()', '`craft.fields.getGroupById()` has been deprecated. Use `craft.app.fields.getGroupById()` instead.');

        return Craft::$app->getFields()->getGroupById($groupId);
    }

    // Fields
    // -------------------------------------------------------------------------

    /**
     * Returns a field by its ID.
     *
     * @param int $fieldId The field’s ID
     * @return FieldInterface|null The field, or null if it doesn’t exist
     */
    public function getFieldById(int $fieldId)
    {
        Craft::$app->getDeprecator()->log('craft.fields.getFieldById()', '`craft.fields.getFieldById()` has been deprecated. Use `craft.app.fields.getFieldById()` instead.');

        return Craft::$app->getFields()->getFieldById($fieldId);
    }

    /**
     * Returns a field by its handle.
     *
     * @param string $handle The field’s handle
     * @return FieldInterface|null The field, or null if it doesn’t exist
     */
    public function getFieldByHandle(string $handle)
    {
        Craft::$app->getDeprecator()->log('craft.fields.getFieldByHandle()', '`craft.fields.getFieldByHandle()` has been deprecated. Use `craft.app.fields.getFieldByHandle()` instead.');

        return Craft::$app->getFields()->getFieldByHandle($handle);
    }

    /**
     * Returns all fields.
     *
     * @param string|null $indexBy The attribute to index the fields by
     * @return FieldInterface[] The fields
     */
    public function getAllFields(string $indexBy = null): array
    {
        Craft::$app->getDeprecator()->log('craft.fields.getAllFields()', '`craft.fields.getAllFields()` has been deprecated. Use `craft.app.fields.allFields` instead.');

        $fields = Craft::$app->getFields()->getAllFields();

        return $indexBy ? ArrayHelper::index($fields, $indexBy) : $fields;
    }

    /**
     * Returns all the fields in a given group.
     *
     * @param int $groupId The field group’s ID
     * @param string|null $indexBy The attribute to index the fields by
     * @return FieldInterface[] The fields
     */
    public function getFieldsByGroupId(int $groupId, string $indexBy = null): array
    {
        Craft::$app->getDeprecator()->log('craft.fields.getFieldsByGroupId()', '`craft.fields.getFieldsByGroupId()` has been deprecated. Use `craft.app.fields.getFieldsByGroupId()` instead.');

        $fields = Craft::$app->getFields()->getFieldsByGroupId($groupId);

        return $indexBy ? ArrayHelper::index($fields, $indexBy) : $fields;
    }

    /**
     * Returns a field layout by its ID.
     *
     * @param int $layoutId The field layout’s ID
     * @return FieldLayout|null The field layout, or null if it doesn’t exist
     */
    public function getLayoutById(int $layoutId)
    {
        Craft::$app->getDeprecator()->log('craft.fields.getLayoutById()', '`craft.fields.getLayoutById()` has been deprecated. Use `craft.app.fields.getLayoutById()` instead.');

        return Craft::$app->getFields()->getLayoutById($layoutId);
    }

    /**
     * Returns a field layout by its associated element type.
     *
     * @param string $type The associated element type
     * @return FieldLayout The field layout
     */
    public function getLayoutByType(string $type): FieldLayout
    {
        Craft::$app->getDeprecator()->log('craft.fields.getLayoutByType()', '`craft.fields.getLayoutByType()` has been deprecated. Use `craft.app.fields.getLayoutByType()` instead.');

        return Craft::$app->getFields()->getLayoutByType($type);
    }

    /**
     * Returns all available field type classes.
     *
     * @return string[] The available field type classes
     */
    public function getAllFieldTypes(): array
    {
        Craft::$app->getDeprecator()->log('craft.fields.getAllFieldTypes()', '`craft.fields.getAllFieldTypes()` has been deprecated. Use `craft.app.fields.allFieldTypes` instead.');

        return Craft::$app->getFields()->getAllFieldTypes();
    }
}
