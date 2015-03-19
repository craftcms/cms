<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use craft\app\base\Model;

/**
 * Params model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Params extends Model
{
	// Properties
	// =========================================================================

	/**
	 * @var array
	 */
	private $_attributes;

	// Public Methods
	// =========================================================================

	public function __isset($name)
	{
		return true;
	}

	public function __set($name, $value)
	{
		$this->_attributes[$name] = $value;
	}

	public function __get($name)
	{
		if (isset($this->_attributes[$name]))
		{
			return $this->_attributes[$name];
		}
		else
		{
			return null;
		}
	}
}
