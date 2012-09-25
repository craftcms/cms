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
	/* BLOCKSPRO ONLY */

	/**
	 * Populates a block package.
	 *
	 * @param array|EntryBlockRecord $attributes
	 * @return EntryBlockPackage
	 */
	public function populateBlock($attributes)
	{
		$block = parent::populateBlock($attributes);
		$block->sectionId = $attributes['sectionId'];
		return $block;
	}

	/**
	 * Populates a block record from a package.
	 *
	 * @access protected
	 * @param EntryBlockPackage $block
	 * @return EntryBlockRecord $blockRecord;
	 */
	protected function populateBlockRecord(EntryBlockPackage $block)
	{
		$blockRecord = parent::populateBlockRecord($block);
		$blockRecord->sectionId = $block->sectionId;
		return $blockRecord;
	}

	/**
	 * Returns the content table name.
	 *
	 * @param EntryBlockPackage $block
	 * @access protected
	 * @return string
	 */
	protected function getContentTable(EntryBlockPackage $block)
	{
		$section = blx()->content->getSectionById($block->sectionId);
		return EntryContentRecord::getTableNameForSection($section);
	}

	/**
	 * Returns all blocks by a section ID.
	 *
	 * @param int $sectionId
	 * @return array
	 */
	public function getBlocksBySectionId($sectionId)
	{
		$blockRecords = EntryBlockRecord::model()->ordered()->findAllByAttributes(array(
			'sectionId' => $sectionId
		));
		return $this->populateBlocks($blockRecords);
	}

	/**
	 * Returns the total number of blocks by a section ID.
	 *
	 * @param int $sectionId
	 * @return int
	 */
	public function getTotalBlocksBySectionId($sectionId)
	{
		return EntryBlockRecord::model()->countByAttributes(array(
			'sectionId' => $sectionId
		));
	}
	/* end BLOCKSPRO ONLY */
}
