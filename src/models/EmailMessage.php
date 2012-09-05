<?php
namespace Blocks;

/**
 *
 */
class EmailMessage extends BaseModel
{
	public function getTableName()
	{
		return 'emailmessages';
	}

	protected function getProperties()
	{
		return array(
			'key'      => array(PropertyType::Char, 'required' => true, 'unique' => true, 'maxLength' => 150),
			'template' => array(PropertyType::Varchar, 'maxLength' => 500),
		);
	}

	protected function getRelations()
	{
		return array(
			'content' => array(static::HAS_MANY, 'EmailMessageContent', 'messageId'),
		);
	}
}
