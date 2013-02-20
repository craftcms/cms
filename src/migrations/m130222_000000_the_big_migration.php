<?php
namespace Blocks;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130222_000000_the_big_migration extends BaseMigration
{
	private $_primaryLocale;
	private $_foreignKeysByTable;
	private $_foreignKeysByRefTable;
	private $_fieldHandles;

	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// Set up _fieldHandles with the reserved words
		$this->_fieldHandles = array('id', 'dateCreated', 'dateUpdated', 'uid', 'author', 'authorId', 'entryTagEntries', 'type', 'postDate', 'expiryDate', 'enabled', 'archived', 'locale', 'title', 'uri', 'url');

		$this->_createAndPopulateLocalesTable();
		$this->_updateEntryRevisionTables();
		$this->_createContentTables();
		$this->_updateLinkTables();
		$this->_addUserPermissionTablesIfNecessary();
		$this->_migrateSectionEntries();
		$this->_findAllForeignKeys();
		$this->_migrateUsers();
		$this->_migrateAssets();
		$this->_migrateSingletons();
		$this->_migrateGlobals();
		$this->_cleanupLinkTable();
		$this->_updateEmailMessageTable();

		// Drop the authorId column from entries now that we're done tweaking the users PK
		$this->dropForeignKey('entries', 'authorId');
		$this->dropColumn('entries', 'authorId');

