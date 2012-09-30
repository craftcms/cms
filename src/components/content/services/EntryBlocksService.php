<?php
namespace Blocks;

/**
 *
 */
class EntryBlocksService extends BaseBlocksService
{
	protected $blockModelClass = 'EntryBlockModel';
	protected $blockRecordClass = 'EntryBlockRecord';
	protected $contentRecordClass = 'EntryContentRecord';
	protected $placeBlockColumnsAfter = 'entryId';
}
