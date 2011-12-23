<?php

class ContentService extends CApplicationComponent implements IContentService
{
	/*
	 * Entries
	 */
	public function getEntryById($entryId)
	{
		$entry = Entries::model()->findByAttributes(array(
			'id' => $entryId,
		));

		return $entry;
	}

	public function getEntriesBySectionId($sectionId)
	{
		$entries = Entries::model()->findAllByAttributes(array(
			'section_id' => $sectionId,
		));

		return $entries;
	}

	public function getEntryTitleByLanguageCode($entryId, $languageCode)
	{
		$entryTitle = EntryTitles::model()->findByAttributes(
			array(
				'entry_id' => $entryId,
				'language_code' => $languageCode),
			array(
				'select' => 'title'));

		return $entryTitle;
	}

	public function getAllEntriesBySiteId($siteId)
	{
		$entries = Blocks::app()->db->createCommand()
			->select('e.*')
			->from('{{sections}} s')
			->join('{{entries}} e', 's.id = e.section_id')
			->where('s.site_id=:siteId', array(':siteId' => $siteId))
			->queryAll();

		return $entries;
	}

	public function doesEntryHaveSubEntries($entryId)
	{
		$exists = Entries::model()->exists(
			'parent_id=:parentId',
			array(':parentId' => $entryId)
		);

		return $exists;
	}

	public function getEntryVersionsByEntryId($entryId)
	{
		$versions = EntryVersions::model()->findAllByAttributes(array(
			'entry_id' => $entryId,
		));

		return $versions;
	}

	public function getEntryVersionById($versionId)
	{
		$version = EntryVersions::model()->findByAttributes(array(
			'id' => $versionId,
		));

		return $version;
	}

	/*
	 * Sections
	 */
	public function getSectionById($sectionId)
	{
		$section = Sections::model()->findByAttributes(array(
			'id' => $sectionId,
		));

		return $section;
	}

	public function doesSectionHaveSubSections($sectionId)
	{
		$exists = Sections::model()->exists(
			'parent_id=:parentId',
			array(':parentId' => $sectionId)
		);

		return $exists;
	}

	public function getSectionBySiteIdHandle($siteId, $handle)
	{
		$section = Sections::model()->findByAttributes(array(
			'handle' => $handle,
			'site_id' => $siteId,
		));

		return $section;
	}

	public function getSectionsBySiteIdHandles($siteId, $handles)
	{
		$sections = Sections::model()->findAllByAttributes(array(
			'handle' => $handles,
			'site_id' => $siteId,
		));

		return $sections;
	}

	public function getAllSectionsBySiteId($siteId)
	{
		$sections = Sections::model()->findAllByAttributes(array(
			'site_id' => $siteId,
		));

		return $sections;
	}

	public function createSection($sectionHandle, $siteHandle, $label, $urlFormat = null, $maxEntries = null, $template = null, $sortable = false, $parentId = null)
	{
		$connection = Blocks::app()->db;
		$dbName = Blocks::app()->config->getDatabaseName();
		$site = Blocks::app()->site->getSiteByHandle($siteHandle);

		$transaction = $connection->beginTransaction();
		try
		{
			$tableName = $this->_getEntryDataTableName($site->handle, $sectionHandle);

			// drop it if it exists
			if ($connection->schema->getTable('{{'.$tableName.'}}') !== null)
				$connection->createCommand()->dropTable('{{'.$tableName.'}}');

			// create dynamic data table
			$connection->createCommand()->createTable('{{'.$tableName.'}}',
				array('id'              => DatabaseColumnType::PK,
					  'entry_id'        => DatabaseColumnType::Integer.' NOT NULL',
					  'version_id'      => DatabaseColumnType::Integer.' NOT NULL',
					  'date_created'    => DatabaseColumnType::Integer,
					  'date_updated'    => DatabaseColumnType::Integer,
					  'uid'             => DatabaseColumnType::String
				));

			$entriesFKName = strtolower($tableName.'_entries_fk');
			$connection->createCommand()->addForeignKey(
				$entriesFKName, '{{'.$tableName.'}}', 'entry_id', '{{entries}}', 'id', 'NO ACTION', 'NO ACTION'
			);

			$entryVersionsFKName = strtolower($tableName.'_entryversions_fk');
			$connection->createCommand()->addForeignKey(
				$entryVersionsFKName, '{{'.$tableName.'}}', 'version_id', '{{entryversions}}', 'id', 'NO ACTION', 'NO ACTION'
			);

			DatabaseHelper::createInsertAuditTrigger($dbName, $tableName);
			DatabaseHelper::createUpdateAuditTrigger($dbName, $tableName);

			// check result.
			$section = new Sections();
			$section->site_id = $site->id;

			if ($parentId !== null)
				$section->parent_id = $parentId;

			$section->label = $label;
			$section->sortable = ($sortable == false ? 0 : 1);
			$section->handle = $sectionHandle;

			if ($urlFormat !== null)
				$section->url_format = $urlFormat;

			if ($maxEntries !== null)
				$section->max_entries = $maxEntries;

			if ($template !== null)
				$section->template = $template;

			$section->save();

			$transaction->commit();
			return $section;

		}
		catch (Exception $e)
		{
			$transaction->rollBack();
			throw new BlocksException($e->getMessage());
		}
	}