		return true;
	}

	/**
	 * Creates the locales table, populated with the site's old primary language,
	 * and deletes the old 'language' column + languag settings.
	 *
	 * @access private
	 */
	private function _createAndPopulateLocalesTable()
	{
		// Create the locales table
		$this->createTable('locales', array(
			'locale'    => array('column' => ColumnType::Locale, 'required' => true, 'primaryKey' => true),
			'sortOrder' => array('maxLength' => 4, 'column' => ColumnType::TinyInt, 'unsigned' => false),
		), null, false);

		// Get the locales
		//-----------------

		// Get the primary locale
		$this->_primaryLocale = blx()->db->createCommand()->select('language')->from('info')->queryScalar();

		$locales = array($this->_primaryLocale);

		if (Blocks::hasPackage(BlocksPackage::Language))
		{
			$storedLanguages = blx()->systemSettings->getSettings('languages');

			if ($storedLanguages)
			{
				$locales = array_merge($locales, $storedLanguages);
			}
		}

		// Fill up the locales table
		//-------------------------------

		$vals = array();

		foreach ($locales as $order => $locale)
		{
			$vals[] = array($locale, $order+1);
		}

		$this->insertAll('locales', array('locale', 'sortOrder'), $vals);

		// Cleanup
		//-------------------------------

		// Drop the language column from info
		$this->dropColumn('info', 'language');

		// Delete the Languages settings
		blx()->systemSettings->saveSettings('languages', null);
	}

	/**
	 * Points the entry revision tables to the new locales table.
	 *
	 * @access private
	 */
	private function _updateEntryRevisionTables()
	{
		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			$revisionTables = array('entrydrafts', 'entryversions');

			foreach ($revisionTables as $table)
			{
				$this->dropForeignKey($table, 'entryId');
				$this->dropIndex($table, 'entryId,language');
				$this->alterColumn($table, 'language', array('column' => ColumnType::Locale, 'required' => true), 'locale');
				$this->createIndex($table, 'entryId,locale');
				$this->addForeignKey($table, 'entryId', 'entries', 'id', 'CASCADE');
				$this->_addLocaleForeignKey($table);

				// Rename the 'blocks' revision data keys to 'fields'
				$revisions = blx()->db->createCommand()->select('id,data')->from($table)->queryAll();

				foreach ($revisions as $revision)
				{
					$data = JsonHelper::decode($revision['data']);

					if (isset($data['blocks']))
					{
						$data['fields'] = $data['blocks'];
						unset($data['blocks']);
					}
					else
					{
						$data['fields'] = array();
					}

					$this->update($table,
						array('data' => JsonHelper::encode($data)),
						array('id' => $revision['id'])
					);
				}
			}
		}
	}

	/**
	 * Creates all the new entry/content tables.
	 *
	 * @access private
	 */
	private function _createContentTables()
	{
		// Tweak the entries table
		$this->alterColumn('entries', 'authorId', array('column' => ColumnType::Int, 'required' => false));
		$this->alterColumn('entries', 'postDate', array('column' => ColumnType::DateTime, 'required' => true));
		$this->addColumnAfter('entries', 'type', array('maxLength' => 150, 'column' => ColumnType::Char, 'required' => true), 'authorId');
		$this->createIndex('entries', 'authorId');
		$this->createIndex('entries', 'type');
		$this->createIndex('entries', 'postDate');
		$this->createIndex('entries', 'expiryDate');
		$this->createIndex('entries', 'enabled');
		$this->createIndex('entries', 'archived');

		// Set all existing entries' type to 'SectionEntry'
		$this->update('entries', array('type' => 'SectionEntry'));

		// Rename the entrytitles table to entries_i18n
		$this->dropForeignKey('entrytitles', 'entryId');
		$this->dropIndex('entrytitles', 'title,entryId,language');
		$this->dropIndex('entrytitles', 'entryId,language', true);
		$this->renameTable('entrytitles', 'entries_i18n');
		$this->alterColumn('entries_i18n', 'language', array('column' => ColumnType::Locale, 'required' => true), 'locale');
		$this->addColumn('entries_i18n', 'uri', array());
		$this->createIndex('entries_i18n', 'entryId,locale', true);
		$this->createIndex('entries_i18n', 'uri,locale', true);
		$this->createIndex('entries_i18n', 'title', false);
		$this->addForeignKey('entries_i18n', 'entryId', 'entries', 'id', 'CASCADE');
		$this->_addLocaleForeignKey('entries_i18n');

		// Create the fieldgroups table
		$this->createTable('fieldgroups', array(
			'name' => array('maxLength' => 100, 'column' => ColumnType::Varchar, 'required' => true),
		));
		$this->createIndex('fieldgroups', 'name', true);

		// Create the fields table
		$this->createTable('fields', array(
			'groupId'      => array('column' => ColumnType::Int, 'required' => false),
			'name'         => array('maxLength' => 100, 'column' => ColumnType::Varchar, 'required' => true),
			'handle'       => array('maxLength' => 64, 'column' => ColumnType::Char, 'required' => true),
			'instructions' => array('column' => ColumnType::Text),
			'translatable' => array('maxLength' => 1, 'default' => false, 'required' => true, 'column' => ColumnType::TinyInt, 'unsigned' => true),
			'type'         => array('maxLength' => 150, 'column' => ColumnType::Char, 'required' => true),
			'settings'     => array('column' => ColumnType::Text),
		));
		$this->createIndex('fields', 'handle', true);
		$this->addForeignKey('fields', 'groupId', 'fieldgroups', 'id', 'CASCADE');

		// Create the fieldlayouts table
		$this->createTable('fieldlayouts', array(
			'type' => array('maxLength' => 150, 'column' => ColumnType::Char, 'required' => true),
		));
		$this->createIndex('fieldlayouts', 'type', false);

		// Create the fieldlayouttabs table
		$this->createTable('fieldlayouttabs', array(
			'layoutId'  => array('column' => ColumnType::Int, 'required' => true),
			'name'      => array('maxLength' => 100, 'column' => ColumnType::Varchar),
			'sortOrder' => array('maxLength' => 4, 'column' => ColumnType::TinyInt, 'unsigned' => false),
		));
		$this->createIndex('fieldlayouttabs', 'sortOrder', false);
		$this->addForeignKey('fieldlayouttabs', 'layoutId', 'fieldlayouts', 'id', 'CASCADE', null);

		// Create the fieldlayoutfields table
		$this->createTable('fieldlayoutfields', array(
			'layoutId'  => array('column' => ColumnType::Int, 'required' => true),
			'tabId'     => array('column' => ColumnType::Int, 'required' => false),
			'fieldId'   => array('column' => ColumnType::Int, 'required' => true),
			'required'  => array('maxLength' => 1, 'default' => false, 'required' => true, 'column' => ColumnType::TinyInt, 'unsigned' => true),
			'sortOrder' => array('maxLength' => 4, 'column' => ColumnType::TinyInt, 'unsigned' => false),
		));
		$this->createIndex('fieldlayoutfields', 'layoutId,fieldId', true);
		$this->createIndex('fieldlayoutfields', 'sortOrder', false);
		$this->addForeignKey('fieldlayoutfields', 'layoutId', 'fieldlayouts', 'id', 'CASCADE', null);
		$this->addForeignKey('fieldlayoutfields', 'tabId', 'fieldlayouttabs', 'id', 'CASCADE', null);
		$this->addForeignKey('fieldlayoutfields', 'fieldId', 'fields', 'id', 'CASCADE', null);

		if (!Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			// Rename the entrycontent table
			$this->dropForeignKey('entrycontent', 'entryId');
			$this->renameTable('entrycontent', 'entrycontent_old');
		}

		// Create the entrycontent table
		$this->createTable('entrycontent', array(
			'entryId' => array('column' => ColumnType::Int, 'required' => true),
			'locale'  => array('column' => ColumnType::Locale, 'required' => true),
		));
		$this->createIndex('entrycontent', 'entryId,locale', true);
		$this->addForeignKey('entrycontent', 'entryId', 'entries', 'id', 'CASCADE');
		$this->_addLocaleForeignKey('entrycontent');
	}

	/**
	 * Updates the link criteria table.
	 */
	private function _updateLinkTables()
	{
		$this->renameColumn('linkcriteria', 'leftEntityType', 'leftEntryType');
		$this->renameColumn('linkcriteria', 'rightEntityType', 'rightEntryType');

		// Update the entry type values
		$entryTypes = array(
			'Entry'       => 'SectionEntry',
			'Page'        => 'Singleton',
			'UserProfile' => 'User',
			'Global'      => 'Globals'
		);

		foreach ($entryTypes as $entityType => $entryType)
		{
			$this->update('linkcriteria', array('leftEntryType' => $entryType), array('leftEntryType' => $entityType));
			$this->update('linkcriteria', array('rightEntryType' => $entryType), array('rightEntryType' => $entityType));
		}

		// Update the links table
		$this->dropForeignKey('links', 'criteriaId');
		$this->dropIndex('links', 'criteriaId,leftEntityId,rightEntityId', true);
		$this->alterColumn('links', 'criteriaId', array('column' => ColumnType::Int, 'required' => true));
		$this->addColumnAfter('links', 'leftEntryId', array('column' => ColumnType::Int, 'required' => true), 'leftEntityId');
		$this->addColumnAfter('links', 'rightEntryId', array('column' => ColumnType::Int, 'required' => true), 'rightEntityId');

		// It's possible that there are duplicate links, since MySQL doesn't enforce unique constraints on NULL values
		$this->delete('links', array('and', 'leftEntityId IS NULL', 'rightEntityId IS NULL'));

		$leftNullLinks = blx()->db->createCommand()->select('id,criteriaId,rightEntityId')->from('links')->where('leftEntityId IS NULL')->queryAll();
		$rightNullLinks = blx()->db->createCommand()->select('id,criteriaId,leftEntityId')->from('links')->where('rightEntityId IS NULL')->queryAll();

		foreach ($leftNullLinks as $link)
		{
			$this->delete('links', array('and', 'criteriaId = :criteriaId', 'rightEntityId = :rightEntityId', 'id != :id'), array(
				':criteriaId'    => $link['criteriaId'],
				':rightEntityId' => $link['rightEntityId'],
				':id'            => $link['id']
			));
		}

		foreach ($rightNullLinks as $link)
		{
			$this->delete('links', array('and', 'criteriaId = :criteriaId', 'leftEntityId = :leftEntityId', 'id != :id'), array(
				':criteriaId'   => $link['criteriaId'],
				':leftEntityId' => $link['lefttEntityId'],
				':id'           => $link['id']
			));
		}
	}

	/**
	 * Adds the userpermission tables if necessary.
	 *
	 * @access private
	 */
	private function _addUserPermissionTablesIfNecessary()
	{
		if (!Blocks::hasPackage(BlocksPackage::Users))
		{
			// Create the usergroups table
			$this->createTable('usergroups', array(
				'name'   => array('maxLength' => 100, 'column' => ColumnType::Varchar, 'required' => true),
				'handle' => array('maxLength' => 255, 'column' => ColumnType::Char, 'required' => true),
			));

			// Create the usergroups_users table
			$this->createTable('usergroups_users', array(
				'groupId' => array('column' => ColumnType::Int, 'required' => true),
				'userId'  => array('column' => ColumnType::Int, 'required' => true),
			));
			$this->createIndex('usergroups_users', 'groupId,userId', true);
			$this->addForeignKey('usergroups_users', 'groupId', 'usergroups', 'id', 'CASCADE', null);
			$this->addForeignKey('usergroups_users', 'userId', 'users', 'id', 'CASCADE', null);

			// Create the userpermissions table
			$this->createTable('userpermissions', array(
				'name' => array('maxLength' => 100, 'column' => ColumnType::Varchar, 'required' => true),
			));
			$this->createIndex('userpermissions', 'name', true);

			// Create the userpermissions_users table
			$this->createTable('userpermissions_users', array(
				'permissionId' => array('column' => ColumnType::Int, 'required' => true),
				'userId'       => array('column' => ColumnType::Int, 'required' => true),
			));
			$this->createIndex('userpermissions_users', 'permissionId,userId', true);
			$this->addForeignKey('userpermissions_users', 'permissionId', 'userpermissions', 'id', 'CASCADE', null);
			$this->addForeignKey('userpermissions_users', 'userId', 'users', 'id', 'CASCADE', null);

			// Create the blx_userpermissions_usergroups table
			$this->createTable('userpermissions_usergroups', array(
				'permissionId' => array('column' => ColumnType::Int, 'required' => true),
				'groupId'      => array('column' => ColumnType::Int, 'required' => true),
			));
			$this->createIndex('userpermissions_usergroups', 'permissionId,groupId', true);
			$this->addForeignKey('userpermissions_usergroups', 'permissionId', 'userpermissions', 'id', 'CASCADE', null);
			$this->addForeignKey('userpermissions_usergroups', 'groupId', 'usergroups', 'id', 'CASCADE', null);
		}
	}

	/**
	 * Migrate section entries
	 *
	 * @access private
	 */
	private function _migrateSectionEntries()
	{
		// Create the new section entry tables
		//------------------------------------

		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			// Add the fieldLayout column to sections
			$this->addColumn('sections', 'fieldLayoutId', array('maxLength' => 11, 'decimals' => 0, 'unsigned' => false, 'length' => 10, 'column' => ColumnType::Int));
		}
		else
		{
			// Create the sections table
			$this->createTable('sections', array(
				'name'          => array('maxLength' => 100, 'column' => ColumnType::Varchar, 'required' => true),
				'handle'        => array('maxLength' => 45, 'column' => ColumnType::Char, 'required' => true),
				'titleLabel'    => array('required' => true, 'default' => 'Title'),
				'hasUrls'       => array('maxLength' => 1, 'default' => true, 'required' => true, 'column' => ColumnType::TinyInt, 'unsigned' => true),
				'template'      => array('maxLength' => 500, 'column' => ColumnType::Varchar),
				'fieldLayoutId' => array('maxLength' => 11, 'decimals' => 0, 'unsigned' => false, 'length' => 10, 'column' => ColumnType::Int),
			));
			$this->createIndex('sections', 'handle', true);

			// Add the blog section
			$this->insert('sections', array(
				'name'       => 'Blog',
				'handle'     => 'blog',
				'titleLabel' => 'title',
				'hasUrls'    => 1,
			));

			// Get its ID
			$blogSectionId = blx()->db->getLastInsertID();

			// Update permissions
			$entryPermissions = array('editentries', 'createentries', 'editpeerentries', 'editpeerentrydrafts', 'publishpeerentrydrafts', 'deletepeerentrydrafts', 'publishentries');
			$blogPermissions = blx()->db->createCommand()
				->select('id,name')
				->from('userpermissions')
				->where(array('in', 'name', $entryPermissions))
				->queryAll();

			foreach ($blogPermissions as $permission)
			{
				$newName = $permission['name'].'insection'.$blogSectionId;
				blx()->db->createCommand()->update('userpermissions', array('name' => $newName), array('id' => $permission['id']));
			}
		}

		// Add the fieldLayoutId FK
		$this->addForeignKey('sections', 'fieldLayoutId', 'fieldlayouts', 'id', 'SET NULL', null);

		// Create the sectionlocales table
		$this->createTable('sectionlocales', array(
			'sectionId' => array('column' => ColumnType::Int, 'required' => true),
			'locale'    => array('column' => ColumnType::Locale, 'required' => true),
			'urlFormat' => array(),
		));
		$this->createIndex('sectionlocales', 'sectionId,locale', true);
		$this->addForeignKey('sectionlocales', 'sectionId', 'sections', 'id', 'CASCADE');
		$this->addForeignKey('sectionlocales', 'locale', 'locales', 'locale', 'CASCADE', 'CASCADE');

		// Create the sectionentries table
		$this->createTable('sectionentries', array(
			'sectionId' => array('column' => ColumnType::Int, 'required' => true),
			'authorId'  => array('column' => ColumnType::Int),
		));
		$this->createIndex('sectionentries', 'sectionId');
		$this->addForeignKey('sectionentries', 'id', 'entries', 'id', 'CASCADE');
		$this->addForeignKey('sectionentries', 'sectionId', 'sections', 'id', 'CASCADE');
		$this->addForeignKey('sectionentries', 'authorId', 'users', 'id', 'SET NULL');

		// Create the sectionentries_i18n table
		$this->createTable('sectionentries_i18n', array(
			'entryId'   => array('column' => ColumnType::Int, 'required' => true),
			'sectionId' => array('column' => ColumnType::Int, 'required' => true),
			'locale'    => array('column' => ColumnType::Locale, 'required' => true),
			'slug'      => array('maxLength' => 50, 'column' => ColumnType::Char, 'required' => true),
		));
		$this->createIndex('sectionentries_i18n', 'entryId,locale', true);
		$this->createIndex('sectionentries_i18n', 'slug,sectionId,locale', true);
		$this->addForeignKey('sectionentries_i18n', 'entryId', 'entries', 'id', 'CASCADE');
		$this->addForeignKey('sectionentries_i18n', 'sectionId', 'sections', 'id', 'CASCADE');
		$this->_addLocaleForeignKey('sectionentries_i18n');

		// Populate the sectionlocales table
		//----------------------------------

		$sectionLocaleData = array();

		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			$sections = blx()->db->createCommand()->select('id,name,handle,hasUrls,urlFormat')->from('sections')->queryAll();

			foreach ($sections as $section)
			{
				$sectionLocaleData[] = array($section['id'], $this->_primaryLocale, $section['urlFormat']);
			}

			// Drop the old urlFormat column
			$this->dropColumn('sections', 'urlFormat');
		}
		else
		{
			$sectionLocaleData[] = array($blogSectionId, $this->_primaryLocale, 'blog/{slug}');
		}

		$this->insertAll('sectionlocales', array('sectionId', 'locale', 'urlFormat'), $sectionLocaleData);

		// Migrate the section entry content out of the entries table
		// and into entries_i18n, sectionentries, and sectionentries_i18n
		//---------------------------------------------------------------------

		// Get all of the data to be
		$columns = 'id,authorId,slug,uri' . (Blocks::hasPackage(BlocksPackage::PublishPro) ? ',sectionId' : '');
		$entries = blx()->db->createCommand()->select($columns)->from('entries')->queryAll();

		$sectionEntryData = array();
		$sectionEntryLocaleData = array();

		foreach ($entries as $entry)
		{
			$this->update('entries_i18n', array('uri' => $entry['uri']), array('entryId' => $entry['id']));

			if (Blocks::hasPackage(BlocksPackage::PublishPro))
			{
				$sectionId = $entry['sectionId'];
			}
			else
			{
				$sectionId = $blogSectionId;
			}

			$sectionEntryData[] = array($entry['id'], $sectionId, $entry['authorId']);
			$sectionEntryLocaleData[] = array($entry['id'], $sectionId, $this->_primaryLocale, $entry['slug']);
		}

		// Add the new rows to sectionentries and sectionentries_i18n
		$this->insertAll('sectionentries', array('id', 'sectionId', 'authorId'), $sectionEntryData);
		$this->insertAll('sectionentries_i18n', array('entryId', 'sectionId', 'locale', 'slug'), $sectionEntryLocaleData);

		// Cleanup
		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			// Drop the sectionId column from entries
			$this->dropForeignKey('entries', 'sectionId');
			$this->dropIndex('entries', 'slug,sectionId', true);
			$this->dropColumn('entries', 'sectionId');
		}
		else
		{
			$this->dropIndex('entries', 'slug', true);
		}

		// Drop the uri and slug columns
		$this->dropIndex('entries', 'uri', true);
		$this->dropColumn('entries', 'slug');
		$this->dropColumn('entries', 'uri');

		// Migrate the content into entrycontent
		//--------------------------------------

		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			foreach ($sections as $section)
			{
				$fields = $this->_migrateFields($section['name'], 'entryblocks', array('sectionId' => $section['id']), 'entrycontent_'.$section['handle'], 'entryId', true);
				$fieldLayoutId = $this->_saveFieldLayout('SectionEntry', $fields, 'Content');
				$this->update('sections', array('fieldLayoutId' => $fieldLayoutId), array('id' => $section['id']));
			}
		}
		else
		{
			$fields = $this->_migrateFields('Blog', 'entryblocks', array(), 'entrycontent_old', 'entryId', true);
			$fieldLayoutId = $this->_saveFieldLayout('SectionEntry', $fields, 'Content');
			$this->update('sections', array('fieldLayoutId' => $fieldLayoutId), array('id' => $blogSectionId));
		}

		// Delete the old entryblocks table
		$this->dropTable('entryblocks');

		// Update the Links table
		//-----------------------

		$leftLinkCriteriaIds = blx()->db->createCommand()->select('id')->where(array('leftEntryType' => 'SectionEntry'))->from('linkcriteria')->queryColumn();
		$rightLinkCriteriaIds = blx()->db->createCommand()->select('id')->where(array('rightEntryType' => 'SectionEntry'))->from('linkcriteria')->queryColumn();

		if ($leftLinkCriteriaIds)
		{
			$this->execute('UPDATE {{links}} SET `leftEntryId` = `leftEntityId` WHERE `criteriaId` IN ('.implode(',', $leftLinkCriteriaIds).')');
		}

		if ($rightLinkCriteriaIds)
		{
			$this->execute('UPDATE {{links}} SET `rightEntryId` = `rightEntityId` WHERE `criteriaId` IN ('.implode(',', $rightLinkCriteriaIds).')');
		}
	}

	/**
	 * Saves a field layout in a given table.
	 *
	 * @access private
	 * @param string $type
	 * @param array $fields
	 * @param string|null $tabName
	 * @return int|null The field layout ID
	 */
	private function _saveFieldLayout($type, $fields, $tabName = null)
	{
		if (!$fields)
		{
			return;
		}

		// Create the new field layout
		$this->insert('fieldlayouts', array('type' => $type));
		$layoutId = blx()->db->getLastInsertID();

		// Do we need to create a tab?
		if ($tabName)
		{
			$this->insert('fieldlayouttabs', array('layoutId' => $layoutId, 'name' => $tabName, 'sortOrder' => 1));
			$tabId = blx()->db->getLastInsertID();
		}
		else
		{
			$tabId = null;
		}

		// Add each of the fields
		$fieldData = array();

		foreach ($fields as $i => $field)
		{
			$fieldData[] = array($layoutId, $tabId, $field['newId'], $field['required'], $i+1);
		}

		$this->insertAll('fieldlayoutfields', array('layoutId', 'tabId', 'fieldId', 'required', 'sortOrder'), $fieldData);

		return $layoutId;
	}

	/**
	 * Migrates users.
	 *
	 * @access private
	 */
	private function _migrateUsers()
	{
		// Entryify users
		$this->_entryify('users', 'User');

		// Rename the language column
		$this->alterColumn('users', 'language', array('column' => ColumnType::Locale), 'preferredLocale');
		$this->_addLocaleForeignKey('users', 'preferredLocale', false);

		// Migrate the content into entrycontent
		//--------------------------------------

		if (Blocks::hasPackage(BlocksPackage::Users))
		{
			// Migrate the fields and content
			$fields = $this->_migrateFields('Users', 'userprofileblocks', array(), 'userprofiles', 'userId', false);
			$this->_saveFieldLayout('User', $fields);

			// Delete the old userprofileblocks table
			$this->dropTable('userprofileblocks');
		}
	}

	/**
	 * Migrates assets.
	 *
	 * @access private
	 */
	private function _migrateAssets()
	{
		// Entryify assets
		$this->_entryify('assetfiles', 'Asset');

		// Migrate the content into entrycontent
		//--------------------------------------

		// Migrate the fields and content
		$fields = $this->_migrateFields('Assets', 'assetblocks', array(), 'assetcontent', 'fileId', false);
		$this->_saveFieldLayout('Asset', $fields);

		// Delete the assetblocks table
		$this->dropTable('assetblocks');
	}

	/**
	 * Migrates singletons.
	 *
	 * @access private
	 */
	private function _migrateSingletons()
	{
		// Entryify singletons
		$this->_entryify('pages', 'Singleton');

		// Modify the columns
		$this->renameColumn('pages', 'title', 'name');
		$this->addColumn('pages', 'fieldLayoutId', array('maxLength' => 11, 'decimals' => 0, 'unsigned' => false, 'length' => 10, 'column' => ColumnType::Int));

		// Create the singletonlocales table
		$this->createTable('singletonlocales', array(
			'singletonId' => array('column' => ColumnType::Int, 'required' => true),
			'locale'      => array('column' => ColumnType::Locale, 'required' => true),
		));
		$this->createIndex('singletonlocales', 'singletonId,locale', true);
		$this->addForeignKey('singletonlocales', 'singletonId', 'pages', 'id', 'CASCADE');
		$this->addForeignKey('singletonlocales', 'locale', 'locales', 'locale', 'CASCADE', 'CASCADE');

		// Migrate the content into entrycontent
		//--------------------------------------

		// Rename the old pageblocks.pageId column to singletonId
		$this->dropForeignKey('pageblocks', 'pageId');
		$this->renameColumn('pageblocks', 'pageId', 'singletonId');

		// Get all the singletons
		$singletons = blx()->db->createCommand()->select('id,name,uri')->from('pages')->queryAll();

		$i18nData = array();
		$localesData = array();

		foreach ($singletons as $singleton)
		{
			// Migrate the fields
			$fields = $this->_migrateFields('Singletons - '.$singleton['name'], 'pageblocks', array('singletonId' => $singleton['id']));
			$fieldLayoutId = $this->_saveFieldLayout('Singleton', $fields, 'Content');
			$this->update('pages', array('fieldLayoutId' => $fieldLayoutId), array('id' => $singleton['id']));

			// Queue up the new data
			$i18nData[] = array($singleton['id'], $this->_primaryLocale, $singleton['name'], $singleton['uri']);
			$localesData[] = array($singleton['id'], $this->_primaryLocale);

			// Migrate the content
			$oldContent = blx()->db->createCommand()->select('content')->from('pagecontent')->where(array('pageId' => $singleton['id'], 'language' => $this->_primaryLocale))->queryScalar();
			$newContent = array('entryId' => $singleton['id'], 'locale' => $this->_primaryLocale);

			if ($oldContent)
			{
				$oldContent = JsonHelper::decode($oldContent);

				foreach ($fields as $field)
				{
					if (isset($oldContent[$field['id']]) && $field['hasContentColumn'])
					{
						$newContent[$field['handle']] = $oldContent[$field['id']];
					}
				}
			}

			$this->insert('entrycontent', $newContent);
		}

		// Batch-insert the new singleton data
		$this->insertAll('entries_i18n', array('entryId', 'locale', 'title', 'uri'), $i18nData);
		$this->insertAll('singletonlocales', array('singletonId', 'locale'), $localesData);

		// Cleanup
		$this->dropTable('pagecontent');
		$this->dropTable('pageblocks');
		$this->dropIndex('pages', 'uri', true);
		$this->dropColumn('pages', 'uri');

		// Rename the pages table to singletons
		$this->dropForeignKey('pages', 'id');
		$this->renameTable('pages', 'singletons');
		$this->addForeignKey('singletons', 'id', 'entries', 'id', 'CASCADE');
		$this->addForeignKey('singletons', 'fieldLayoutId', 'fieldlayouts', 'id', 'SET NULL', null);
	}

	/**
	 * Migrates globals.
	 *
	 * @access private
	 */
	private function _migrateGlobals()
	{
		// Migrate the content into entrycontent
		//--------------------------------------

		// Migrate the fields
		$fields = $this->_migrateFields('Globals', 'globalblocks');
		$this->_saveFieldLayout('Globals', $fields);

		$oldContent = blx()->db->createCommand()->from('globalcontent')->where(array('language' => $this->_primaryLocale))->queryRow();

		if ($oldContent)
		{
			// Create the new Globals entry
			$this->insert('entries', array(
				'type'     => 'Globals',
				'postDate' => $oldContent['dateCreated'],
				'enabled'  => 1
			));

			// Get the new entry ID
			$entryId = blx()->db->getLastInsertID();

			// Add a row to entries_i18n
			$this->insert('entries_i18n', array(
				'entryId' => $entryId,
				'locale'  => $this->_primaryLocale,
				'title'   => 'Globals'
			));

			// Migrate the content
			$newContent = array(
				'entryId' => $entryId,
				'locale'  => $this->_primaryLocale
			);

			foreach ($fields as $field)
			{
				if (isset($oldContent[$field['oldHandle']]) && $field['hasContentColumn'])
				{
					$newContent[$field['handle']] = $oldContent[$field['oldHandle']];
				}
			}

			$this->insert('entrycontent', $newContent);

			// Update the Links table
			//-----------------------

			$linkCriteriaIds = blx()->db->createCommand()->select('id')->where(array('leftEntryType' => 'Globals'))->from('linkcriteria')->queryColumn();

			if ($linkCriteriaIds)
			{
				$this->update('links', array('leftEntryId' => $entryId), array('in', 'criteriaId', $linkCriteriaIds));
			}
		}

		// Cleanup
		$this->dropTable('globalblocks');
		$this->dropTable('globalcontent');
	}

	/**
	 * Drop the old columns from the links table.
	 *
	 * @access private
	 */
	private function _cleanupLinkTable()
	{
		$this->createIndex('links', 'criteriaId,leftEntryId,rightEntryId', true);
		$this->addForeignKey('links', 'criteriaId', 'linkcriteria', 'id', 'CASCADE');
		$this->addForeignKey('links', 'leftEntryId', 'entries', 'id', 'CASCADE');
		$this->addForeignKey('links', 'rightEntryId', 'entries', 'id', 'CASCADE');

		$this->dropColumn('links', 'leftEntityId');
		$this->dropColumn('links', 'rightEntityId');
	}

	/**
	 * Updates the emailmessages table
	 */
	private function _updateEmailMessageTable()
	{
		if (Blocks::hasPackage(BlocksPackage::Rebrand))
		{
			// Rename the language column in emailmessages
			$this->dropIndex('emailmessages', 'key,language', true);
			$this->alterColumn('emailmessages', 'language', array('column' => ColumnType::Locale, 'required' => true), 'locale');
			$this->createIndex('emailmessages', 'key,locale', true);
			$this->_addLocaleForeignKey('emailmessages');
		}
	}

	/**
	 * Finds all the foreign keys.
	 *
	 * @access private
	 */
	private function _findAllForeignKeys()
	{
		$this->_foreignKeysByRefTable = array();

		$tablePrefixLength = strlen(blx()->db->tablePrefix);
		$tables = blx()->db->getSchema()->getTableNames();

		$refActions = 'RESTRICT|CASCADE|NO ACTION|SET DEFAULT|SET NULL';

		foreach ($tables as $prefixedTable)
		{
			// Get the CREATE TABLE sql
			$query = blx()->db->createCommand()->setText("SHOW CREATE TABLE `{$prefixedTable}`")->queryRow();
			$createTableSql = $query['Create Table'];

			// Get the table name without the table prefix
			$table = substr($prefixedTable, $tablePrefixLength);

			if (preg_match_all("/CONSTRAINT `(\w+)` FOREIGN KEY \(`([\w`,]+)`\) REFERENCES `(\w+)` \(`([\w`,]+)`\)( ON DELETE ({$refActions}))?( ON UPDATE ({$refActions}))?/", $createTableSql, $matches, PREG_SET_ORDER))
			{
				$newForeignKeyNames = array();

				foreach ($matches as $match)
				{
					$fk = (object) array(
						'table'       => $table,
						'name'        => $match[1],
						'columns'     => explode('`,`', $match[2]),
						'columnTypes' => array(),
						'refTable'    => substr($match[3], $tablePrefixLength),
						'refColumns'  => explode('`,`', $match[4]),
						'onDelete'    => (!empty($match[6]) ? $match[6] : null),
						'onUpdate'    => (!empty($match[8]) ? $match[8] : null),
					);

					// Get the involved column types
					foreach ($fk->columns as $column)
					{
						preg_match("/^\s*`{$column}` (.+),\s*$/m", $createTableSql, $columnTypeMatch);
						$fk->columnTypes[] = $columnTypeMatch[1];
					}

					$this->_foreignKeysByTable[$fk->table][] = $fk;
					$this->_foreignKeysByRefTable[$fk->refTable][] = $fk;
				}
			}
		}
	}

	/**
	 * Returns all of the foreign keys to a certain table/column.
	 *
	 * @access private
	 * @param string $table
	 * @param string $column
	 * @return array
	 */
	private function _getForeignKeysTo($table, $column)
	{
		$fks = array();

		if (isset($this->_foreignKeysByRefTable[$table]))
		{
			foreach ($this->_foreignKeysByRefTable[$table] as $fk)
			{
				if (in_array($column, $fk->refColumns))
				{
					$fks[] = $fk;
				}
			}
		}

		return $fks;
	}

	/**
	 * Drops all the foreign keys on a table.
	 *
	 * @access private
	 * @param string $table
	 */
	private function _dropAllForeignKeysOnTable($table)
	{
		if (isset($this->_foreignKeysByTable[$table]))
		{
			foreach ($this->_foreignKeysByTable[$table] as $fk)
			{
				$this->dropForeignKey($fk->table, implode(',', $fk->columns));
			}
		}
	}

	/**
	 * Creates entries for all rows in a given table, swaps its 'id' PK for 'entryId',
	 * and updates any FK's in other tables.
	 *
	 * @access private
	 * @param string $table
	 * @param string $entryType
	 */
	private function _entryify($table, $entryType)
	{
		$fks = array();

		// Find all of the foreign keys to this table's id column
		foreach ($this->_getForeignKeysTo($table, 'id') as $fk)
		{
			// Figure out which column in the FK is pointing to this table's id column
			$idColumnIndex = array_search('id', $fk->refColumns);

			//$newRefColumns = array_merge($fk->refColumns);
			//$newRefColumns[$idColumnIndex] = 'entryId';

			$fk = (object) array(
				'fk'            => $fk,
				'column'        => $fk->columns[$idColumnIndex],
				'type'          => $fk->columnTypes[$idColumnIndex],
				//'newRefColumns' => $newRefColumns,
			);

			// Drop the FK constraint
			$this->dropForeignKey($fk->fk->table, implode(',', $fk->fk->columns));

			// Create the temporary column
			$this->addColumnAfter($fk->fk->table, $fk->column.'_tmp', $fk->type, $fk->column);

			$fks[] = $fk;
		}

		// Rename the old id column to oldId
		$this->renameColumn($table, 'id', 'oldId');

		// Add an entryId column to the table
		$this->addColumnAfter($table, 'id', array('column' => ColumnType::Int, 'required' => true), 'oldId');

		// Get all of the known IDs
		$oldRows = blx()->db->createCommand()->select('oldId,dateCreated')->from($table)->queryAll();

		// Get all of the link criterias
		$leftLinkCriteriaIds = blx()->db->createCommand()->select('id')->where(array('leftEntryType' => $entryType))->from('linkcriteria')->queryColumn();
		$rightLinkCriteriaIds = blx()->db->createCommand()->select('id')->where(array('rightEntryType' => $entryType))->from('linkcriteria')->queryColumn();

		foreach ($oldRows as $row)
		{
			// Create a new entry
			$this->insert('entries', array(
				'type'     => $entryType,
				'postDate' => $row['dateCreated'],
				'enabled'  => 1
			));

			// Get the new entry ID
			$entryId = blx()->db->getLastInsertID();

			// Update the table with the new entry ID
			$this->update($table, array('id' => $entryId), array('oldId' => $row['oldId']));

			// Update any links
			if ($leftLinkCriteriaIds)
			{
				$this->update('links', array('leftEntryId' => $entryId),
					array('and', array('in', 'criteriaId', $leftLinkCriteriaIds), 'leftEntityId = :id'),
					array(':id' => $row['oldId'])
				);
			}

			if ($rightLinkCriteriaIds)
			{
				$this->update('links', array('rightEntryId' => $entryId),
					array('and', array('in', 'criteriaId', $rightLinkCriteriaIds), 'rightEntityId = :id'),
					array(':id' => $row['oldId'])
				);
			}

			// Update the other tables' new FK columns
			foreach ($fks as $fk)
			{
				$this->update($fk->fk->table, array($fk->column.'_tmp' => $entryId), array($fk->column => $row['oldId']));
			}

			// Update permissions
			if ($table == 'pages')
			{
				blx()->db->createCommand()->update('userpermissions',
					array('name' => 'editsingleton'.$entryId),
					array('name' => 'editpage'.$row['oldId'])
				);
			}
		}

		// Drop the oldId column
		$this->dropColumn($table, 'oldId');

		// Set the new PK
		$this->addPrimaryKey($table, 'id');

		// Make 'id' a FK to entries
		$this->addForeignKey($table, 'id', 'entries', 'id', 'CASCADE');

		// Now deal with the rest of the tables
		foreach ($fks as $fk)
		{
			// Move the entry IDs over from the temporary column to the real column
			$this->execute('UPDATE {{'.$fk->fk->table.'}} SET `'.$fk->column.'` = `'.$fk->column.'_tmp`');

			// Drop the temporary column
			$this->dropColumn($fk->fk->table, $fk->column.'_tmp');

			// Add a FK constraint back
			$this->addForeignKey($fk->fk->table, implode(',', $fk->fk->columns), $fk->fk->refTable, implode(',', $fk->fk->refColumns), $fk->fk->onDelete, $fk->fk->onUpdate);
		}
	}

	/**
	 * Creates a new field group, adds some existing fields to it, migrates the old content into entrycontent, and drops the old content table.
	 *
	 * @access private
	 * @param string $groupName
	 * @param string $oldFieldsTable
	 * @param array $fieldTableConditions
	 * @param string|null $oldContentTable
	 * @param string|null $oldContentTableFk
	 * @param bool $hasLanguageColumn
	 * @return array
	 */
	private function _migrateFields($groupName, $oldFieldsTable, $fieldTableConditions = array(), $oldContentTable = null, $oldContentTableFk = null, $hasLanguageColumn = false)
	{
		// Create the new field group and get its ID
		$this->insert('fieldgroups', array('name' => $groupName));
		$groupId = blx()->db->getLastInsertID();

		// Get the fields
		$fields = blx()->db->createCommand()
			->select('id,name,handle,instructions,required,translatable,type,settings,sortOrder')
			->from($oldFieldsTable)
			->where($fieldTableConditions)
			->order('sortOrder')
			->queryAll();

		$oldContentColumns = array($oldContentTableFk);
		$newContentColumns = array('entryId', 'locale');

		foreach ($fields as &$field)
		{
			// Make sure that the handle is unique
			$field['oldHandle'] = $field['handle'];

			if (in_array($field['handle'], $this->_fieldHandles))
			{
				for ($i = 1; in_array($field['handle'], $this->_fieldHandles); $i++)
				{
					$field['handle'] = $field['oldHandle'].$i;
				}

				Blocks::log("Renamed the {$groupName}/{$field['name']} field's handle from '{$field['oldHandle']}' to '{$field['handle']}'.", \CLogger::LEVEL_WARNING);
			}

			$this->_fieldHandles[] = $field['handle'];

			// Save it to the fields table
			// (required and sortOrder are getting moved to the new fields table)
			$this->insert('fields', array(
				'groupId'      => $groupId,
				'name'         => $field['name'],
				'handle'       => $field['handle'],
				'instructions' => $field['instructions'],
				'translatable' => $field['translatable'],
				'type'         => $field['type'],
				'settings'     => $field['settings'],
			));

			// Save the groupId and new global field ID on the $field array
			$field['groupId'] = $groupId;
			$field['newId']   = blx()->db->getLastInsertID();

			// Get the fieldtype
			$fieldType = blx()->components->getComponentByTypeAndClass(ComponentType::Field, $field['type']);

			if ($field['settings'])
			{
				$settings = JsonHelper::decode($field['settings']);
				$fieldType->setSettings($settings);
			}

			// Does the fieldtype want a entrycontent column?
			$contentColumnType = $fieldType->defineContentAttribute();
			$field['hasContentColumn'] = (bool) $contentColumnType;
			if ($field['hasContentColumn'])
			{
				$contentColumnType = ModelHelper::normalizeAttributeConfig($contentColumnType);

				// Add the new column to entrycontent
				$this->addColumn('entrycontent', $field['handle'], $contentColumnType);

				$oldContentColumns[] = $field['oldHandle'];
				$newContentColumns[] = $field['handle'];
			}
		}

		// Migrate the content?
		if ($oldContentTable)
		{
			// Fetch the content from the old content table
			$query = blx()->db->createCommand()->select(implode(',', $oldContentColumns))->from($oldContentTable);

			if ($hasLanguageColumn)
			{
				// Ignore content that wasn't written in the site's primary language
				$query->where(array('language' => $this->_primaryLocale));
			}

			$content = $query->queryAll(false);

			// Add the primary locale into the data
			foreach ($content as &$row)
			{
				array_splice($row, 1, 0, $this->_primaryLocale);
			}

			// Add the content to entrycontent
			$this->insertAll('entrycontent', $newContentColumns, $content);

			// Drop the old content table
			$this->dropTable($oldContentTable);
		}

		return $fields;
	}

	/**
	 * Adds a locale foreign key to a table.
	 *
	 * @access private
	 * @param string $table
	 */
	private function _addLocaleForeignKey($table, $column = 'locale', $required = true)
	{
		if ($required)
		{
			// Delete any rows that don't have the proper locale
			$this->delete($table,
				$column.' != :locale',
				array(':locale' => $this->_primaryLocale)
			);

			$onDelete = 'CASCADE';
		}
		else
		{
			// Set the locale column to null where it doesn't match the primary locale
			$this->update($table,
				array($column => null),
				$column.' != :locale',
				array(':locale' => $this->_primaryLocale)
			);

			$onDelete = 'SET NULL';
		}

		// Add the foreign key
		$this->addForeignKey($table, $column, 'locales', 'locale', $onDelete, 'CASCADE');
	}
}
