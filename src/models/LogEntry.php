<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use craft\app\base\Model;
use craft\app\enums\AttributeType;

/**
 * Class LogEntry model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class LogEntry extends Model
{
	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc Model::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return [
			'dateTime'    => AttributeType::DateTime,
			'level'       => AttributeType::String,
			'category'    => AttributeType::Number,
			'get'         => AttributeType::Mixed,
			'post'        => AttributeType::Mixed,
			'cookie'      => AttributeType::Mixed,
			'session'     => AttributeType::Mixed,
			'server'      => AttributeType::Mixed,
			'profile'     => AttributeType::Mixed,
			'message'     => AttributeType::String,
		];
	}
}
