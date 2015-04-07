<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\tasks;

use craft\app\base\Task;
use craft\app\base\InvalidComponentInterface;
use craft\app\base\InvalidComponentTrait;

/**
 * InvalidWidget represents a widget with an invalid class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class InvalidTask extends Task implements InvalidComponentInterface
{
	// Traits
	// =========================================================================

	use InvalidComponentTrait;

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	protected function getDefaultDescription()
	{
		return $this->type;
	}
}
