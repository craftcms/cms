<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140204_000000_structures extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		if (!craft()->db->tableExists('structures'))
		{
			Craft::log('Creating the structures table.', LogLevel::Info, true);

			$this->createTable('structures', array(
				'maxLevels' => array('maxLength' => 6, 'decimals' => 0, 'column' => ColumnType::SmallInt, 'unsigned' => true),
			));
		}

		if (!craft()->db->tableExists('structureelements'))
		{
			Craft::log('Creating the structureelements table.', LogLevel::Info, true);

			$this->createTable('structureelements', array(
				'structureId' => array('column' => ColumnType::Int, 'null' => false),
				'elementId'   => array('column' => ColumnType::Int),
				'root'        => array('decimals' => 0, 'column' => ColumnType::Int,      'unsigned' => true, 'length' => 10),
				'lft'         => array('decimals' => 0, 'column' => ColumnType::Int,      'unsigned' => true, 'length' => 10, 'null' => false),
				'rgt'         => array('decimals' => 0, 'column' => ColumnType::Int,      'unsigned' => true, 'length' => 10, 'null' => false),
				'level'       => array('decimals' => 0, 'column' => ColumnType::SmallInt, 'unsigned' => true, 'length' => 6,  'null' => false),
			));

			$this->createIndex('structureelements', 'structureId,elementId', true);
			$this->createIndex('structureelements', 'root');
			$this->createIndex('structureelements', 'lft');
			$this->createIndex('structureelements', 'rgt');
			$this->createIndex('structureelements', 'level');
			$this->addForeignKey('structureelements', 'structureId', 'structures', 'id', 'CASCADE', null);
			$this->addForeignKey('structureelements', 'elementId', 'elements', 'id', 'CASCADE', null);
		}

		if (!craft()->db->columnExists('sections', 'structureId'))
		{
			Craft::log('Adding the sections.structureId column.', LogLevel::Info, true);

			$this->addColumnAfter('sections', 'structureId', array('column' => ColumnType::Int), 'id');
			$this->addForeignKey('sections', 'structureId', 'structures', 'id', 'SET NULL', null);
		}

		if (craft()->db->columnExists('sections', 'maxDepth'))
		{
			Craft::log('Creating structures for existing sections.', LogLevel::Info, true);

			$structureSections = craft()->db->createCommand()
				->select('id, structureId, maxDepth')
				->from('sections')
				->where('type="structure"')
				->queryAll();

			$structuresBySectionId = array();
			$structureRootsBySectionId = array();

			foreach ($structureSections as $section)
			{
				if ($section['structureId'])
				{
					continue;
				}

				$this->insert('structures', array(
					'maxLevels' => $section['maxDepth']
				));

				$structureId = craft()->db->getLastInsertID();

				$this->update('sections', array(
					'structureId' => $structureId
				), array(
					'id' => $section['id']
				));

				$oldRoot = craft()->db->createCommand()
					->select('id, lft, rgt')
					->from('entries')
					->where(array(
						'sectionId' => $section['id'],
						'depth'     => '0'
					))
					->queryRow();

				$this->insert('structureelements', array(
					'structureId' => $structureId,
					'lft'         => $oldRoot['lft'],
					'rgt'         => $oldRoot['rgt'],
					'level'       => '0'
				));

				$newRootId = craft()->db->getLastInsertID();

				$this->update('structureelements', array(
					'root' => $newRootId
				), array(
					'id' => $newRootId
				));

				$structuresBySectionId[$section['id']] = array(
					'id' => $structureId,
					'oldRootId' => $oldRoot['id'],
					'newRootId' => $newRootId
				);
			}

			if ($structuresBySectionId)
			{
				Craft::log('Moving entry hierarchy into the structureelements table.', LogLevel::Info, true);

				$entries = craft()->db->createCommand()
					->select('id, sectionId, root, lft, rgt, depth')
					->from('entries')
					->where(array('and',
						array('in', 'sectionId', array_keys($structuresBySectionId)),
						'depth != "0"'
					))
					->queryAll();

				foreach ($entries as $entry)
				{
					craft()->config->maxPowerCaptain();

					$this->insert('structureelements', array(
						'structureId' => $structuresBySectionId[$entry['sectionId']]['id'],
						'elementId'   => $entry['id'],
						'root'        => $structuresBySectionId[$entry['sectionId']]['newRootId'],
						'lft'         => $entry['lft'],
						'rgt'         => $entry['rgt'],
						'level'       => $entry['depth']
					));
				}

				Craft::log('Deleting the old root elements.', LogLevel::Info, true);

				foreach ($structuresBySectionId as $structure)
				{
					$this->delete('elements', array(
						'id' => $structure['oldRootId']
					));
				}
			}

			Craft::log('Dropping the sections.maxDepth column.', LogLevel::Info, true);
			$this->dropColumn('sections', 'maxDepth');
		}

		if (craft()->db->columnExists('entries', 'root'))
		{
			Craft::log('Dropping the entries.root column.', LogLevel::Info, true);
			$this->dropIndex('entries', 'root');
			$this->dropColumn('entries', 'root');
		}

		if (craft()->db->columnExists('entries', 'lft'))
		{
			Craft::log('Dropping the entries.lft column.', LogLevel::Info, true);
			$this->dropIndex('entries', 'lft');
			$this->dropColumn('entries', 'lft');
		}

		if (craft()->db->columnExists('entries', 'rgt'))
		{
			Craft::log('Dropping the entries.rgt column.', LogLevel::Info, true);
			$this->dropIndex('entries', 'rgt');
			$this->dropColumn('entries', 'rgt');
		}

		if (craft()->db->columnExists('entries', 'depth'))
		{
			Craft::log('Dropping the entries.depth column.', LogLevel::Info, true);
			$this->dropIndex('entries', 'depth');
			$this->dropColumn('entries', 'depth');
		}

		return true;
	}
}