	/*
	 * Blocks
	 */

	public function createBlock($blockHandle, $sectionHandle, $siteHandle, $label, $type, $sortOrder, $blockDataType = DatabaseColumnType::Text, $instructions = null, $required = false)
	{
		$connection = Blocks::app()->db;
		$site = Blocks::app()->site->getSiteByHandle($siteHandle);
		$section = $this->getSectionBySiteIdHandle($site->id, $sectionHandle);

		$transaction = $connection->beginTransaction();
		try
		{
			$tableName = $this->_getEntryDataTableName($site->handle, $sectionHandle);
			$lastBlockColumnName = $this->_getLastBlockColumnName($tableName);
			Blocks::app()->db->createCommand()->addColumnAfter(
				'{{'.$tableName.'}}',
				'block_'.$blockHandle,
				$blockDataType,
				$lastBlockColumnName
			);

			// add to entry block row to table.
			$block = new EntryBlocks();
			$block->section_id = $section->id;
			$block->handle = $blockHandle;
			$block->label = $label;
			$block->type = $type;
			$block->sort_order = $sortOrder;

			if ($instructions !== null)
				$block->instructions = $instructions;

			$block->required = ($required == false ? 0 : 1);
			$block->save();
			$transaction->commit();

			return $block;

		}
		catch (Exception $e)
		{
			$transaction->rollBack();
			throw new BlocksException($e->getMessage());
		}

		return false;
	}

	public function getBlocksBySectionId($sectionId)
	{
		$sections = EntryBlocks::model()->findAllByAttributes(array(
			'section_id' => $sectionId,
		));

		return $sections;
	}

	public function getBlocksByEntryId($entryId)
	{
		$blocks = Blocks::app()->db->createCommand()
			->select('eb.*')
			->from('{{entryblocks}} eb')
			->join('{{sections}} s', 's.id = eb.section_id')
			->join('{{entries}} e', 's.id = e.section_id')
			->where('e.id=:entryId', array(':entryId' => $entryId))
			->queryAll();

		return $blocks;
	}

	public function getBlockByEntryIdHandle($entryId, $handle)
	{
		$blocks = Blocks::app()->db->createCommand()
			->select('eb.*')
			->from('{{entryblocks}} eb')
			->join('{{sections}} s', 's.id = eb.section_id')
			->join('{{entries}} e', 's.id = e.section_id')
			->where('e.id=:entryId AND eb.handle=:handle', array(':entryId' => $entryId, ':handle' => $handle))
			->queryAll();

		return $blocks;
	}

	private function _getEntryDataTableName($siteHandle, $sectionHandle)
	{
		return strtolower('entrydata_'.$siteHandle.'_'.$sectionHandle);
	}

	private function _getLastBlockColumnName($table)
	{
		Blocks::app()->db->schema->refresh();
		$dataTable = Blocks::app()->db->schema->getTable('{{'.$table.'}}');

		$columnNames = $dataTable->getColumnNames();

		$lastBlockMatch = null;
		foreach ($columnNames as $columnName)
		{
			if (strpos($columnName, 'block_') !== false)
				$lastBlockMatch = $columnName;
		}

		if ($lastBlockMatch == null)
			$lastBlockMatch = 'version_id';

		return $lastBlockMatch;
	}
}
