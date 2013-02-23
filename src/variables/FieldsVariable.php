<?php
namespace Blocks;

/**
 * Content functions
 */
class FieldsVariable
{
	// Groups
	// ======

	/**
	 * Returns all field groups.
	 *
	 * @param string|null $indexBy
	 * @return array
	 */
	public function getAllGroups($indexBy = null)
	{
		return blx()->fields->getAllGroups($indexBy);
	}

	/**
	 * Returns a field group by its ID.
	 *
	 * @param int $groupId
	 * @return FieldGroupModel|null
	 */
	public function getGroupById($groupId)
	{
		return blx()->fields->getGroupById($groupId);
	}

	// Fields
	// ======

	/**
	 * Returns a field by its ID.
	 *
	 * @param int $fieldId
	 * @return FieldModel|null
	 */
	public function getFieldById($fieldId)
	{
		return blx()->fields->getFieldById($fieldId);
	}

	/**
	 * Returns all fields.
	 *
	 * @param string|null $indexBy
	 * @return array
	 */
	public function getAllFields($indexBy = null)
	{
		return blx()->fields->getAllFields($indexBy);
	}

	/**
	 * Returns all the fields in a given group.
	 *
	 * @param int         $groupId
	 * @param string|null $indexBy
	 * @return array
	 */
	public function getFieldsByGroupId($groupId, $indexBy = null)
	{
		return blx()->fields->getFieldsByGroupId($groupId, $indexBy);
	}

	// Layouts
	// =======

	/**
	 * Returns a field layout by its ID.
	 *
	 * @param int $layoutId
	 * @return FieldLayoutModel|null
	 */
	public function getLayoutById($layoutId)
	{
		return blx()->fields->getLayoutById($layoutId);
	}

	/**
	 * Returns a field layout by its type.
	 *
	 * @param int $type
	 * @return FieldLayoutModel|null
	 */
	public function getLayoutByType($type)
	{
		return blx()->fields->getLayoutByType($type);
	}

	// Fieldtypes
	// ==========

	/**
	 * Returns all installed fieldtypes.
	 *
	 * @return array
	 */
	public function getAllFieldTypes()
	{
		$fieldTypes = blx()->fields->getAllFieldTypes();
		return FieldTypeVariable::populateVariables($fieldTypes);
	}

	/**
	 * Gets a fieldtype.
	 *
	 * @param string $class
	 * @return FieldTypeVariable|null
	 */
	public function getFieldType($class)
	{
		$fieldType = blx()->fields->getFieldType($class);

		if ($fieldType)
		{
			return new FieldTypeVariable($fieldType);
		}
	}

	/**
	 * Populates a fieldtype.
	 *
	 * @param FieldModel $field
	 * @param ElementModel|null $element
	 * @return BaseFieldType|null
	 */
	public function populateFieldType(FieldModel $field, $element = null)
	{
		$fieldType = blx()->fields->populateFieldType($field, $element);

		if ($fieldType)
		{
			return new FieldTypeVariable($fieldType);
		}
	}
}
