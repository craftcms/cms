<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\base;

use yii\base\Arrayable;

/**
 * This interface defines the contract that all Craft components must implement.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
interface ComponentInterface extends Arrayable
{
	// Public Methods
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
	public static function classDisplayName();

	/**
	 * Returns a unique handle that can be used to refer to this class.
	 * @return string The class handle.
	 */
	public static function classHandle();

}
