<?php
namespace Craft;

/**
 * Class BaseApplicationComponent
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.components
 * @since     1.0
 */
class BaseApplicationComponent extends \CApplicationComponent
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
