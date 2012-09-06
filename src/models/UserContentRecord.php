<?php
namespace Blocks;

/**
 *
 */
class UserContentRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'usercontent';
	}

	protected function defineRelations()
	{
		return array(
			'user' => array(static::BELONGS_TO, 'UserRecord', 'unique' => true, 'required' => true),
		);
	}
}
