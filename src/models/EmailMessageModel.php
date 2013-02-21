<?php
namespace Blocks;

Blocks::requirePackage(BlocksPackage::Rebrand);

/**
 * Email message model class
 */
class EmailMessageModel extends BaseModel
{
	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'key'      => AttributeType::String,
			'locale'   => AttributeType::Locale,
			'subject'  => AttributeType::String,
			'body'     => AttributeType::String,
			'htmlBody' => AttributeType::String,
		);
	}
}
