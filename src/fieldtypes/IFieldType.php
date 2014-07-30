<?php
namespace Craft;

/**
 * Interface IFieldType
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.etc.fieldtypes
 * @since     1.0
 */
interface IFieldType extends ISavableComponentType
{
	/**
	 * @return mixed
	 */
	public function defineContentAttribute();

	/**
	 * @param string $name
	 * @param mixed  $value
	 * @return string
	 */
	public function getInputHtml($name, $value);

	/**
	 * @param mixed $value
	 * @return mixed
	 */
	public function prepValueFromPost($value);

	/**
	 * @param mixed $value
	 * @return true|string|array
	 */
	public function validate($value);

	/**
	 * @param mixed $value
	 * @return string
	 */
	public function getSearchKeywords($value);

	/**
	 */
	public function onBeforeSave();

	/**
	 */
	public function onAfterSave();

	/**
	 */
	public function onBeforeDelete();

	/**
	 */
	public function onAfterDelete();

	/**
	 */
	public function onAfterElementSave();

	/**
	 * @param mixed $value
	 * @return mixed
	 */
	public function prepValue($value);

	/**
	 * @param DbCommand $query
	 * @param mixed     $value
	 * @return void|false
	 */
	public function modifyElementsQuery(DbCommand $query, $value);
}
