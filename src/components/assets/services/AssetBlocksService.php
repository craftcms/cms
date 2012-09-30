<?php
namespace Blocks;

/**
 *
 */
class AssetBlocksService extends BaseBlocksService
{
	protected $blockModelClass = 'AssetBlockModel';
	protected $blockRecordClass = 'AssetBlockRecord';
	protected $contentRecordClass = 'AssetContentRecord';
	protected $placeBlockColumnsAfter = 'fileId';
}
