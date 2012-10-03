<?php
namespace Blocks;

/**
 *
 */
class AssetContentRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'assetcontent';
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'file' => array(static::BELONGS_TO, 'AssetFileRecord', 'unique' => true, 'required' => true),
		);
	}
}
