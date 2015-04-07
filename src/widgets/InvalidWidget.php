<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\widgets;

use craft\app\base\Widget;
use craft\app\base\InvalidComponentInterface;
use craft\app\base\InvalidComponentTrait;

/**
 * InvalidWidget represents a widget with an invalid class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class InvalidWidget extends Widget implements InvalidComponentInterface
{
	// Traits
	// =========================================================================

	use InvalidComponentTrait;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function getBodyHtml()
	{
		return false;
	}
}
