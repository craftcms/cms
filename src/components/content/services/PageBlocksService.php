<?php
namespace Blocks;

/**
 *
 */
class PageBlocksService extends BaseBlocksService
{
	/**
	 * The block model class name.
	 *
	 * @access protected
	 * @var string
	 */
	protected $blockModelClass = 'PageBlockModel';

	/**
	 * The block record class name.
	 *
	 * @access protected
	 * @var string
	 */
	protected $blockRecordClass = 'PageBlockRecord';

	/**
	 * The content record class name.
	 *
	 * @access protected
	 * @var string
	 */
	protected $contentRecordClass = 'PageContentRecord';

	/**
	 * Populates a block record from a model.
	 *
	 * @access protected
	 * @param PageBlockModel $block
	 * @return PageBlockRecord $blockRecord;
	 */
	protected function populateBlockRecord(PageBlockModel $block)
	{
		$blockRecord = parent::populateBlockRecord($block);
		$blockRecord->pageId = $block->pageId;
		return $blockRecord;
	}

	/**
	 * Returns the content table name.
	 *
	 * @param BaseBlockModel $block
	 * @access protected
	 * @return string|false
	 */
	protected function getContentTable(BaseBlockModel $block)
	{
		return false;
	}

	/**
	 * Returns all blocks by a page ID.
	 *
	 * @param int $pageId
	 * @return array
	 */
	public function getBlocksByPageId($pageId)
	{
		$blockRecords = PageBlockRecord::model()->ordered()->findAllByAttributes(array(
			'pageId' => $pageId
		));
		return $this->populateBlocks($blockRecords);
	}

	/**
	 * Returns the total number of blocks by a page ID.
	 *
	 * @param int $pageId
	 * @return int
	 */
	public function getTotalBlocksByPageId($pageId)
	{
		return PageBlockRecord::model()->countByAttributes(array(
			'pageId' => $pageId
		));
	}
}
