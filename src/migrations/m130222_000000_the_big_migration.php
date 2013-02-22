<?php
namespace Blocks;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130222_000000_the_big_migration extends BaseMigration
{
	private $_primaryLocale;

	private $_tables;
	private $_tablePrefixLength;
	private $_fkRefActions = 'RESTRICT|CASCADE|NO ACTION|SET DEFAULT|SET NULL';

	private $_fieldHandles = array('id', 'dateCreated', 'dateUpdated', 'uid', 'author', 'authorId', 'entryTagEntries', 'type', 'postDate', 'expiryDate', 'enabled', 'archived', 'locale', 'title', 'uri', 'url');

	private $_sections;
	private $_blogSectionId;
	private $_newEntryFieldIds;


	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$this->_tablePrefixLength = strlen(blx()->db->tablePrefix);

		// Create all the new tables
		$this->_createAndPopulateLocalesTable();
		$this->_createElementTables();
		$this->_createFieldTables();
		$this->_createUserPermissionTablesIfNecessary();
		$this->_createSectionTables();

		$this->_prepLinkTables();
		$this->_analyzeTables();
		$this->_migrateSingletons();
		$this->_migrateEntries();
		$this->_updateEntryRevisionTables();
		$this->_migrateUsers();
		$this->_migrateAssets();
		$this->_migrateGlobals();

		$this->_cleanupLinksTable();

		$this->_updateEmailMessageTable();

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
	 * Creates the new element tables.
	 *
	 * @access private
	 */
	private function _createElementTables()
	{
		// Create the elements table
		$this->createTable('elements', array(
			'type'     => array('maxLength' => 150, 'column' => ColumnType::Char, 'required' => true),
			'enabled'  => array('maxLength' => 1, 'default' => true, 'required' => true, 'column' => ColumnType::TinyInt, 'unsigned' => true),
			'archived' => array('maxLength' => 1, 'default' => false, 'required' => true, 'column' => ColumnType::TinyInt, 'unsigned' => true),
		));
		$this->createIndex('elements', 'type', false);
		$this->createIndex('elements', 'enabled', false);
		$this->createIndex('elements', 'archived', false);

		// Create the elements_i18n table
		$this->createTable('elements_i18n', array(
			'elementId' => array('column' => ColumnType::Int, 'required' => true),
			'locale'    => array('column' => ColumnType::Locale, 'required' => true),
			'uri'       => array(),
		));
		$this->createIndex('elements_i18n', 'elementId,locale', true);
		$this->createIndex('elements_i18n', 'uri,locale', true);
		$this->addForeignKey('elements_i18n', 'elementId', 'elements', 'id', 'CASCADE', null);
		$this->addForeignKey('elements_i18n', 'locale', 'locales', 'locale', 'CASCADE', 'CASCADE');

		// Create the content table
		$this->createTable('content', array(
			'elementId' => array('column' => ColumnType::Int, 'required' => true),
			'locale'    => array('column' => ColumnType::Locale, 'required' => true),
		));
		$this->createIndex('content', 'elementId,locale', true);
		$this->addForeignKey('content', 'elementId', 'elements', 'id', 'CASCADE', null);
		$this->addForeignKey('content', 'locale', 'locales', 'locale', 'CASCADE', 'CASCADE');
	}

	/**
	 * Creates the new field tables.
	 *
	 * @access private
	 */
	private function _createFieldTables()
	{
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
	}

