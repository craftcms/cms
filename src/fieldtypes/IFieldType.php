<?php
namespace Craft;

/**
 * Interface IFieldType
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.fieldtypes
 * @since     1.0
 */
interface IFieldType extends ISavableComponentType
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns the content attribute config.
	 *
	 * @return mixed
	 */
	public function defineContentAttribute();

	/**
	 * Returns the field's input HTML.
	 *
	 * @param string $name
	 * @param mixed  $value
	 *
	 * @return string
	 */
	public function getInputHtml($name, $value);

	/**
	 * Returns the input value as it should be saved to the database.
	 *
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	public function prepValueFromPost($value);

	/**
	 * Validates the value beyond the checks that were assumed based on the content attribute.  Returns 'true' or any
	 * custom validation errors.
	 *
	 * @param mixed $value
	 *
	 * @return true|string|array
	 */
	public function validate($value);

	/**
	 * Returns the search keywords that should be associated with this field, based on the prepped post data.
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	public function getSearchKeywords($value);

	/**
	 * Performs any actions before a field is saved.
	 *
	 * @return null
	 */
	public function onBeforeSave();

	/**
	 * Performs any actions after a field is saved.
	 *
	 * @return null
	 */
	public function onAfterSave();

	/**
	 * Performs any actions before a field is deleted.
	 *
	 * @return null
	 */
	public function onBeforeDelete();

	/**
	 * Performs any actions after a field is deleted.
	 *
	 * @return null
	 */
	public function onAfterDelete();

	/**
	 * Performs any additional actions after the element has been saved.
	 *
	 * @return null
	 */
	public function onAfterElementSave();

	/**
	 * @inheritDoc IFieldType::prepValue()
	 *
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	public function prepValue($value);

	/**
	 * Modifies an element query that's filtering by this field.
	 *
	 * @param DbCommand $query
	 * @param mixed     $value
	 *
	 * @return null|false
	 */
	public function modifyElementsQuery(DbCommand $query, $value);
}
