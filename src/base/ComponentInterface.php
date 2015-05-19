<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
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
	 * Instantiates and returns a new component object.
	 *
	 * @param array $config The config settings to populate the component with
	 * @return static The new component object
	 */
	public static function create($config);

	// Public Methods
	// =========================================================================

	/**
	 * Returns the class name that should be used to represent the field.
	 *
	 * @return string The class name that should be used to represent the field.
	 */
	public function getType();
}
