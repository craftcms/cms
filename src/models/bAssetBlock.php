<?php

/**
 *
 */
class bAssetBlock extends bBaseBlocksModel
{
	protected $tableName = 'assetblocks';

	protected $model = 'bAsset';
	protected $foreignKey = 'asset';

	/**
	 * Returns an instance of the specified model
	 * @return object The model instance
	 * @static
	 */
	public static function model($class = __CLASS__)
	{
		return parent::model($class);
	}
}
