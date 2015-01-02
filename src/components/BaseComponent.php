<?php
namespace craft\app\components;

use yii\base\Component;

/**
 * BaseComponent class
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.components
 * @since     3.0
 */
class BaseComponent extends Component
{
	// Public Methods
	// =========================================================================
	/**
	 * Checks if this application component has been initialized yet, or not.
	 *
	 * Craft is overriding this for consistency.
	 *
	 * @return bool Whether this application component has been initialized (i.e., {@link init()} has been invoked).
	 */
	public function isInitialized()
	{
		return $this->getIsInitialized();
	}
}
