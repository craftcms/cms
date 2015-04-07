<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\volumes;

use craft\app\base\Volume;
use craft\app\base\InvalidComponentInterface;
use craft\app\base\InvalidComponentTrait;
use craft\app\base\VolumeTrait;

/**
 * InvalidSource represents a s with an invalid class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class InvalidVolume extends Volume implements InvalidComponentInterface
{
	// Traits
	// =========================================================================

	use InvalidComponentTrait;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	protected function createAdapter()
	{
		return null;
	}

	/**
	 * @inheritdoc
	 */
	public function getRootUrl()
	{
		return null;
	}

	/**
	 * @inheritdoc
	 */
	public function getRootPath()
	{
		return null;
	}
}
