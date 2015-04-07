<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\base;

/**
 * InvalidComponentInterface defines the common interface to be implemented by invalid component classes.
 *
 * A class implementing this interface should also implement [[ComponentInterface]] and [[\yii\base\Arrayable]],
 * and use [[InvalidComponentTrait]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
interface InvalidComponentInterface
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns the expected component class name.
	 *
	 * @return string
	 */
	public function getType();
}