	/**
	 * Adds the userpermission tables if necessary.
	 *
	 * @access private
	 */
	private function _createUserPermissionTablesIfNecessary()
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
	 * Updates the section tables.
	 *
	 * @access private
	 */
	private function _createSectionTables()
	{
		// Update the section tables
		//--------------------------

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
			$this->_blogSectionId = blx()->db->getLastInsertID();

			// Update permissions
			$entryPermissions = array('editentries', 'createentries', 'editpeerentries', 'editpeerentrydrafts', 'publishpeerentrydrafts', 'deletepeerentrydrafts', 'publishentries');
			$blogPermissions = blx()->db->createCommand()
				->select('id,name')
				->from('userpermissions')
				->where(array('in', 'name', $entryPermissions))
				->queryAll();

			foreach ($blogPermissions as $permission)
			{
				$newName = $permission['name'].'insection'.$this->_blogSectionId;
				blx()->db->createCommand()->update('userpermissions', array('name' => $newName), array('id' => $permission['id']));
			}

			// Add the sectionId column to the entries table
			$this->addColumnAfter('entries', 'sectionId', array('column' => ColumnType::Int, 'required' => true), 'authorId');
			$this->addForeignKey('entries', 'sectionId', 'sections', 'id', 'CASCADE');

			// Update all the existing entries with the new blog section ID
			$this->update('entries', array('sectionId' => $this->_blogSectionId));
		}

		// Add the fieldLayoutId FK
		$this->addForeignKey('sections', 'fieldLayoutId', 'fieldlayouts', 'id', 'SET NULL', null);

		// Create the sections_i18n table
		$this->createTable('sections_i18n', array(
			'sectionId' => array('column' => ColumnType::Int, 'required' => true),
			'locale'    => array('column' => ColumnType::Locale, 'required' => true),
			'urlFormat' => array(),
		));
		$this->createIndex('sections_i18n', 'sectionId,locale', true);
		$this->addForeignKey('sections_i18n', 'sectionId', 'sections', 'id', 'CASCADE');
		$this->addForeignKey('sections_i18n', 'locale', 'locales', 'locale', 'CASCADE', 'CASCADE');

		// Populate the sections_i18n table
		//---------------------------------

		$sectionLocaleData = array();

		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			$this->_sections = blx()->db->createCommand()->select('id,name,handle,hasUrls,urlFormat')->from('sections')->queryAll();

			foreach ($this->_sections as $section)
			{
				$sectionLocaleData[] = array($section['id'], $this->_primaryLocale, $section['urlFormat']);
			}

