<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\migrations;

use craft\app\db\Migration;

/**
 * Installation Migration
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Install extends Migration
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function safeUp()
	{
		$this->_createTables();
		$this->_createIndexes();
		$this->_addForeignKeys();
	}

	// Private Methods
	// =========================================================================

	/**
	 * Creates the tables.
	 */
	private function _createTables()
	{
		$this->createTable(
			'{{%assetfolders}}',
			[
				'id' => 'pk',
				'parentId' => 'integer(11) DEFAULT NULL',
				'sourceId' => 'integer(11) DEFAULT NULL',
				'name' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'path' => 'string(255) COLLATE utf8_unicode_ci DEFAULT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%assetindexdata}}',
			[
				'id' => 'pk',
				'sessionId' => 'string(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'\'',
				'sourceId' => 'integer(10) NOT NULL',
				'offset' => 'integer(10) NOT NULL',
				'uri' => 'string(255) COLLATE utf8_unicode_ci DEFAULT NULL',
				'size' => 'integer(10) DEFAULT NULL',
				'recordId' => 'integer(10) DEFAULT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%assets}}',
			[
				'id' => 'pk',
				'sourceId' => 'integer(11) DEFAULT NULL',
				'folderId' => 'integer(11) NOT NULL',
				'filename' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'kind' => 'string(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'unknown\'',
				'width' => 'smallint(6) unsigned DEFAULT NULL',
				'height' => 'smallint(6) unsigned DEFAULT NULL',
				'size' => 'integer(11) unsigned DEFAULT NULL',
				'dateModified' => 'datetime DEFAULT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%assettransformindex}}',
			[
				'id' => 'pk',
				'fileId' => 'integer(11) NOT NULL',
				'filename' => 'string(255) COLLATE utf8_unicode_ci DEFAULT NULL',
				'format' => 'string(255) COLLATE utf8_unicode_ci DEFAULT NULL',
				'location' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'sourceId' => 'integer(11) DEFAULT NULL',
				'fileExists' => 'smallint(1) unsigned NOT NULL DEFAULT \'0\'',
				'inProgress' => 'smallint(1) unsigned NOT NULL DEFAULT \'0\'',
				'dateIndexed' => 'datetime DEFAULT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%assettransforms}}',
			[
				'id' => 'pk',
				'name' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'handle' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'mode' => 'enum(\'stretch\',\'fit\',\'crop\') COLLATE utf8_unicode_ci NOT NULL DEFAULT \'crop\'',
				'position' => 'enum(\'top-left\',\'top-center\',\'top-right\',\'center-left\',\'center-center\',\'center-right\',\'bottom-left\',\'bottom-center\',\'bottom-right\') COLLATE utf8_unicode_ci NOT NULL DEFAULT \'center-center\'',
				'height' => 'integer(10) DEFAULT NULL',
				'width' => 'integer(10) DEFAULT NULL',
				'format' => 'string(255) COLLATE utf8_unicode_ci DEFAULT NULL',
				'quality' => 'integer(11) DEFAULT NULL',
				'dimensionChangeTime' => 'datetime DEFAULT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%categories}}',
			[
				'id' => 'pk',
				'groupId' => 'integer(11) NOT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%categorygroups}}',
			[
				'id' => 'pk',
				'structureId' => 'integer(11) NOT NULL',
				'fieldLayoutId' => 'integer(11) DEFAULT NULL',
				'name' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'handle' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'hasUrls' => 'smallint(1) NOT NULL DEFAULT \'1\'',
				'template' => 'string(500) COLLATE utf8_unicode_ci DEFAULT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%categorygroups_i18n}}',
			[
				'id' => 'pk',
				'groupId' => 'integer(11) NOT NULL',
				'locale' => 'char(12) COLLATE utf8_unicode_ci NOT NULL',
				'urlFormat' => 'string(255) COLLATE utf8_unicode_ci DEFAULT NULL',
				'nestedUrlFormat' => 'string(255) COLLATE utf8_unicode_ci DEFAULT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%content}}',
			[
				'id' => 'pk',
				'elementId' => 'integer(11) NOT NULL',
				'locale' => 'char(12) COLLATE utf8_unicode_ci NOT NULL',
				'title' => 'string(255) COLLATE utf8_unicode_ci DEFAULT NULL',
				'field_heading' => 'string(255) COLLATE utf8_unicode_ci DEFAULT NULL',
				'field_siteIntro' => 'text COLLATE utf8_unicode_ci',
				'field_body' => 'text COLLATE utf8_unicode_ci',
				'field_description' => 'text COLLATE utf8_unicode_ci',
				'field_metaDescription' => 'text COLLATE utf8_unicode_ci',
				'field_linkColor' => 'char(7) COLLATE utf8_unicode_ci DEFAULT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%deprecationerrors}}',
			[
				'id' => 'pk',
				'key' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'fingerprint' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'lastOccurrence' => 'datetime NOT NULL',
				'file' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'line' => 'smallint(6) unsigned NOT NULL',
				'class' => 'string(255) COLLATE utf8_unicode_ci DEFAULT NULL',
				'method' => 'string(255) COLLATE utf8_unicode_ci DEFAULT NULL',
				'template' => 'string(255) COLLATE utf8_unicode_ci DEFAULT NULL',
				'templateLine' => 'smallint(6) unsigned DEFAULT NULL',
				'message' => 'string(255) COLLATE utf8_unicode_ci DEFAULT NULL',
				'traces' => 'text COLLATE utf8_unicode_ci',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%elements}}',
			[
				'id' => 'pk',
				'type' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'enabled' => 'smallint(1) unsigned NOT NULL DEFAULT \'1\'',
				'archived' => 'smallint(1) unsigned NOT NULL DEFAULT \'0\'',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%elements_i18n}}',
			[
				'id' => 'pk',
				'elementId' => 'integer(11) NOT NULL',
				'locale' => 'char(12) COLLATE utf8_unicode_ci NOT NULL',
				'slug' => 'string(255) COLLATE utf8_unicode_ci DEFAULT NULL',
				'uri' => 'string(255) COLLATE utf8_unicode_ci DEFAULT NULL',
				'enabled' => 'smallint(1) DEFAULT \'1\'',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%emailmessages}}',
			[
				'id' => 'pk',
				'key' => 'char(150) COLLATE utf8_unicode_ci NOT NULL',
				'locale' => 'char(12) COLLATE utf8_unicode_ci NOT NULL',
				'subject' => 'string(1000) COLLATE utf8_unicode_ci NOT NULL',
				'body' => 'text COLLATE utf8_unicode_ci NOT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%entries}}',
			[
				'id' => 'pk',
				'sectionId' => 'integer(11) NOT NULL',
				'typeId' => 'integer(11) DEFAULT NULL',
				'authorId' => 'integer(11) DEFAULT NULL',
				'postDate' => 'datetime DEFAULT NULL',
				'expiryDate' => 'datetime DEFAULT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%entrydrafts}}',
			[
				'id' => 'pk',
				'entryId' => 'integer(11) NOT NULL',
				'sectionId' => 'integer(11) NOT NULL',
				'creatorId' => 'integer(11) NOT NULL',
				'locale' => 'char(12) COLLATE utf8_unicode_ci NOT NULL',
				'name' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'notes' => 'text COLLATE utf8_unicode_ci',
				'data' => 'mediumtext COLLATE utf8_unicode_ci NOT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%entrytypes}}',
			[
				'id' => 'pk',
				'sectionId' => 'integer(11) NOT NULL',
				'fieldLayoutId' => 'integer(11) DEFAULT NULL',
				'name' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'handle' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'hasTitleField' => 'smallint(1) NOT NULL DEFAULT \'1\'',
				'titleLabel' => 'string(255) COLLATE utf8_unicode_ci DEFAULT NULL',
				'titleFormat' => 'string(255) COLLATE utf8_unicode_ci DEFAULT NULL',
				'sortOrder' => 'smallint(4) DEFAULT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%entryversions}}',
			[
				'id' => 'pk',
				'entryId' => 'integer(11) NOT NULL',
				'sectionId' => 'integer(11) NOT NULL',
				'creatorId' => 'integer(11) DEFAULT NULL',
				'locale' => 'char(12) COLLATE utf8_unicode_ci NOT NULL',
				'num' => 'smallint(6) unsigned NOT NULL',
				'notes' => 'text COLLATE utf8_unicode_ci',
				'data' => 'mediumtext COLLATE utf8_unicode_ci NOT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%fieldgroups}}',
			[
				'id' => 'pk',
				'name' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%fieldlayoutfields}}',
			[
				'id' => 'pk',
				'layoutId' => 'integer(11) NOT NULL',
				'tabId' => 'integer(11) NOT NULL',
				'fieldId' => 'integer(11) NOT NULL',
				'required' => 'smallint(1) unsigned NOT NULL DEFAULT \'0\'',
				'sortOrder' => 'smallint(4) DEFAULT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%fieldlayouts}}',
			[
				'id' => 'pk',
				'type' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%fieldlayouttabs}}',
			[
				'id' => 'pk',
				'layoutId' => 'integer(11) NOT NULL',
				'name' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'sortOrder' => 'smallint(4) DEFAULT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%fields}}',
			[
				'id' => 'pk',
				'groupId' => 'integer(11) DEFAULT NULL',
				'name' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'handle' => 'string(64) COLLATE utf8_unicode_ci NOT NULL',
				'context' => 'string(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'global\'',
				'instructions' => 'text COLLATE utf8_unicode_ci',
				'translatable' => 'smallint(1) unsigned NOT NULL DEFAULT \'0\'',
				'type' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'settings' => 'text COLLATE utf8_unicode_ci',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%globalsets}}',
			[
				'id' => 'pk',
				'name' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'handle' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'fieldLayoutId' => 'integer(10) DEFAULT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%info}}',
			[
				'id' => 'pk',
				'version' => 'string(15) COLLATE utf8_unicode_ci NOT NULL',
				'build' => 'integer(11) unsigned NOT NULL',
				'schemaVersion' => 'string(15) COLLATE utf8_unicode_ci NOT NULL',
				'releaseDate' => 'datetime NOT NULL',
				'edition' => 'smallint(1) unsigned NOT NULL DEFAULT \'0\'',
				'siteName' => 'string(100) COLLATE utf8_unicode_ci NOT NULL',
				'siteUrl' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'timezone' => 'string(30) COLLATE utf8_unicode_ci DEFAULT NULL',
				'on' => 'smallint(1) unsigned NOT NULL DEFAULT \'0\'',
				'maintenance' => 'smallint(1) unsigned NOT NULL DEFAULT \'0\'',
				'track' => 'string(40) COLLATE utf8_unicode_ci NOT NULL',
				'fieldVersion' => 'integer(11) NOT NULL DEFAULT \'1\'',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%locales}}',
			[
				'locale' => 'pk',
				'sortOrder' => 'smallint(4) DEFAULT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%matrixblocks}}',
			[
				'id' => 'pk',
				'ownerId' => 'integer(11) NOT NULL',
				'ownerLocale' => 'char(12) COLLATE utf8_unicode_ci DEFAULT NULL',
				'fieldId' => 'integer(11) NOT NULL',
				'typeId' => 'integer(11) DEFAULT NULL',
				'sortOrder' => 'smallint(4) DEFAULT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%matrixblocktypes}}',
			[
				'id' => 'pk',
				'fieldId' => 'integer(11) NOT NULL',
				'fieldLayoutId' => 'integer(11) DEFAULT NULL',
				'name' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'handle' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'sortOrder' => 'smallint(4) DEFAULT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%migrations}}',
			[
				'id' => 'pk',
				'pluginId' => 'integer(11) DEFAULT NULL',
				'version' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'applyTime' => 'datetime NOT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%plugins}}',
			[
				'id' => 'pk',
				'class' => 'string(150) COLLATE utf8_unicode_ci NOT NULL',
				'version' => 'char(15) COLLATE utf8_unicode_ci NOT NULL',
				'enabled' => 'smallint(1) unsigned NOT NULL DEFAULT \'0\'',
				'settings' => 'text COLLATE utf8_unicode_ci',
				'installDate' => 'datetime NOT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%rackspaceaccess}}',
			[
				'id' => 'pk',
				'connectionKey' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'token' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'storageUrl' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'cdnUrl' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%relations}}',
			[
				'id' => 'pk',
				'fieldId' => 'integer(11) NOT NULL',
				'sourceId' => 'integer(11) NOT NULL',
				'sourceLocale' => 'char(12) COLLATE utf8_unicode_ci DEFAULT NULL',
				'targetId' => 'integer(11) NOT NULL',
				'sortOrder' => 'smallint(6) DEFAULT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%routes}}',
			[
				'id' => 'pk',
				'locale' => 'char(12) COLLATE utf8_unicode_ci DEFAULT NULL',
				'urlParts' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'urlPattern' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'template' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'sortOrder' => 'smallint(4) DEFAULT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%searchindex}}',
			[
				'elementId' => 'pk',
				'attribute' => 'pk',
				'fieldId' => 'pk',
				'locale' => 'pk',
				'keywords' => 'text COLLATE utf8_unicode_ci NOT NULL',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%sections}}',
			[
				'id' => 'pk',
				'structureId' => 'integer(11) DEFAULT NULL',
				'name' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'handle' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'type' => 'enum(\'single\',\'channel\',\'structure\') COLLATE utf8_unicode_ci NOT NULL DEFAULT \'channel\'',
				'hasUrls' => 'smallint(1) unsigned NOT NULL DEFAULT \'1\'',
				'template' => 'string(500) COLLATE utf8_unicode_ci DEFAULT NULL',
				'enableVersioning' => 'smallint(1) unsigned NOT NULL DEFAULT \'0\'',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%sections_i18n}}',
			[
				'id' => 'pk',
				'sectionId' => 'integer(11) NOT NULL',
				'locale' => 'char(12) COLLATE utf8_unicode_ci NOT NULL',
				'enabledByDefault' => 'smallint(1) DEFAULT \'1\'',
				'urlFormat' => 'string(255) COLLATE utf8_unicode_ci DEFAULT NULL',
				'nestedUrlFormat' => 'string(255) COLLATE utf8_unicode_ci DEFAULT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%sessions}}',
			[
				'id' => 'pk',
				'userId' => 'integer(11) NOT NULL',
				'token' => 'char(100) COLLATE utf8_unicode_ci NOT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%shunnedmessages}}',
			[
				'id' => 'pk',
				'userId' => 'integer(11) NOT NULL',
				'message' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'expiryDate' => 'datetime DEFAULT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%structureelements}}',
			[
				'id' => 'pk',
				'structureId' => 'integer(11) NOT NULL',
				'elementId' => 'integer(11) DEFAULT NULL',
				'root' => 'integer(10) unsigned DEFAULT NULL',
				'lft' => 'integer(10) unsigned NOT NULL',
				'rgt' => 'integer(10) unsigned NOT NULL',
				'level' => 'smallint(6) unsigned NOT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%structures}}',
			[
				'id' => 'pk',
				'maxLevels' => 'smallint(6) unsigned DEFAULT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%systemsettings}}',
			[
				'id' => 'pk',
				'category' => 'string(15) COLLATE utf8_unicode_ci NOT NULL',
				'settings' => 'text COLLATE utf8_unicode_ci',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%taggroups}}',
			[
				'id' => 'pk',
				'name' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'handle' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'fieldLayoutId' => 'integer(10) DEFAULT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%tags}}',
			[
				'id' => 'pk',
				'groupId' => 'integer(11) NOT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%tasks}}',
			[
				'id' => 'pk',
				'root' => 'integer(11) unsigned DEFAULT NULL',
				'lft' => 'integer(11) unsigned NOT NULL',
				'rgt' => 'integer(11) unsigned NOT NULL',
				'level' => 'smallint(6) unsigned NOT NULL',
				'currentStep' => 'integer(11) unsigned DEFAULT NULL',
				'totalSteps' => 'integer(11) unsigned DEFAULT NULL',
				'status' => 'enum(\'pending\',\'error\',\'running\') COLLATE utf8_unicode_ci DEFAULT NULL',
				'type' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'description' => 'string(255) COLLATE utf8_unicode_ci DEFAULT NULL',
				'settings' => 'text COLLATE utf8_unicode_ci NOT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%templatecachecriteria}}',
			[
				'id' => 'pk',
				'cacheId' => 'integer(11) NOT NULL',
				'type' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'criteria' => 'text COLLATE utf8_unicode_ci NOT NULL',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%templatecacheelements}}',
			[
				'cacheId' => 'integer(11) NOT NULL',
				'elementId' => 'integer(11) NOT NULL',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%templatecaches}}',
			[
				'id' => 'pk',
				'cacheKey' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'locale' => 'char(12) COLLATE utf8_unicode_ci NOT NULL',
				'path' => 'string(255) COLLATE utf8_unicode_ci DEFAULT NULL',
				'expiryDate' => 'datetime NOT NULL',
				'body' => 'mediumtext COLLATE utf8_unicode_ci NOT NULL',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%tokens}}',
			[
				'id' => 'pk',
				'token' => 'char(32) COLLATE utf8_unicode_ci NOT NULL',
				'route' => 'text COLLATE utf8_unicode_ci',
				'usageLimit' => 'smallint(3) unsigned DEFAULT NULL',
				'usageCount' => 'smallint(3) unsigned DEFAULT NULL',
				'expiryDate' => 'datetime NOT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%usergroups}}',
			[
				'id' => 'pk',
				'name' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'handle' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%usergroups_users}}',
			[
				'id' => 'pk',
				'groupId' => 'integer(11) NOT NULL',
				'userId' => 'integer(11) NOT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%userpermissions}}',
			[
				'id' => 'pk',
				'name' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%userpermissions_usergroups}}',
			[
				'id' => 'pk',
				'permissionId' => 'integer(11) NOT NULL',
				'groupId' => 'integer(11) NOT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%userpermissions_users}}',
			[
				'id' => 'pk',
				'permissionId' => 'integer(11) NOT NULL',
				'userId' => 'integer(11) NOT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%users}}',
			[
				'id' => 'pk',
				'username' => 'string(100) COLLATE utf8_unicode_ci NOT NULL',
				'photo' => 'string(100) COLLATE utf8_unicode_ci DEFAULT NULL',
				'firstName' => 'string(100) COLLATE utf8_unicode_ci DEFAULT NULL',
				'lastName' => 'string(100) COLLATE utf8_unicode_ci DEFAULT NULL',
				'email' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'password' => 'char(255) COLLATE utf8_unicode_ci DEFAULT NULL',
				'preferredLocale' => 'char(12) COLLATE utf8_unicode_ci DEFAULT NULL',
				'weekStartDay' => 'smallint(4) NOT NULL DEFAULT \'0\'',
				'admin' => 'smallint(1) unsigned NOT NULL DEFAULT \'0\'',
				'client' => 'smallint(1) NOT NULL',
				'locked' => 'smallint(1) NOT NULL',
				'suspended' => 'smallint(1) NOT NULL',
				'pending' => 'smallint(1) NOT NULL',
				'archived' => 'smallint(1) NOT NULL',
				'lastLoginDate' => 'datetime DEFAULT NULL',
				'lastLoginAttemptIPAddress' => 'string(45) COLLATE utf8_unicode_ci DEFAULT NULL',
				'invalidLoginWindowStart' => 'datetime DEFAULT NULL',
				'invalidLoginCount' => 'smallint(4) unsigned DEFAULT NULL',
				'lastInvalidLoginDate' => 'datetime DEFAULT NULL',
				'lockoutDate' => 'datetime DEFAULT NULL',
				'verificationCode' => 'char(100) COLLATE utf8_unicode_ci DEFAULT NULL',
				'verificationCodeIssuedDate' => 'datetime DEFAULT NULL',
				'unverifiedEmail' => 'string(255) COLLATE utf8_unicode_ci DEFAULT NULL',
				'passwordResetRequired' => 'smallint(1) unsigned NOT NULL DEFAULT \'0\'',
				'lastPasswordChangeDate' => 'datetime DEFAULT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%volumes}}',
			[
				'id' => 'pk',
				'fieldLayoutId' => 'integer(11) DEFAULT NULL',
				'name' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'handle' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'type' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'settings' => 'text COLLATE utf8_unicode_ci',
				'sortOrder' => 'smallint(4) DEFAULT NULL',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);

		$this->createTable(
			'{{%widgets}}',
			[
				'id' => 'pk',
				'userId' => 'integer(11) NOT NULL',
				'type' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				'sortOrder' => 'smallint(4) DEFAULT NULL',
				'settings' => 'text COLLATE utf8_unicode_ci',
				'enabled' => 'smallint(1) DEFAULT \'1\'',
				'dateCreated' => 'datetime NOT NULL',
				'dateUpdated' => 'datetime NOT NULL',
				'uid' => 'char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'0\'',
			],
			null,
			false,
			false
		);
	}

	/**
	 * Creates the indexes.
	 */
	private function _createIndexes()
	{
		$this->createIndex(
			$this->db->getIndexName('{{%assetfolders}}', ['name','parentId','sourceId'], true),
			'{{%assetfolders}}',
			['name','parentId','sourceId'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%assetfolders}}', ['sourceId'], false),
			'{{%assetfolders}}',
			['sourceId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%assetfolders}}', ['parentId'], false),
			'{{%assetfolders}}',
			['parentId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%assetindexdata}}', ['sessionId','sourceId','offset'], true),
			'{{%assetindexdata}}',
			['sessionId','sourceId','offset'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%assetindexdata}}', ['sourceId'], false),
			'{{%assetindexdata}}',
			['sourceId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%assets}}', ['filename','folderId'], true),
			'{{%assets}}',
			['filename','folderId'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%assets}}', ['folderId'], false),
			'{{%assets}}',
			['folderId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%assets}}', ['sourceId'], false),
			'{{%assets}}',
			['sourceId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%assettransformindex}}', ['sourceId','fileId','location'], false),
			'{{%assettransformindex}}',
			['sourceId','fileId','location'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%assettransforms}}', ['name'], true),
			'{{%assettransforms}}',
			['name'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%assettransforms}}', ['handle'], true),
			'{{%assettransforms}}',
			['handle'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%categories}}', ['groupId'], false),
			'{{%categories}}',
			['groupId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%categorygroups}}', ['name'], true),
			'{{%categorygroups}}',
			['name'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%categorygroups}}', ['handle'], true),
			'{{%categorygroups}}',
			['handle'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%categorygroups}}', ['structureId'], false),
			'{{%categorygroups}}',
			['structureId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%categorygroups}}', ['fieldLayoutId'], false),
			'{{%categorygroups}}',
			['fieldLayoutId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%categorygroups_i18n}}', ['groupId','locale'], true),
			'{{%categorygroups_i18n}}',
			['groupId','locale'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%categorygroups_i18n}}', ['locale'], false),
			'{{%categorygroups_i18n}}',
			['locale'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%content}}', ['elementId','locale'], true),
			'{{%content}}',
			['elementId','locale'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%content}}', ['locale'], false),
			'{{%content}}',
			['locale'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%content}}', ['title'], false),
			'{{%content}}',
			['title'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%deprecationerrors}}', ['key','fingerprint'], true),
			'{{%deprecationerrors}}',
			['key','fingerprint'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%elements}}', ['type'], false),
			'{{%elements}}',
			['type'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%elements}}', ['enabled'], false),
			'{{%elements}}',
			['enabled'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%elements}}', ['archived','dateCreated'], false),
			'{{%elements}}',
			['archived','dateCreated'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%elements_i18n}}', ['elementId','locale'], true),
			'{{%elements_i18n}}',
			['elementId','locale'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%elements_i18n}}', ['uri','locale'], true),
			'{{%elements_i18n}}',
			['uri','locale'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%elements_i18n}}', ['locale'], false),
			'{{%elements_i18n}}',
			['locale'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%elements_i18n}}', ['slug','locale'], false),
			'{{%elements_i18n}}',
			['slug','locale'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%elements_i18n}}', ['enabled'], false),
			'{{%elements_i18n}}',
			['enabled'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%emailmessages}}', ['key','locale'], true),
			'{{%emailmessages}}',
			['key','locale'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%emailmessages}}', ['locale'], false),
			'{{%emailmessages}}',
			['locale'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%entries}}', ['postDate'], false),
			'{{%entries}}',
			['postDate'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%entries}}', ['expiryDate'], false),
			'{{%entries}}',
			['expiryDate'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%entries}}', ['authorId'], false),
			'{{%entries}}',
			['authorId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%entries}}', ['sectionId'], false),
			'{{%entries}}',
			['sectionId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%entries}}', ['typeId'], false),
			'{{%entries}}',
			['typeId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%entrydrafts}}', ['sectionId'], false),
			'{{%entrydrafts}}',
			['sectionId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%entrydrafts}}', ['entryId','locale'], false),
			'{{%entrydrafts}}',
			['entryId','locale'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%entrydrafts}}', ['locale'], false),
			'{{%entrydrafts}}',
			['locale'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%entrydrafts}}', ['creatorId'], false),
			'{{%entrydrafts}}',
			['creatorId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%entrytypes}}', ['name','sectionId'], true),
			'{{%entrytypes}}',
			['name','sectionId'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%entrytypes}}', ['handle','sectionId'], true),
			'{{%entrytypes}}',
			['handle','sectionId'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%entrytypes}}', ['sectionId'], false),
			'{{%entrytypes}}',
			['sectionId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%entrytypes}}', ['fieldLayoutId'], false),
			'{{%entrytypes}}',
			['fieldLayoutId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%entryversions}}', ['sectionId'], false),
			'{{%entryversions}}',
			['sectionId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%entryversions}}', ['entryId','locale'], false),
			'{{%entryversions}}',
			['entryId','locale'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%entryversions}}', ['locale'], false),
			'{{%entryversions}}',
			['locale'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%entryversions}}', ['creatorId'], false),
			'{{%entryversions}}',
			['creatorId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%fieldgroups}}', ['name'], true),
			'{{%fieldgroups}}',
			['name'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%fieldlayoutfields}}', ['layoutId','fieldId'], true),
			'{{%fieldlayoutfields}}',
			['layoutId','fieldId'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%fieldlayoutfields}}', ['sortOrder'], false),
			'{{%fieldlayoutfields}}',
			['sortOrder'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%fieldlayoutfields}}', ['tabId'], false),
			'{{%fieldlayoutfields}}',
			['tabId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%fieldlayoutfields}}', ['fieldId'], false),
			'{{%fieldlayoutfields}}',
			['fieldId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%fieldlayouts}}', ['type'], false),
			'{{%fieldlayouts}}',
			['type'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%fieldlayouttabs}}', ['sortOrder'], false),
			'{{%fieldlayouttabs}}',
			['sortOrder'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%fieldlayouttabs}}', ['layoutId'], false),
			'{{%fieldlayouttabs}}',
			['layoutId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%fields}}', ['handle','context'], true),
			'{{%fields}}',
			['handle','context'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%fields}}', ['groupId'], false),
			'{{%fields}}',
			['groupId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%fields}}', ['context'], false),
			'{{%fields}}',
			['context'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%globalsets}}', ['name'], true),
			'{{%globalsets}}',
			['name'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%globalsets}}', ['handle'], true),
			'{{%globalsets}}',
			['handle'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%globalsets}}', ['fieldLayoutId'], false),
			'{{%globalsets}}',
			['fieldLayoutId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%locales}}', ['sortOrder'], false),
			'{{%locales}}',
			['sortOrder'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%matrixblocks}}', ['ownerId'], false),
			'{{%matrixblocks}}',
			['ownerId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%matrixblocks}}', ['fieldId'], false),
			'{{%matrixblocks}}',
			['fieldId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%matrixblocks}}', ['typeId'], false),
			'{{%matrixblocks}}',
			['typeId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%matrixblocks}}', ['sortOrder'], false),
			'{{%matrixblocks}}',
			['sortOrder'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%matrixblocks}}', ['ownerLocale'], false),
			'{{%matrixblocks}}',
			['ownerLocale'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%matrixblocktypes}}', ['name','fieldId'], true),
			'{{%matrixblocktypes}}',
			['name','fieldId'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%matrixblocktypes}}', ['handle','fieldId'], true),
			'{{%matrixblocktypes}}',
			['handle','fieldId'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%matrixblocktypes}}', ['fieldId'], false),
			'{{%matrixblocktypes}}',
			['fieldId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%matrixblocktypes}}', ['fieldLayoutId'], false),
			'{{%matrixblocktypes}}',
			['fieldLayoutId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%migrations}}', ['version'], true),
			'{{%migrations}}',
			['version'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%migrations}}', ['pluginId'], false),
			'{{%migrations}}',
			['pluginId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%rackspaceaccess}}', ['connectionKey'], true),
			'{{%rackspaceaccess}}',
			['connectionKey'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%relations}}', ['fieldId','sourceId','sourceLocale','targetId'], true),
			'{{%relations}}',
			['fieldId','sourceId','sourceLocale','targetId'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%relations}}', ['sourceId'], false),
			'{{%relations}}',
			['sourceId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%relations}}', ['targetId'], false),
			'{{%relations}}',
			['targetId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%relations}}', ['sourceLocale'], false),
			'{{%relations}}',
			['sourceLocale'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%routes}}', ['urlPattern'], true),
			'{{%routes}}',
			['urlPattern'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%routes}}', ['locale'], false),
			'{{%routes}}',
			['locale'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%searchindex}}', ['keywords'], false),
			'{{%searchindex}}',
			['keywords'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%sections}}', ['handle'], true),
			'{{%sections}}',
			['handle'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%sections}}', ['name'], true),
			'{{%sections}}',
			['name'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%sections}}', ['structureId'], false),
			'{{%sections}}',
			['structureId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%sections_i18n}}', ['sectionId','locale'], true),
			'{{%sections_i18n}}',
			['sectionId','locale'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%sections_i18n}}', ['locale'], false),
			'{{%sections_i18n}}',
			['locale'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%sessions}}', ['uid'], false),
			'{{%sessions}}',
			['uid'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%sessions}}', ['token'], false),
			'{{%sessions}}',
			['token'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%sessions}}', ['dateUpdated'], false),
			'{{%sessions}}',
			['dateUpdated'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%sessions}}', ['userId'], false),
			'{{%sessions}}',
			['userId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%shunnedmessages}}', ['userId','message'], true),
			'{{%shunnedmessages}}',
			['userId','message'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%structureelements}}', ['structureId','elementId'], true),
			'{{%structureelements}}',
			['structureId','elementId'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%structureelements}}', ['root'], false),
			'{{%structureelements}}',
			['root'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%structureelements}}', ['lft'], false),
			'{{%structureelements}}',
			['lft'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%structureelements}}', ['rgt'], false),
			'{{%structureelements}}',
			['rgt'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%structureelements}}', ['level'], false),
			'{{%structureelements}}',
			['level'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%structureelements}}', ['elementId'], false),
			'{{%structureelements}}',
			['elementId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%systemsettings}}', ['category'], true),
			'{{%systemsettings}}',
			['category'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%taggroups}}', ['name'], true),
			'{{%taggroups}}',
			['name'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%taggroups}}', ['handle'], true),
			'{{%taggroups}}',
			['handle'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%tags}}', ['groupId'], false),
			'{{%tags}}',
			['groupId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%tasks}}', ['root'], false),
			'{{%tasks}}',
			['root'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%tasks}}', ['lft'], false),
			'{{%tasks}}',
			['lft'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%tasks}}', ['rgt'], false),
			'{{%tasks}}',
			['rgt'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%tasks}}', ['level'], false),
			'{{%tasks}}',
			['level'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%templatecachecriteria}}', ['cacheId'], false),
			'{{%templatecachecriteria}}',
			['cacheId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%templatecachecriteria}}', ['type'], false),
			'{{%templatecachecriteria}}',
			['type'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%templatecacheelements}}', ['cacheId'], false),
			'{{%templatecacheelements}}',
			['cacheId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%templatecacheelements}}', ['elementId'], false),
			'{{%templatecacheelements}}',
			['elementId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%templatecaches}}', ['expiryDate','cacheKey','locale','path'], false),
			'{{%templatecaches}}',
			['expiryDate','cacheKey','locale','path'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%templatecaches}}', ['locale'], false),
			'{{%templatecaches}}',
			['locale'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%tokens}}', ['token'], true),
			'{{%tokens}}',
			['token'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%tokens}}', ['expiryDate'], false),
			'{{%tokens}}',
			['expiryDate'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%usergroups_users}}', ['groupId','userId'], true),
			'{{%usergroups_users}}',
			['groupId','userId'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%usergroups_users}}', ['userId'], false),
			'{{%usergroups_users}}',
			['userId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%userpermissions}}', ['name'], true),
			'{{%userpermissions}}',
			['name'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%userpermissions_usergroups}}', ['permissionId','groupId'], true),
			'{{%userpermissions_usergroups}}',
			['permissionId','groupId'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%userpermissions_usergroups}}', ['groupId'], false),
			'{{%userpermissions_usergroups}}',
			['groupId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%userpermissions_users}}', ['permissionId','userId'], true),
			'{{%userpermissions_users}}',
			['permissionId','userId'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%userpermissions_users}}', ['userId'], false),
			'{{%userpermissions_users}}',
			['userId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%users}}', ['username'], true),
			'{{%users}}',
			['username'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%users}}', ['email'], true),
			'{{%users}}',
			['email'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%users}}', ['uid'], false),
			'{{%users}}',
			['uid'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%users}}', ['verificationCode'], false),
			'{{%users}}',
			['verificationCode'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%users}}', ['preferredLocale'], false),
			'{{%users}}',
			['preferredLocale'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%volumes}}', ['name'], true),
			'{{%volumes}}',
			['name'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%volumes}}', ['handle'], true),
			'{{%volumes}}',
			['handle'],
			true
		);

		$this->createIndex(
			$this->db->getIndexName('{{%volumes}}', ['fieldLayoutId'], false),
			'{{%volumes}}',
			['fieldLayoutId'],
			false
		);

		$this->createIndex(
			$this->db->getIndexName('{{%widgets}}', ['userId'], false),
			'{{%widgets}}',
			['userId'],
			false
		);
	}

	/**
	 * Adds the foreign keys.
	 */
	private function _addForeignKeys()
	{
		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%assetfolders}}', ['sourceId']),
			'{{%assetfolders}}',
			['sourceId'],
			'{{%volumes}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%assetfolders}}', ['parentId']),
			'{{%assetfolders}}',
			['parentId'],
			'{{%assetfolders}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%assetindexdata}}', ['sourceId']),
			'{{%assetindexdata}}',
			['sourceId'],
			'{{%volumes}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%assets}}', ['sourceId']),
			'{{%assets}}',
			['sourceId'],
			'{{%volumes}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%assets}}', ['folderId']),
			'{{%assets}}',
			['folderId'],
			'{{%assetfolders}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%assets}}', ['id']),
			'{{%assets}}',
			['id'],
			'{{%elements}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%categories}}', ['groupId']),
			'{{%categories}}',
			['groupId'],
			'{{%categorygroups}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%categories}}', ['id']),
			'{{%categories}}',
			['id'],
			'{{%elements}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%categorygroups}}', ['fieldLayoutId']),
			'{{%categorygroups}}',
			['fieldLayoutId'],
			'{{%fieldlayouts}}',
			['id'],
			'SET NULL',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%categorygroups}}', ['structureId']),
			'{{%categorygroups}}',
			['structureId'],
			'{{%structures}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%categorygroups_i18n}}', ['locale']),
			'{{%categorygroups_i18n}}',
			['locale'],
			'{{%locales}}',
			['locale'],
			'CASCADE',
			'CASCADE'
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%categorygroups_i18n}}', ['groupId']),
			'{{%categorygroups_i18n}}',
			['groupId'],
			'{{%categorygroups}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%content}}', ['elementId']),
			'{{%content}}',
			['elementId'],
			'{{%elements}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%content}}', ['locale']),
			'{{%content}}',
			['locale'],
			'{{%locales}}',
			['locale'],
			'CASCADE',
			'CASCADE'
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%elements_i18n}}', ['elementId']),
			'{{%elements_i18n}}',
			['elementId'],
			'{{%elements}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%elements_i18n}}', ['locale']),
			'{{%elements_i18n}}',
			['locale'],
			'{{%locales}}',
			['locale'],
			'CASCADE',
			'CASCADE'
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%emailmessages}}', ['locale']),
			'{{%emailmessages}}',
			['locale'],
			'{{%locales}}',
			['locale'],
			'CASCADE',
			'CASCADE'
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%entries}}', ['authorId']),
			'{{%entries}}',
			['authorId'],
			'{{%users}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%entries}}', ['id']),
			'{{%entries}}',
			['id'],
			'{{%elements}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%entries}}', ['sectionId']),
			'{{%entries}}',
			['sectionId'],
			'{{%sections}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%entries}}', ['typeId']),
			'{{%entries}}',
			['typeId'],
			'{{%entrytypes}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%entrydrafts}}', ['creatorId']),
			'{{%entrydrafts}}',
			['creatorId'],
			'{{%users}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%entrydrafts}}', ['entryId']),
			'{{%entrydrafts}}',
			['entryId'],
			'{{%entries}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%entrydrafts}}', ['locale']),
			'{{%entrydrafts}}',
			['locale'],
			'{{%locales}}',
			['locale'],
			'CASCADE',
			'CASCADE'
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%entrydrafts}}', ['sectionId']),
			'{{%entrydrafts}}',
			['sectionId'],
			'{{%sections}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%entrytypes}}', ['fieldLayoutId']),
			'{{%entrytypes}}',
			['fieldLayoutId'],
			'{{%fieldlayouts}}',
			['id'],
			'SET NULL',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%entrytypes}}', ['sectionId']),
			'{{%entrytypes}}',
			['sectionId'],
			'{{%sections}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%entryversions}}', ['creatorId']),
			'{{%entryversions}}',
			['creatorId'],
			'{{%users}}',
			['id'],
			'SET NULL',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%entryversions}}', ['entryId']),
			'{{%entryversions}}',
			['entryId'],
			'{{%entries}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%entryversions}}', ['locale']),
			'{{%entryversions}}',
			['locale'],
			'{{%locales}}',
			['locale'],
			'CASCADE',
			'CASCADE'
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%entryversions}}', ['sectionId']),
			'{{%entryversions}}',
			['sectionId'],
			'{{%sections}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%fieldlayoutfields}}', ['tabId']),
			'{{%fieldlayoutfields}}',
			['tabId'],
			'{{%fieldlayouttabs}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%fieldlayoutfields}}', ['fieldId']),
			'{{%fieldlayoutfields}}',
			['fieldId'],
			'{{%fields}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%fieldlayoutfields}}', ['layoutId']),
			'{{%fieldlayoutfields}}',
			['layoutId'],
			'{{%fieldlayouts}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%fieldlayouttabs}}', ['layoutId']),
			'{{%fieldlayouttabs}}',
			['layoutId'],
			'{{%fieldlayouts}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%fields}}', ['groupId']),
			'{{%fields}}',
			['groupId'],
			'{{%fieldgroups}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%globalsets}}', ['fieldLayoutId']),
			'{{%globalsets}}',
			['fieldLayoutId'],
			'{{%fieldlayouts}}',
			['id'],
			'SET NULL',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%globalsets}}', ['id']),
			'{{%globalsets}}',
			['id'],
			'{{%elements}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%matrixblocks}}', ['ownerLocale']),
			'{{%matrixblocks}}',
			['ownerLocale'],
			'{{%locales}}',
			['locale'],
			'CASCADE',
			'CASCADE'
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%matrixblocks}}', ['fieldId']),
			'{{%matrixblocks}}',
			['fieldId'],
			'{{%fields}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%matrixblocks}}', ['id']),
			'{{%matrixblocks}}',
			['id'],
			'{{%elements}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%matrixblocks}}', ['ownerId']),
			'{{%matrixblocks}}',
			['ownerId'],
			'{{%elements}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%matrixblocks}}', ['typeId']),
			'{{%matrixblocks}}',
			['typeId'],
			'{{%matrixblocktypes}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%matrixblocktypes}}', ['fieldLayoutId']),
			'{{%matrixblocktypes}}',
			['fieldLayoutId'],
			'{{%fieldlayouts}}',
			['id'],
			'SET NULL',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%matrixblocktypes}}', ['fieldId']),
			'{{%matrixblocktypes}}',
			['fieldId'],
			'{{%fields}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%migrations}}', ['pluginId']),
			'{{%migrations}}',
			['pluginId'],
			'{{%plugins}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%relations}}', ['fieldId']),
			'{{%relations}}',
			['fieldId'],
			'{{%fields}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%relations}}', ['sourceId']),
			'{{%relations}}',
			['sourceId'],
			'{{%elements}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%relations}}', ['sourceLocale']),
			'{{%relations}}',
			['sourceLocale'],
			'{{%locales}}',
			['locale'],
			'CASCADE',
			'CASCADE'
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%relations}}', ['targetId']),
			'{{%relations}}',
			['targetId'],
			'{{%elements}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%routes}}', ['locale']),
			'{{%routes}}',
			['locale'],
			'{{%locales}}',
			['locale'],
			'CASCADE',
			'CASCADE'
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%sections}}', ['structureId']),
			'{{%sections}}',
			['structureId'],
			'{{%structures}}',
			['id'],
			'SET NULL',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%sections_i18n}}', ['locale']),
			'{{%sections_i18n}}',
			['locale'],
			'{{%locales}}',
			['locale'],
			'CASCADE',
			'CASCADE'
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%sections_i18n}}', ['sectionId']),
			'{{%sections_i18n}}',
			['sectionId'],
			'{{%sections}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%sessions}}', ['userId']),
			'{{%sessions}}',
			['userId'],
			'{{%users}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%shunnedmessages}}', ['userId']),
			'{{%shunnedmessages}}',
			['userId'],
			'{{%users}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%structureelements}}', ['elementId']),
			'{{%structureelements}}',
			['elementId'],
			'{{%elements}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%structureelements}}', ['structureId']),
			'{{%structureelements}}',
			['structureId'],
			'{{%structures}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%tags}}', ['groupId']),
			'{{%tags}}',
			['groupId'],
			'{{%taggroups}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%tags}}', ['id']),
			'{{%tags}}',
			['id'],
			'{{%elements}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%templatecachecriteria}}', ['cacheId']),
			'{{%templatecachecriteria}}',
			['cacheId'],
			'{{%templatecaches}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%templatecacheelements}}', ['elementId']),
			'{{%templatecacheelements}}',
			['elementId'],
			'{{%elements}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%templatecacheelements}}', ['cacheId']),
			'{{%templatecacheelements}}',
			['cacheId'],
			'{{%templatecaches}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%templatecaches}}', ['locale']),
			'{{%templatecaches}}',
			['locale'],
			'{{%locales}}',
			['locale'],
			'CASCADE',
			'CASCADE'
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%usergroups_users}}', ['groupId']),
			'{{%usergroups_users}}',
			['groupId'],
			'{{%usergroups}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%usergroups_users}}', ['userId']),
			'{{%usergroups_users}}',
			['userId'],
			'{{%users}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%userpermissions_usergroups}}', ['groupId']),
			'{{%userpermissions_usergroups}}',
			['groupId'],
			'{{%usergroups}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%userpermissions_usergroups}}', ['permissionId']),
			'{{%userpermissions_usergroups}}',
			['permissionId'],
			'{{%userpermissions}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%userpermissions_users}}', ['permissionId']),
			'{{%userpermissions_users}}',
			['permissionId'],
			'{{%userpermissions}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%userpermissions_users}}', ['userId']),
			'{{%userpermissions_users}}',
			['userId'],
			'{{%users}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%users}}', ['id']),
			'{{%users}}',
			['id'],
			'{{%elements}}',
			['id'],
			'CASCADE',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%users}}', ['preferredLocale']),
			'{{%users}}',
			['preferredLocale'],
			'{{%locales}}',
			['locale'],
			'SET NULL',
			'CASCADE'
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%volumes}}', ['fieldLayoutId']),
			'{{%volumes}}',
			['fieldLayoutId'],
			'{{%fieldlayouts}}',
			['id'],
			'SET NULL',
			null
		);

		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%widgets}}', ['userId']),
			'{{%widgets}}',
			['userId'],
			'{{%users}}',
			['id'],
			'CASCADE',
			null
		);
	}
}
