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
			'key'      => array(AttributeType::String, 'required' => true, 'unique' => true, 'maxLength' => 150, 'column' => ColumnType::Char),
			'template' => array(AttributeType::String, 'maxLength' => 500),
		);
	}

	public function defineRelations()
	{
		return array(
			'content' => array(static::HAS_MANY, 'EmailMessageContentRecord', 'messageId'),
		);
	}
}
