<?php
namespace Blocks;

/**
 *
 */
class AssetContentRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'assetcontent';
	}

	public function defineRelations()
	{
		return array(
			'file' => array(static::BELONGS_TO, 'AssetFileRecord', 'unique' => true, 'required' => true),
		);
	}
}
