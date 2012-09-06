<?php
namespace Blocks;

/**
 *
 */
class EmailMessageRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'emailmessages';
	}

	public function defineAttributes()
	{
		return array(
			'key'      => array(AttributeType::Char, 'required' => true, 'unique' => true, 'maxLength' => 150),
			'template' => array(AttributeType::Varchar, 'maxLength' => 500),
		);
	}

	public function defineRelations()
	{
		return array(
			'content' => array(static::HAS_MANY, 'EmailMessageContentRecord', 'messageId'),
		);
	}
}
