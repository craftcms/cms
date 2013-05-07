<?php
namespace Craft;

/**
 * Global Set element type
 */
class GlobalSetElementType extends BaseElementType
{
	/**
	 * Returns the element type name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Global Sets');
	}

	/**
	 * Returns whether this element type is translatable.
	 *
	 * @return bool
	 */
	public function isTranslatable()
	{
		return true;
	}

	/**
	 * Populates an element model based on a query result.
	 *
	 * @param array $row
	 * @return array
	 */
	public function populateElementModel($row)
	{
		return GlobalSetModel::populateModel($row);
	}
}
