<?php
namespace Blocks;

/**
 *
 */
class EmailMessageContentRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'emailmessagecontnet';
	}

	protected function defineAttributes()
	{
		return array(
			'language' => AttributeType::Language,
			'subject'  => array(AttributeType::Varchar, 'required' => true, 'maxLength' => 1000),
			'body'     => array(AttributeType::Text, 'required' => true),
			'htmlBody' => AttributeType::Text,
		);
	}

	protected function defineRelations()
	{
		return array(
			'message' => array(static::BELONGS_TO, 'EmailMessageRecord'),
		);
	}

	protected function defineIndexes()
	{
		return array(
			array('columns' => array('messageId', 'language'), 'unique' => true)
		);
	}
}
