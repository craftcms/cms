<?php
namespace Blocks;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m121018_094905_adding_link_criterias extends \CDbMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$blockSets = array(
			'assetblocks' => 'Asset',
			'entryblocks' => 'Entry',
			'globalblocks' => 'GlobalContent',
			'pageblocks' => 'Page',
			'userprofileblocks' => 'UserProfile',
		);

		// AttributeType::SortOrder is now a signed TinyInt
		$sortColumn = ModelHelper::normalizeAttributeConfig(AttributeType::SortOrder);
		$sortableTables = array_merge(array_keys($blockSets), array('assetsources', 'routes', 'widgets'));

		foreach ($sortableTables as $table)
		{
			blx()->db->createCommand()->alterColumn($table, 'sortOrder', $sortColumn);
		}

		// Add the linkcriteria table
		$linkCriteriaModel = new LinkCriteriaRecord('install');
		$linkCriteriaModel->createTable();
		$linkCriteriaModel->addForeignKeys();

		// Add/alter the links table columns
		blx()->db->createCommand()->addColumnAfter('links', 'criteriaId', array('column' => ColumnType::Int, 'null' => false), 'id');
		blx()->db->createCommand()->alterColumn('links', 'parentId', array('column' => ColumnType::Int, 'unsigned' => true), 'leftEntityId');
		blx()->db->createCommand()->alterColumn('links', 'childId', array('column' => ColumnType::Int, 'unsigned' => true, 'null' => false), 'rightEntityId');
		blx()->db->createCommand()->alterColumn('links', 'sortOrder', $sortColumn, 'leftSortOrder');
		blx()->db->createCommand()->addColumnAfter('links', 'rightSortOrder', $sortColumn, 'leftSortOrder');

		// Update all Links blocks
		foreach ($blockSets as $table => $entityType)
		{
			$select = 'id, handle, settings, sortOrder';

			$isSection = ($entityType == 'Entry' && Blocks::hasPackage(BlocksPackage::PublishPro));
			if ($isSection)
			{
				$select .= ', sectionId';
			}

			$linksBlockRows = blx()->db->createCommand()
				->select($select)
				->from($table)
				->where('type="Links"')
				->queryAll();

			foreach ($linksBlockRows as $blockRow)
			{
				// Add the row in the linkcriteria table
				$blockSettings = JsonHelper::decode($blockRow['settings']);

				$criteria = new LinkCriteriaRecord();
				$criteria->ltrHandle = $blockRow['handle'];
				$criteria->leftEntityType = $entityType;
				$criteria->rightEntityType = $blockSettings['type'];
				$criteria->leftSettings = ($isSection ? array('sectionId' => $blockRow['sectionId']) : null);
				$criteria->rightSettings = $blockSettings['linkTypeSettings'];

				$criteria->save();

				// Set the criteriaId in the settings
				$blockSettings['criteriaId'] = $criteria->id;
				blx()->db->createCommand()->update($table,
					array('settings' => JsonHelper::encode($blockSettings)),
					array('id' => $blockRow['id'])
				);

				// Update the rows in the links table
				blx()->db->createCommand()->update('links',
					array('criteriaId' => $criteria->id),
					array('parentType' => $entityType, 'blockId' => $blockRow['id'])
				);
			}
		}

		// Update the links table indexes
		blx()->db->createCommand()->dropIndex('links_blockId_parentType_parentId_childType_childId_unique_idx', 'links');
		blx()->db->createCommand()->createIndex('links_criteriaId_leftEntityId_rightEntityId_unique_idx', 'links', 'criteriaId,leftEntityId,rightEntityId', true);

		$linkModel = new LinkRecord();
		$linkModel->addForeignKeys();

		// Drop the old links table columns
		blx()->db->createCommand()->dropColumn('links', 'blockId');
		blx()->db->createCommand()->dropColumn('links', 'parentType');
		blx()->db->createCommand()->dropColumn('links', 'childType');

		return true;
	}
}
