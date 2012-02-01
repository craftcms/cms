<?php
namespace Blocks;

/**
 *
 */
class AssetBlock extends BaseBlocksModel
{
	protected $tableName = 'assetblocks';
	protected $model = 'Asset';
	protected $foreignKey = 'asset';
}
