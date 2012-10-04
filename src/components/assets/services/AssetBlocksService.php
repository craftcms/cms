<?php
namespace Blocks;

/**
 *
 */
class AssetBlocksService extends BaseBlocksService
{
	/**
	 * The block model class name.
	 *
	 * @access protected
	 * @var string
	 */
	protected $blockModelClass = 'AssetBlockModel';

	/**
	 * The block record class name.
	 *
	 * @access protected
	 * @var string
	 */
	protected $blockRecordClass = 'AssetBlockRecord';

	/**
	 * The content record class name.
	 *
	 * @access protected
	 * @var string
	 */
	protected $contentRecordClass = 'AssetContentRecord';

	/**
	 * The name of the content table column right before where the block columns should be inserted.
	 *
	 * @access protected
	 * @var string
	 */
	protected $placeBlockColumnsAfter = 'fileId';
}
