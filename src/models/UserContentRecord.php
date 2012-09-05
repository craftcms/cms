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

	protected function getRelations()
	{
		return array(
			'user' => array(static::BELONGS_TO, 'User', 'unique' => true, 'required' => true),
		);
	}
}
