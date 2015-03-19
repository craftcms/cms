<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\variables;

use craft\app\base\Field;
use craft\app\base\FieldInterface;
use craft\app\models\FieldGroup as FieldGroupModel;
use craft\app\models\FieldLayout as FieldLayoutModel;

/**
 * Fields provides an API for accessing information about fields. It is accessible from templates via `craft.fields`.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
	 * @param string|null $indexBy
	 *
	 * @return FieldGroupModel[]
	 */
	public function getAllGroups($indexBy = null)
	{
		return \Craft::$app->fields->getAllGroups($indexBy);
	}

	/**
	 * Returns a field group by its ID.
	 *
	 * @param int $groupId
	 *
	 * @return FieldGroupModel|null
	 */
	public function getGroupById($groupId)
	{
		return \Craft::$app->fields->getGroupById($groupId);
	}

	// Fields
	// -------------------------------------------------------------------------

	/**
	 * Returns a field by its ID.
	 *
	 * @param int $fieldId
	 *
	 * @return FieldInterface|Field|null
	 */
	public function getFieldById($fieldId)
	{
		return \Craft::$app->fields->getFieldById($fieldId);
	}

	/**
	 * Returns a field by its handle.
	 *
	 * @param string $handle
	 *
	 * @return FieldInterface|Field|null
	 */
	public function getFieldByHandle($handle)
	{
		return \Craft::$app->fields->getFieldByHandle($handle);
	}

	/**
	 * Returns all fields.
	 *
	 * @param string$indexBy
	 *
	 * @return FieldInterface[]|Field[]
	 */
	public function getAllFields($indexBy = null)
	{
		return \Craft::$app->fields->getAllFields($indexBy);
	}

	/**
	 * Returns all the fields in a given group.
	 *
	 * @param int         $groupId
	 * @param string|null $indexBy
	 *
	 * @return FieldInterface[]|Field[]
	 */
	public function getFieldsByGroupId($groupId, $indexBy = null)
	{
		return \Craft::$app->fields->getFieldsByGroupId($groupId, $indexBy);
	}

	/**
	 * Returns a field layout by its ID.
	 *
	 * @param int $layoutId
	 *
	 * @return FieldLayoutModel|null
	 */
	public function getLayoutById($layoutId)
	{
		return \Craft::$app->fields->getLayoutById($layoutId);
	}

	/**
	 * Returns a field layout by its type.
	 *
	 * @param int $type
	 *
	 * @return FieldLayoutModel|null
	 */
	public function getLayoutByType($type)
	{
		return \Craft::$app->fields->getLayoutByType($type);
	}

	/**
	 * Returns all available field type classes.
	 *
	 * @return FieldInterface[] The available field type classes.
	 */
	public function getAllFieldTypes()
	{
		return \Craft::$app->fields->getAllFieldTypes();
	}

	/**
	 * Returns info about the field with the given class name.
	 *
	 * @param string|FieldInterface|Field $field
	 * @return ComponentInfo
	 */
	public function getFieldTypeInfo($field)
	{
		return new ComponentInfo($field);
	}

	/**
	 * Creates a field with a given config.
	 *
	 * @param mixed $config
	 * @return FieldInterface
	 */
	public function createField($config)
	{
		return \Craft::$app->fields->createField($config);
	}
}
