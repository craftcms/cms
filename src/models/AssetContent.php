<?php
namespace Blocks;

/**
 *
 */
class AssetContent extends BaseContentModel
{
	protected $tableName = 'assetcontent';
	protected $model = 'Asset';
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
