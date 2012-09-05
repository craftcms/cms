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
			'content' => array(static::HAS_MANY, 'EmailMessageContentRecord', 'messageId'),
		);
	}
}
