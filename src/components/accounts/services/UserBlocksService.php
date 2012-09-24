<?php
namespace Blocks;

/**
 *
 */
class UserBlocksService extends BaseBlocksService
{
	protected $blockPackageClass = 'UserBlockPackage';
	protected $blockRecordClass = 'UserBlockRecord';
	protected $contentRecordClass = 'UserContentRecord';
	protected $placeBlockColumnsAfter = 'userId';
}
