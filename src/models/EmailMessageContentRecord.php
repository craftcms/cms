<?php
namespace Blocks;

/**
 *
 */
class EmailMessageContentRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'emailmessagecontent';
	}

	public function defineAttributes()
	{
		return array(
			'language' => array(AttributeType::Language, 'required' => true),
			'subject'  => array(AttributeType::String, 'required' => true, 'maxLength' => 1000),
			'body'     => array(AttributeType::String, 'required' => true, 'column' => ColumnType::Text),
			'htmlBody' => array(AttributeType::String, 'column' => ColumnType::Text),
		);
	}

	public function defineRelations()
	{
		return array(
			'message' => array(static::BELONGS_TO, 'EmailMessageRecord'),
		);
	}

	public function defineIndexes()
	{
		return array(
			array('columns' => array('messageId', 'language'), 'unique' => true)
		);
	}
}
