<?php
namespace Blocks;

/**
 *
 */
class EmailMessageContent extends BaseModel
{
	public function getTableName()
	{
		return 'emailmessagecontnet';
	}

	protected function getProperties()
	{
		return array(
			'language'  => PropertyType::Language,
			'subject'   => array(PropertyType::Varchar, 'required' => true, 'maxLength' => 1000),
			'body'      => array(PropertyType::Text, 'required' => true),
			'html_body' => PropertyType::Text,
		);
	}

	protected function getRelations()
	{
		return array(
			'message' => array(static::BELONGS_TO, 'EmailMessage'),
		);
	}

	protected function getIndexes()
	{
		return array(
			array('columns' => array('message_id', 'language'), 'unique' => true)
		);
	}
}
