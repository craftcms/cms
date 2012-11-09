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
			'key'      => array(AttributeType::String, 'required' => true, 'maxLength' => 150, 'column' => ColumnType::Char),
			'language' => array(AttributeType::Language, 'required' => true),
			'subject'  => array(AttributeType::String, 'required' => true, 'maxLength' => 1000),
			'body'     => array(AttributeType::String, 'required' => true, 'column' => ColumnType::Text),
			'htmlBody' => array(AttributeType::String, 'column' => ColumnType::Text),
		);
	}

	public function defineIndexes()
	{
		return array(
			array('columns' => array('key', 'language'), 'unique' => true),
		);
	}
}
