<?php
namespace Craft;

/**
 * Fieldtype interface
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
	 * @return mixed
	 */
	public function getPostData();

	/**
	 */
	public function onBeforeSave();

	/**
	 */
	public function onAfterSave();

	/**
	 */
	public function onAfterElementSave();

	/**
	 * @param mixed $value
	 * @return mixed
	 */
	public function prepValue($value);
}
