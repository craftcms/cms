<?php
namespace Blocks;

/**
 *
 */
class EntryBlocksService extends BaseBlocksService
{
	/**
	 * The block model class name.
	 *
	 * @access protected
	 * @var string
	 */
	protected $blockModelClass = 'EntryBlockModel';

	/**
	 * The block record class name.
	 *
	 * @access protected
	 * @var string
	 */
	protected $blockRecordClass = 'EntryBlockRecord';

	/**
	 * The content record class name.
	 *
	 * @access protected
	 * @var string
	 */
	protected $contentRecordClass = 'EntryContentRecord';

	/**
	 * The name of the content table column right before where the block columns should be inserted.
	 *
	 * @access protected
	 * @var string
	 */
	protected $placeBlockColumnsAfter = 'entryId';
}
