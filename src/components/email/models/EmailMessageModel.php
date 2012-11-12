<?php
namespace Blocks;

/**
 * Email message model class
 */
class EmailMessageModel extends BaseModel
{
	public function defineAttributes()
	{
		return array(
			'key'      => AttributeType::String,
			'language' => AttributeType::Language,
			'subject'  => AttributeType::String,
			'body'     => AttributeType::String,
			'htmlBody' => AttributeType::String,
		);
	}
}
