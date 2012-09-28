<?php
namespace Blocks;

/**
 *
 */
class EntryBlocksService extends BaseBlocksService
{
	protected $blockPackageClass = 'EntryBlockPackage';
	protected $blockRecordClass = 'EntryBlockRecord';
	protected $contentRecordClass = 'EntryContentRecord';
	protected $placeBlockColumnsAfter = 'entryId';
}
