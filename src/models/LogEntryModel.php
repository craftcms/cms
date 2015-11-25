<?php
namespace Craft;

/**
 * Class LogEntryModel
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.models
 * @since     1.3
 */
class LogEntryModel extends BaseModel
{
	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseModel::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
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
		);
	}
}
