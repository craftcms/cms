<?php
namespace Blocks;

/**
 *
 */
class AssetBlocksService extends BaseBlocksService
{
	protected $blockPackageClass = 'AssetBlockPackage';
	protected $blockRecordClass = 'AssetBlockRecord';
	protected $contentRecordClass = 'AssetContentRecord';
	protected $placeBlockColumnsAfter = 'fileId';
}
