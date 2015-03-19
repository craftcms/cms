<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\base;

/**
 * ComponentInterface defines the common interface to be implemented by Craft component classes.
 *
 * A class implementing this interface should also implement [[\yii\base\Arrayable]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
interface ComponentInterface
{
	// Static
	// =========================================================================

	/**
	 * Returns the fully qualified name of this class.
	 * @return static The fully qualified name of this class.
	 */
	public static function className();

	/**
	 * Returns the display name of this class.
	 * @return string The display name of this class.
	 */
	public static function displayName();

	/**
	 * Returns a unique handle that can be used to refer to this class.
	 * @return string The class handle.
	 */
	public static function classHandle();

	/**
	 * Instantiates a new component instance.
	 *
	 * This method is generally called together with [[populate()]].
	 * It is not meant to be used for creating new elements directly.
	 *
	 * You may override this method if the instance being created
	 * depends on the data to be populated into the element.
	 * For example, by creating an element based on the value of a column,
	 * you may implement the so-called single-table inheritance mapping.
	 *
	 * @param array $config Data to be populated into the record via [[populate()]] afterward.
	 * @return static The newly created component.
	 */
	public static function instantiate($config);

	/**
	 * Populates a component with a given set of attributes.
	 *
	 * This is an internal method meant to be called to create component objects after
	 * fetching data from the database or another source.
	 *
	 * @param ComponentInterface $model The component to be populated. In most cases this will be an instance
	 * created by [[instantiate()]] beforehand.
	 * @param array $config Attribute values to populate the component with (name => value).
	 */
	public static function populateModel($model, $config);

	// Public Methods
	// =========================================================================

	/**
	 * Returns the class name that should be used to represent the field.
	 *
	 * @return string The class name that should be used to represent the field.
	 */
	public function getType();
}
