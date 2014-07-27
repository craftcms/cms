<?php
namespace Craft;

/**
 * Class LogEntryModel
 *
 * @package craft.app.models
 */
class LogEntryModel extends BaseModel
{
	protected function defineAttributes()
	{
		return array(
			'dateTime'    => AttributeType::String,
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
