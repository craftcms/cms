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
	public function populateBlockPackage($attributes)
	{
		$blockPackage = parent::populateBlockPackage($attributes);
		$blockPackage->sectionId = $attributes['sectionId'];
		return $blockPackage;
	}

	/**
	 * Populates a block record from a package.
	 *
	 * @access protected
	 * @param EntryBlockPackage $blockPackage
	 * @return EntryBlockRecord $blockRecord;
	 */
	protected function populateBlockRecord(EntryBlockPackage $blockPackage)
	{
		$blockRecord = parent::populateBlockRecord($blockPackage);
		$blockRecord->sectionId = $blockPackage->sectionId;
		return $blockRecord;
	}

	/**
	 * Returns the content table name.
	 *
	 * @param EntryBlockPackage $blockPackage
	 * @access protected
	 * @return string
	 */
	protected function getContentTable(EntryBlockPackage $blockPackage)
	{
		$section = blx()->content->getSectionById($blockPackage->sectionId);
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
		return $this->populateBlockPackages($blockRecords);
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
