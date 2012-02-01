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
}