			// Drop the old urlFormat column
			$this->dropColumn('sections', 'urlFormat');
		}
		else
		{
			$sectionLocaleData[] = array($this->_blogSectionId, $this->_primaryLocale, 'blog/{slug}');
		}

		$this->insertAll('sections_i18n', array('sectionId', 'locale', 'urlFormat'), $sectionLocaleData);
	}

	/**
	 * Updates the element types in linkcriteria, adds the new leftElementId and rightElementId columns to links, and deletes any duplicate links.
	 */
	private function _prepLinkTables()
	{
		$this->renameColumn('linkcriteria', 'leftEntityType', 'leftElementType');
		$this->renameColumn('linkcriteria', 'rightEntityType', 'rightElementType');

		// Update the element type values (Entry and Asset remain unchanged!)
		$elementTypes = array(
			//'Entry'       => ElementType::Entry,
			//'Asset'       => ElementType::Asset,
			'Page'        => ElementType::Singleton,
			'UserProfile' => ElementType::User,
			'Global'      => ElementType::Globals,
		);

		foreach ($elementTypes as $entityType => $elementType)
		{
			$this->update('linkcriteria', array('leftElementType' => $elementType), array('leftElementType' => $entityType));
			$this->update('linkcriteria', array('rightElementType' => $elementType), array('rightElementType' => $entityType));
		}

		// Update the links table
		$this->dropForeignKey('links', 'criteriaId');
		$this->dropIndex('links', 'criteriaId,leftEntityId,rightEntityId', true);
		$this->alterColumn('links', 'criteriaId', array('column' => ColumnType::Int, 'required' => true));
		$this->addColumnAfter('links', 'leftElementId', array('column' => ColumnType::Int, 'required' => true), 'leftEntityId');
		$this->addColumnAfter('links', 'rightElementId', array('column' => ColumnType::Int, 'required' => true), 'rightEntityId');

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
	 * Migrates singletons.
	 *
	 * @access private
	 */
	private function _migrateSingletons()
	{
		// Make singletons elemental
		$this->_makeElemental('pages', ElementType::Singleton);

		// Modify the columns
		$this->renameColumn('pages', 'title', 'name');
		$this->addColumn('pages', 'fieldLayoutId', array('maxLength' => 11, 'decimals' => 0, 'unsigned' => false, 'length' => 10, 'column' => ColumnType::Int));

		// Create the singletons_i18n table
		$this->createTable('singletons_i18n', array(
			'singletonId' => array('column' => ColumnType::Int, 'required' => true),
			'locale'      => array('column' => ColumnType::Locale, 'required' => true),
		));
		$this->createIndex('singletons_i18n', 'singletonId,locale', true);
		$this->addForeignKey('singletons_i18n', 'singletonId', 'pages', 'id', 'CASCADE');
		$this->addForeignKey('singletons_i18n', 'locale', 'locales', 'locale', 'CASCADE', 'CASCADE');

		// Migrate the content
		//--------------------

		// Get all the singletons
		$singletons = blx()->db->createCommand()->select('id,name,uri')->from('pages')->queryAll();

		$i18nData = array();
		$localesData = array();

		foreach ($singletons as $singleton)
		{
			// Migrate the fields
			$fields = $this->_migrateFields('Singletons - '.$singleton['name'], 'pageblocks', array('pageId' => $singleton['id']));
			$fieldLayoutId = $this->_saveFieldLayout(ElementType::Singleton, $fields, 'Content');
			$this->update('pages', array('fieldLayoutId' => $fieldLayoutId), array('id' => $singleton['id']));

			// Queue up the new data
			$i18nData[] = array($singleton['id'], $this->_primaryLocale, $singleton['uri']);
			$localesData[] = array($singleton['id'], $this->_primaryLocale);

			// Migrate the content
			$oldContent = blx()->db->createCommand()->select('content')->from('pagecontent')->where(array('pageId' => $singleton['id'], 'language' => $this->_primaryLocale))->queryScalar();
			$newContent = array('elementId' => $singleton['id'], 'locale' => $this->_primaryLocale);

			if ($oldContent)
			{
				$oldContent = JsonHelper::decode($oldContent);

				foreach ($fields as $field)
				{
					if (isset($oldContent[$field['id']]) && isset($this->_tables['content']->columns[$field['handle']]))
					{
						$newContent[$field['handle']] = $oldContent[$field['id']];
					}
				}
			}

			$this->insert('content', $newContent);
		}

		// Batch-insert the new singleton data
		$this->insertAll('elements_i18n', array('elementId', 'locale', 'uri'), $i18nData);
		$this->insertAll('singletons_i18n', array('singletonId', 'locale'), $localesData);

		// Cleanup
		$this->dropTable('pagecontent');
		$this->dropTable('pageblocks');
		$this->dropIndex('pages', 'uri', true);
		$this->dropColumn('pages', 'uri');

		// Rename the pages table to singletons
		$this->dropForeignKey('pages', 'id');
		$this->renameTable('pages', 'singletons');
		$this->addForeignKey('singletons', 'id', 'elements', 'id', 'CASCADE');
		$this->addForeignKey('singletons', 'fieldLayoutId', 'fieldlayouts', 'id', 'SET NULL', null);

		// Keep the table info up-to-date
		unset($this->_tables['pages']);
		unset($this->_tables['pagecontent']);
		unset($this->_tables['pageblocks']);
		$this->_analyzeTable('singletons');
		$this->_analyzeTable('singletons_i18n');
	}

	/**
	 * Migrates entries.
	 */
	private function _migrateEntries()
	{
		// Make users elemental
		$this->_makeElemental('entries', ElementType::Entry);

		// Tweak the entries table
		$this->alterColumn('entries', 'postDate', array('column' => ColumnType::DateTime, 'required' => true));
		$this->createIndex('entries', 'postDate');
		$this->createIndex('entries', 'expiryDate');
		$this->dropColumn('entries', 'enabled');
		$this->dropColumn('entries', 'archived');

		// Rename the entrytitles table to entries_i18n
		$this->dropForeignKey('entrytitles', 'entryId');
		$this->dropIndex('entrytitles', 'title,entryId,language');
		$this->dropIndex('entrytitles', 'entryId,language', true);
		$this->renameTable('entrytitles', 'entries_i18n');
		$this->addColumnAfter('entries_i18n', 'sectionId', array('column' => ColumnType::Int, 'required' => true), 'entryId');
		$this->alterColumn('entries_i18n', 'language', array('column' => ColumnType::Locale, 'required' => true), 'locale');
		$this->addColumn('entries_i18n', 'slug', array());
		$this->createIndex('entries_i18n', 'entryId,locale', true);
		$this->createIndex('entries_i18n', 'slug,sectionId,locale', true);
		$this->createIndex('entries_i18n', 'title', false);
		$this->addForeignKey('entries_i18n', 'entryId', 'entries', 'id', 'CASCADE');
		$this->_addLocaleForeignKey('entries_i18n');

		// Move entry slugs into entries_i18n, and entry URIs into elements_i18n
		//----------------------------------------------------------------------

		// Get all of the data to be migrated
		$entries = blx()->db->createCommand()
			->select('id, sectionId, slug, uri')
			->from('entries')
			->queryAll();

		$elementLocaleData = array();

		foreach ($entries as $entry)
		{
			// It's possible that an entry's URI was already taken by a singleton, so let's make sure it's unique
			$conditions = array('and', 'locale = :locale', 'uri = :uri');
			$params = array(':locale' => $this->_primaryLocale);

			for ($i = 0; true; $i++)
			{
				$slug = $entry['slug'].($i == 0 ? '' : "-{$i}");
				$uri  = ($i == 0 ? $entry['uri'] : str_replace($entry['slug'], $slug, $entry['uri']));

				$params[':uri'] = $uri;

				$totalElements = blx()->db->createCommand()
					->select('count(id)')
					->from('elements_i18n')
					->where($conditions, $params)
					->queryScalar();

				if ($totalElements == 0)
				{
					break;
				}
			}

			// Copy the sectionId and slug into entries_i18n
			$this->update('entries_i18n',
				array('sectionId' => $entry['sectionId'], 'slug' => $slug),
				array('entryId' => $entry['id'])
			);

			// Queue up a new row in elements_i18n for the entry
			$elementLocaleData[] = array($entry['id'], $this->_primaryLocale, $uri);
		}

		// Add the new rows to elements_i18n
		$this->insertAll('elements_i18n', array('elementId', 'locale', 'uri'), $elementLocaleData);

		// Finally add the entries_i18n.sectionId FK
		$this->addForeignKey('entries_i18n', 'sectionId', 'sections', 'id', 'CASCADE');

		// Drop the slug and uri columns from the entries table

		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			$this->dropIndex('entries', 'slug,sectionId', true);
		}
		else
		{
			$this->dropIndex('entries', 'slug', true);
		}

		$this->dropIndex('entries', 'uri', true);
		$this->dropColumn('entries', 'slug');
		$this->dropColumn('entries', 'uri');

		// Migrate the content
		//--------------------

		$this->_newEntryFieldIds = array();

		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			foreach ($this->_sections as $section)
			{
				$contentTable = 'entrycontent_'.$section['handle'];
				$fields = $this->_migrateFields($section['name'], 'entryblocks', array('sectionId' => $section['id']), $contentTable, 'entryId', true);
				$fieldLayoutId = $this->_saveFieldLayout(ElementType::Entry, $fields, 'Content');
				$this->update('sections', array('fieldLayoutId' => $fieldLayoutId), array('id' => $section['id']));

				foreach ($fields as $field)
				{
					$this->_newEntryFieldIds[$field['id']] = $field['newId'];
				}
			}
		}
		else
		{
			$fields = $this->_migrateFields('Blog', 'entryblocks', array(), 'entrycontent', 'entryId', true);
			$fieldLayoutId = $this->_saveFieldLayout(ElementType::Entry, $fields, 'Content');
			$this->update('sections', array('fieldLayoutId' => $fieldLayoutId), array('id' => $this->_blogSectionId));
		}

		// Delete the old entryblocks table
		$this->dropTable('entryblocks');

		// Keep the table info up-to-date
		unset($this->_tables['entrytitles']);
		unset($this->_tables['entryblocks']);
		$this->_analyzeTable('entries');
		$this->_analyzeTable('entries_i18n');
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
					$fieldData = array();

					$data = JsonHelper::decode($revision['data']);

					if (isset($data['blocks']))
					{
						foreach ($data['blocks'] as $blockId => $value)
						{
							if (isset($this->_newEntryFieldIds[$blockId]))
							{
								$fieldId = $this->_newEntryFieldIds[$blockId];
								$fieldData[$fieldId] = $value;
							}
						}

						unset($data['blocks']);
					}

					$data['fields'] = $fieldData;

					$this->update($table,
						array('data' => JsonHelper::encode($data)),
						array('id' => $revision['id'])
					);
				}
			}
		}
	}

	/**
	 * Migrates users.
	 *
	 * @access private
	 */
	private function _migrateUsers()
	{
		// Make users elemental
		$this->_makeElemental('users', ElementType::User);

		// Rename the language column
		$this->alterColumn('users', 'language', array('column' => ColumnType::Locale), 'preferredLocale');
		$this->_addLocaleForeignKey('users', 'preferredLocale', false);

		// Migrate the content
		//--------------------

		if (Blocks::hasPackage(BlocksPackage::Users))
		{
			// Migrate the fields and content
			$fields = $this->_migrateFields('Users', 'userprofileblocks', array(), 'userprofiles', 'userId', false);
			$this->_saveFieldLayout(ElementType::User, $fields);

			// Delete the old userprofileblocks table
			$this->dropTable('userprofileblocks');
		}

		// Keep the table info up-to-date
		unset($this->_tables['userprofileblocks']);
		$this->_analyzeTable('users');
	}

	/**
	 * Migrates assets.
	 *
	 * @access private
	 */
	private function _migrateAssets()
	{
		// Make assets elemental
		$this->_makeElemental('assetfiles', ElementType::Asset);

		// Migrate the content
		//--------------------

		// Migrate the fields and content
		$fields = $this->_migrateFields('Assets', 'assetblocks', array(), 'assetcontent', 'fileId', false);
		$this->_saveFieldLayout(ElementType::Asset, $fields);

		// Delete the assetblocks table
		$this->dropTable('assetblocks');

		// Keep the table info up-to-date
		unset($this->_tables['assetblocks']);
		$this->_analyzeTable('assetfiles');
	}

	/**
	 * Migrates globals.
	 *
	 * @access private
	 */
	private function _migrateGlobals()
	{
		// Migrate the content
		//--------------------

		// Migrate the fields
		$fields = $this->_migrateFields(ElementType::Globals, 'globalblocks');
		$this->_saveFieldLayout(ElementType::Globals, $fields);

		$oldContent = blx()->db->createCommand()->from('globalcontent')->where(array('language' => $this->_primaryLocale))->queryRow();

		if ($oldContent)
		{
			// Create the new Globals element
			$this->insert('elements', array(
				'type'     => ElementType::Globals,
				'enabled'  => 1
			));

			// Get the new element ID
			$elementId = blx()->db->getLastInsertID();

			// Add a row to elements_i18n
			$this->insert('elements_i18n', array(
				'elementId' => $elementId,
				'locale'    => $this->_primaryLocale,
			));

			// Migrate the content
			$newContent = array(
				'elementId' => $elementId,
				'locale'    => $this->_primaryLocale
			);

			foreach ($fields as $field)
			{
				if (isset($oldContent[$field['oldHandle']]) && isset($this->_tables['content']->columns[$field['handle']]))
				{
					$newContent[$field['handle']] = $oldContent[$field['oldHandle']];
				}
			}

			$this->insert('content', $newContent);

			// Update the Links table
			//-----------------------

			$linkCriteriaIds = blx()->db->createCommand()->select('id')->where(array('leftElementType' => ElementType::Globals))->from('linkcriteria')->queryColumn();

			if ($linkCriteriaIds)
			{
				$this->update('links', array('leftElementId' => $elementId), array('in', 'criteriaId', $linkCriteriaIds));
			}
		}

		// Cleanup
		$this->dropTable('globalblocks');
		$this->dropTable('globalcontent');

		// Keep the table info up-to-date
		unset($this->_tables['globalblocks']);
		unset($this->_tables['globalcontent']);
	}

	/**
	 * Drops the old columns from the links table. Called once all the element types are finished migrating.
	 *
	 * @access private
	 */
	private function _cleanupLinksTable()
	{
		$this->createIndex('links', 'criteriaId,leftElementId,rightElementId', true);
		$this->addForeignKey('links', 'criteriaId', 'linkcriteria', 'id', 'CASCADE');
		$this->addForeignKey('links', 'leftElementId', 'elements', 'id', 'CASCADE');
		$this->addForeignKey('links', 'rightElementId', 'elements', 'id', 'CASCADE');

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
	 * Records all the foreign keys and indexes for each table.
	 *
	 * @access private
	 */
	private function _analyzeTables()
	{
		$this->_tables = array();

		$tables = blx()->db->getSchema()->getTableNames();

		foreach ($tables as $table)
		{
			$table = substr($table, $this->_tablePrefixLength);
			$this->_analyzeTable($table);
		}
	}

	/**
	 * Records all the foreign keys and indexes for a given table.
	 */
	private function _analyzeTable($table)
	{
		$this->_tables[$table] = (object) array(
			'name'    => $table,
			'columns' => array(),
			'fks'     => array(),
			'indexes' => array(),
		);

		// Get the CREATE TABLE sql
		$query = blx()->db->createCommand()->setText('SHOW CREATE TABLE `{{'.$table.'}}`')->queryRow();
		$createTableSql = $query['Create Table'];

		// Find the columns
		if (preg_match_all('/^\s*`(\w+)`\s+(.*),$/m', $createTableSql, $matches, PREG_SET_ORDER))
		{
			foreach ($matches as $match)
			{
				$name = $match[1];
				$this->_tables[$table]->columns[$name] = (object) array(
					'name' => $name,
					'type' => $match[2]
				);
			}
		}

		// Find the foreign keys
		if (preg_match_all("/CONSTRAINT `(\w+)` FOREIGN KEY \(`([\w`,]+)`\) REFERENCES `(\w+)` \(`([\w`,]+)`\)( ON DELETE ({$this->_fkRefActions}))?( ON UPDATE ({$this->_fkRefActions}))?/", $createTableSql, $matches, PREG_SET_ORDER))
		{
			foreach ($matches as $match)
			{
				$name = $match[1];
				$this->_tables[$table]->fks[$name] = (object) array(
					'name'        => $name,
					'columns'     => explode('`,`', $match[2]),
					'refTable'    => substr($match[3], $this->_tablePrefixLength),
					'refColumns'  => explode('`,`', $match[4]),
					'onDelete'    => (!empty($match[6]) ? $match[6] : null),
					'onUpdate'    => (!empty($match[8]) ? $match[8] : null),
				);
			}
		}

		// Find the indexes
		if (preg_match_all('/(UNIQUE )?KEY `(\w+)` \(`([\w`,]+)`\)/', $createTableSql, $matches, PREG_SET_ORDER))
		{
			foreach ($matches as $match)
			{
				$name = $match[2];
				$this->_tables[$table]->indexes[$name] = (object) array(
					'name'    => $name,
					'columns' => explode('`,`', $match[3]),
					'unique'  => !empty($match[1]),
				);
			}
		}
	}

	/**
	 * Returns all of the tables that have foreign keys to a given table and column.
	 *
	 * @param string $table
	 * @param string $column
	 * @return array
	 */
	private function _getTablesWithForeignKeysTo($table, $column = 'id')
	{
		$tables = array();

		foreach ($this->_tables as $table)
		{
			foreach ($table->fks as $fk)
			{
				if ($fk->refTable == $table && in_array($column, $fk->refColumns))
				{
					$fkTables[] = $table;
				}
			}
		}

		return $tables;
	}

	/**
	 * Drops all the foreign keys on a table.
	 *
	 * @access private
	 * @param object $table
	 */
	private function _dropAllForeignKeysOnTable($table)
	{
		foreach ($table->fks as $fk)
		{
			// Don't assume that the FK name is "correct"
			blx()->db->createCommand()->setText(blx()->db->getSchema()->dropForeignKey($fk->name, '{{'.$table->name.'}}'))->execute();
		}
	}

	/**
	 * Drops all the unique indexes on a table.
	 *
	 * @access private
	 * @param object $table
	 */
	private function _dropAllUniqueIndexesOnTable($table)
	{
		foreach ($table->indexes as $index)
		{
			if ($index->unique)
			{
				// Don't assume that the constraint name is "correct"
				blx()->db->createCommand()->setText(blx()->db->getSchema()->dropIndex($index->name, '{{'.$table->name.'}}'))->execute();
			}
		}
	}

	/**
	 * Restores all the foreign keys on a table.
	 *
	 * @access private
	 * @param object $table
	 */
	private function _restoreAllForeignKeysOnTable($table)
	{
		foreach ($table->fks as $fk)
		{
			$this->addForeignKey($table->name, implode(',', $fk->columns), $fk->refTable, implode(',', $fk->refColumns));
		}
	}

	/**
	 * Restores all the unique indexes on a table.
	 *
	 * @access private
	 * @param object $table
	 */
	private function _restoreAllUniqueIndexesOnTable($table)
	{
		foreach ($table->indexes as $index)
		{
			if ($index->unique)
			{
				$this->createIndex($table->name, implode(',', $index->columns), true);
			}
		}
	}

	/**
	 * Creates elements for all rows in a given table, swaps its 'id' PK for 'elementId',
	 * and updates any FK's in other tables.
	 *
	 * @access private
	 * @param string $table
	 * @param string $elementType
	 */
	private function _makeElemental($table, $elementType)
	{
		$idColumnType = array('column' => ColumnType::Int, 'required' => true);

		// Figure out which tables have FK's pointing to this table's id column
		$fks = array();

		foreach ($this->_tables as $otherTable)
		{
			foreach ($otherTable->fks as $fk)
			{
				if ($fk->refTable == $table)
				{
					// Figure out which column in the FK is pointing to this table's id column (if any)
					$fkColumnIndex = array_search('id', $fk->refColumns);

					if ($fkColumnIndex !== false)
					{
						// Get its column name
						$fkColumnName = $fk->columns[$fkColumnIndex];

						// Get its column type
						$fkColumnRequired = (strpos($otherTable->columns[$fkColumnName]->type, 'NOT NULL') !== false);
						$fkColumnType = array_merge($idColumnType, array('required' => $fkColumnRequired));

						// Drop all FKs and indexes on this table
						$this->_dropAllForeignKeysOnTable($otherTable);
						$this->_dropAllUniqueIndexesOnTable($otherTable);

						// Rename the old id column and add the new one
						$this->renameColumn($otherTable->name, $fkColumnName, $fkColumnName.'_old');
						$this->addColumnAfter($otherTable->name, $fkColumnName, $fkColumnType, $fkColumnName.'_old');

						$fks[] = (object) array(
							'table'  => $otherTable,
							'column' => $fkColumnName
						);
					}
				}
			}
		}

		// Rename the old id column and add the new one
		$this->renameColumn($table, 'id', 'id_old');
		$this->addColumnAfter($table, 'id', $idColumnType, 'id_old');

		// Get all of the rows
		$oldRows = blx()->db->createCommand()
			->select('id_old'.($elementType == ElementType::Entry ? ',enabled,archived' : ''))
			->from($table)
			->queryAll();

		// Get all of the link criterias
		$leftLinkCriteriaIds = blx()->db->createCommand()->select('id')->where(array('leftElementType' => $elementType))->from('linkcriteria')->queryColumn();
		$rightLinkCriteriaIds = blx()->db->createCommand()->select('id')->where(array('rightElementType' => $elementType))->from('linkcriteria')->queryColumn();

		foreach ($oldRows as $row)
		{
			// Create a new row in elements
			$this->insert('elements', array(
				'type'     => $elementType,
				'enabled'  => ($elementType == ElementType::Entry ? $row['enabled'] : 1),
				'archived' => ($elementType == ElementType::Entry ? $row['archived'] : 0)
			));

			// Get the new element ID
			$elementId = blx()->db->getLastInsertID();

			// Update this table with the new element ID
			$this->update($table, array('id' => $elementId), array('id_old' => $row['id_old']));

			// Update any links
			if ($leftLinkCriteriaIds)
			{
				$this->update('links', array('leftElementId' => $elementId),
					array('and', array('in', 'criteriaId', $leftLinkCriteriaIds), 'leftEntityId = :id'),
					array(':id' => $row['id_old'])
				);
			}

			if ($rightLinkCriteriaIds)
			{
				$this->update('links', array('rightElementId' => $elementId),
					array('and', array('in', 'criteriaId', $rightLinkCriteriaIds), 'rightEntityId = :id'),
					array(':id' => $row['id_old'])
				);
			}

			// Update the other tables' new FK columns
			foreach ($fks as $fk)
			{
				$this->update($fk->table->name, array($fk->column => $elementId), array($fk->column.'_old' => $row['id_old']));
			}

			if ($elementType == ElementType::Singleton)
			{
				// Update singleton permissions
				blx()->db->createCommand()->update('userpermissions',
					array('name' => 'editsingleton'.$elementId),
					array('name' => 'editpage'.$row['id_old'])
				);
			}
		}

		// Drop the old id column
		$this->dropColumn($table, 'id_old');

		// Set the new PK
		$this->addPrimaryKey($table, 'id');

		// Make 'id' a FK to elements
		$this->addForeignKey($table, 'id', 'elements', 'id', 'CASCADE');

		// Now deal with the rest of the tables
		foreach ($fks as $fk)
		{
			// Drop the old FK column
			$this->dropColumn($fk->table->name, $fk->column.'_old');

			// Restore its unique indexes and FKs
			$this->_restoreAllUniqueIndexesOnTable($fk->table);
			$this->_restoreAllForeignKeysOnTable($fk->table);
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
	 * Creates a new field group, adds some existing fields to it, migrates the old content into content, and drops the old content table.
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
		$newContentColumns = array('elementId', 'locale');

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

			// Did this field have a content column?
			$contentColumnType = false;

			// Let the fieldtype be the judge of that, if it exists
			$fieldType = blx()->components->getComponentByTypeAndClass(ComponentType::Field, $field['type']);

			if ($fieldType)
			{
				if ($field['settings'])
				{
					$settings = JsonHelper::decode($field['settings']);
					$fieldType->setSettings($settings);
				}

				$contentColumnType = $fieldType->defineContentAttribute();

				if ($contentColumnType)
				{
					// Normalize it
					$contentColumnType = ModelHelper::normalizeAttributeConfig($contentColumnType);
				}
			}

			// Check the old content table, if there was one
			else if ($oldContentTable)
			{
				if (isset($this->_tables[$oldContentTable]->columns[$field['oldHandle']]))
				{
					$contentColumnType = $this->_tables[$oldContentTable]->columns[$field['oldHandle']]->type;
				}
			}
			else
			{
				// Better safe than sorry... default to TEXT
				$contentColumnType = array('column' => ColumnType::Text);
			}

			if ($contentColumnType)
			{
				// Add the new content column
				$this->addColumn('content', $field['handle'], $contentColumnType);

				// Add a record of it to our table info array in case something else needs to check for it
				// *cough* singletons *cough* globals *cough*
				$this->_tables['content']->columns[$field['handle']] = (object) array(
					'name' => $field['handle'],
					'type' => $contentColumnType
				);

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

			// Add the content to content
			$this->insertAll('content', $newContentColumns, $content);

			// Drop the old content table
			$this->dropTable($oldContentTable);
			unset($this->_tables[$oldContentTable]);
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
