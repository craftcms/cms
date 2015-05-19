<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\migrations;

use Craft;
use craft\app\elements\User;
use craft\app\enums\EmailerType;
use craft\app\db\InstallMigration;
use craft\app\helpers\StringHelper;
use craft\app\models\Info;

/**
 * Installation Migration
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Install extends InstallMigration
{
	// Properties
	// =========================================================================

	/**
	 * @var string The site name
	 */
	public $siteName;

	/**
	 * @var string The site URL
	 */
	public $siteUrl;

	/**
	 * @var string The site locale
	 */
	public $locale;

	/**
	 * @var string The admin user’s username
	 */
	public $username;

	/**
	 * @var string The admin user’s password
	 */
	public $password;

	/**
	 * @var string The admin user’s email
	 */
	public $email;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function safeUp()
	{
		parent::safeUp();

		// Add the FULLTEXT index on searchindex.keywords
		// TODO: MySQL specific
		$this->db->createCommand(
			'CREATE FULLTEXT INDEX ' .
			$this->db->quoteTableName($this->db->getIndexName('{{%searchindex}}', 'keywords')).' ON ' .
			$this->db->quoteTableName('{{%searchindex}}').' ' .
			'('.$this->db->quoteColumnName('keywords').')'
		)->execute();

		// Add the site locale
		$this->insert(
			'{{%locales}}',
			[
				'locale'    => $this->locale,
				'sortOrder' => 1
			]
		);

		// Populate the info table
		echo "    > populate the info table ...";
		Craft::$app->saveInfo(new Info([
			'version'       => Craft::$app->version,
			'build'         => Craft::$app->build,
			'schemaVersion' => Craft::$app->schemaVersion,
			'releaseDate'   => Craft::$app->releaseDate,
			'edition'       => '0',
			'siteName'      => $this->siteName,
			'siteUrl'       => $this->siteUrl,
			'on'            => '1',
			'maintenance'   => '0',
			'track'         => Craft::$app->track,
			'fieldVersion'  => StringHelper::randomString(12),
		]));
		echo " done\n";

		// Craft, you are installed now.
		Craft::$app->setIsInstalled();

		// Set the language to the desired locale
		Craft::$app->language = $this->locale;

		// Save the first user
		echo "    > save the first user ...";
		$user = new User([
			'username'    => $this->username,
			'newPassword' => $this->password,
			'email'       => $this->email,
			'admin'       => true
		]);
		Craft::$app->getUsers()->saveUser($user);
		echo " done\n";

		// Log them in
		if (!Craft::$app->getRequest()->getIsConsoleRequest())
		{
			Craft::$app->getUser()->login($user);
		}

		// Save the default email settings
		echo "    > save the email settings ...";
		Craft::$app->getSystemSettings()->saveSettings('email', [
			'protocol'     => EmailerType::Php,
			'emailAddress' => $this->email,
			'senderName'   => $this->siteName
		]);
		echo " done\n";
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	protected function defineSchema()
	{
		return [
			'{{%assetindexdata}}' => [
				'columns' => [
					'sessionId' => 'string(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'\'',
					'volumeId' => 'integer(10) NOT NULL',
					'offset' => 'integer(10) NOT NULL',
					'uri' => 'string(255) COLLATE utf8_unicode_ci DEFAULT NULL',
					'size' => 'integer(10) DEFAULT NULL',
					'timestamp' => 'datetime DEFAULT NULL',
					'recordId' => 'integer(10) DEFAULT NULL',
				],
				'indexes' => [
					['sessionId,volumeId,offset', true],
					['volumeId', false],
				],
				'foreignKeys' => [
					['volumeId', '{{%volumes}}', 'id', 'CASCADE', null],
				],
			],
			'{{%assets}}' => [
				'columns' => [
					'volumeId' => 'integer(11) DEFAULT NULL',
					'folderId' => 'integer(11) NOT NULL',
					'filename' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
					'kind' => 'string(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'unknown\'',
					'width' => 'smallint(6) unsigned DEFAULT NULL',
					'height' => 'smallint(6) unsigned DEFAULT NULL',
					'size' => 'integer(11) unsigned DEFAULT NULL',
					'dateModified' => 'datetime DEFAULT NULL',
				],
				'indexes' => [
					['filename,folderId', true],
					['folderId', false],
					['volumeId', false],
				],
				'foreignKeys' => [
					['volumeId', '{{%volumes}}', 'id', 'CASCADE', null],
					['folderId', '{{%volumefolders}}', 'id', 'CASCADE', null],
					['id', '{{%elements}}', 'id', 'CASCADE', null],
				],
			],
			'{{%assettransformindex}}' => [
				'columns' => [
					'fileId' => 'integer(11) NOT NULL',
					'filename' => 'string(255) COLLATE utf8_unicode_ci DEFAULT NULL',
					'format' => 'string(255) COLLATE utf8_unicode_ci DEFAULT NULL',
					'location' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
					'volumeId' => 'integer(11) DEFAULT NULL',
					'fileExists' => 'smallint(1) unsigned NOT NULL DEFAULT \'0\'',
					'inProgress' => 'smallint(1) unsigned NOT NULL DEFAULT \'0\'',
					'dateIndexed' => 'datetime DEFAULT NULL',
				],
				'indexes' => [
					['volumeId,fileId,location', false],
				],
			],
			'{{%assettransforms}}' => [
				'columns' => [
					'name' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
					'handle' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
					'mode' => 'enum(\'stretch\',\'fit\',\'crop\') COLLATE utf8_unicode_ci NOT NULL DEFAULT \'crop\'',
					'position' => 'enum(\'top-left\',\'top-center\',\'top-right\',\'center-left\',\'center-center\',\'center-right\',\'bottom-left\',\'bottom-center\',\'bottom-right\') COLLATE utf8_unicode_ci NOT NULL DEFAULT \'center-center\'',
					'height' => 'integer(10) DEFAULT NULL',
					'width' => 'integer(10) DEFAULT NULL',
					'format' => 'string(255) COLLATE utf8_unicode_ci DEFAULT NULL',
					'quality' => 'integer(11) DEFAULT NULL',
					'dimensionChangeTime' => 'datetime DEFAULT NULL',
				],
				'indexes' => [
					['name', true],
					['handle', true],
				],
			],
			'{{%categories}}' => [
				'columns' => [
					'groupId' => 'integer(11) NOT NULL',
				],
				'indexes' => [
					['groupId', false],
				],
				'foreignKeys' => [
					['groupId', '{{%categorygroups}}', 'id', 'CASCADE', null],
					['id', '{{%elements}}', 'id', 'CASCADE', null],
				],
			],
			'{{%categorygroups}}' => [
				'columns' => [
					'structureId' => 'integer(11) NOT NULL',
					'fieldLayoutId' => 'integer(11) DEFAULT NULL',
					'name' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
					'handle' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
					'hasUrls' => 'smallint(1) NOT NULL DEFAULT \'1\'',
					'template' => 'string(500) COLLATE utf8_unicode_ci DEFAULT NULL',
				],
				'indexes' => [
					['name', true],
					['handle', true],
					['structureId', false],
					['fieldLayoutId', false],
				],
				'foreignKeys' => [
					['fieldLayoutId', '{{%fieldlayouts}}', 'id', 'SET NULL', null],
					['structureId', '{{%structures}}', 'id', 'CASCADE', null],
				],
			],
			'{{%categorygroups_i18n}}' => [
				'columns' => [
					'groupId' => 'integer(11) NOT NULL',
					'locale' => 'char(12) COLLATE utf8_unicode_ci NOT NULL',
					'urlFormat' => 'string(255) COLLATE utf8_unicode_ci DEFAULT NULL',
					'nestedUrlFormat' => 'string(255) COLLATE utf8_unicode_ci DEFAULT NULL',
				],
				'indexes' => [
					['groupId,locale', true],
					['locale', false],
				],
				'foreignKeys' => [
					['locale', '{{%locales}}', 'locale', 'CASCADE', 'CASCADE'],
					['groupId', '{{%categorygroups}}', 'id', 'CASCADE', null],
				],
			],
			'{{%content}}' => [
				'columns' => [
					'elementId' => 'integer(11) NOT NULL',
					'locale' => 'char(12) COLLATE utf8_unicode_ci NOT NULL',
					'title' => 'string(255) COLLATE utf8_unicode_ci DEFAULT NULL',
					'field_heading' => 'string(255) COLLATE utf8_unicode_ci DEFAULT NULL',
					'field_siteIntro' => 'text COLLATE utf8_unicode_ci',
					'field_body' => 'text COLLATE utf8_unicode_ci',
					'field_description' => 'text COLLATE utf8_unicode_ci',
					'field_metaDescription' => 'text COLLATE utf8_unicode_ci',
					'field_linkColor' => 'char(7) COLLATE utf8_unicode_ci DEFAULT NULL',
				],
				'indexes' => [
					['elementId,locale', true],
					['locale', false],
					['title', false],
				],
				'foreignKeys' => [
					['elementId', '{{%elements}}', 'id', 'CASCADE', null],
					['locale', '{{%locales}}', 'locale', 'CASCADE', 'CASCADE'],
				],
			],
			'{{%deprecationerrors}}' => [
				'columns' => [
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
				],
				'indexes' => [
					['key,fingerprint', true],
				],
			],
			'{{%elements}}' => [
				'columns' => [
					'type' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
					'enabled' => 'smallint(1) unsigned NOT NULL DEFAULT \'1\'',
					'archived' => 'smallint(1) unsigned NOT NULL DEFAULT \'0\'',
				],
				'indexes' => [
					['type', false],
					['enabled', false],
					['archived,dateCreated', false],
				],
			],
			'{{%elements_i18n}}' => [
				'columns' => [
					'elementId' => 'integer(11) NOT NULL',
					'locale' => 'char(12) COLLATE utf8_unicode_ci NOT NULL',
					'slug' => 'string(255) COLLATE utf8_unicode_ci DEFAULT NULL',
					'uri' => 'string(255) COLLATE utf8_unicode_ci DEFAULT NULL',
					'enabled' => 'smallint(1) DEFAULT \'1\'',
				],
				'indexes' => [
					['elementId,locale', true],
					['uri,locale', true],
					['locale', false],
					['slug,locale', false],
					['enabled', false],
				],
				'foreignKeys' => [
					['elementId', '{{%elements}}', 'id', 'CASCADE', null],
					['locale', '{{%locales}}', 'locale', 'CASCADE', 'CASCADE'],
				],
			],
			'{{%emailmessages}}' => [
				'columns' => [
					'key' => 'char(150) COLLATE utf8_unicode_ci NOT NULL',
					'locale' => 'char(12) COLLATE utf8_unicode_ci NOT NULL',
					'subject' => 'string(1000) COLLATE utf8_unicode_ci NOT NULL',
					'body' => 'text COLLATE utf8_unicode_ci NOT NULL',
				],
				'indexes' => [
					['key,locale', true],
					['locale', false],
				],
				'foreignKeys' => [
					['locale', '{{%locales}}', 'locale', 'CASCADE', 'CASCADE'],
				],
			],
			'{{%entries}}' => [
				'columns' => [
					'sectionId' => 'integer(11) NOT NULL',
					'typeId' => 'integer(11) DEFAULT NULL',
					'authorId' => 'integer(11) DEFAULT NULL',
					'postDate' => 'datetime DEFAULT NULL',
					'expiryDate' => 'datetime DEFAULT NULL',
				],
				'indexes' => [
					['postDate', false],
					['expiryDate', false],
					['authorId', false],
					['sectionId', false],
					['typeId', false],
				],
				'foreignKeys' => [
					['authorId', '{{%users}}', 'id', 'CASCADE', null],
					['id', '{{%elements}}', 'id', 'CASCADE', null],
					['sectionId', '{{%sections}}', 'id', 'CASCADE', null],
					['typeId', '{{%entrytypes}}', 'id', 'CASCADE', null],
				],
			],
			'{{%entrydrafts}}' => [
				'columns' => [
					'entryId' => 'integer(11) NOT NULL',
					'sectionId' => 'integer(11) NOT NULL',
					'creatorId' => 'integer(11) NOT NULL',
					'locale' => 'char(12) COLLATE utf8_unicode_ci NOT NULL',
					'name' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
					'notes' => 'text COLLATE utf8_unicode_ci',
					'data' => 'mediumtext COLLATE utf8_unicode_ci NOT NULL',
				],
				'indexes' => [
					['sectionId', false],
					['entryId,locale', false],
					['locale', false],
					['creatorId', false],
				],
				'foreignKeys' => [
					['creatorId', '{{%users}}', 'id', 'CASCADE', null],
					['entryId', '{{%entries}}', 'id', 'CASCADE', null],
					['locale', '{{%locales}}', 'locale', 'CASCADE', 'CASCADE'],
					['sectionId', '{{%sections}}', 'id', 'CASCADE', null],
				],
			],
			'{{%entrytypes}}' => [
				'columns' => [
					'sectionId' => 'integer(11) NOT NULL',
					'fieldLayoutId' => 'integer(11) DEFAULT NULL',
					'name' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
					'handle' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
					'hasTitleField' => 'smallint(1) NOT NULL DEFAULT \'1\'',
					'titleLabel' => 'string(255) COLLATE utf8_unicode_ci DEFAULT NULL',
					'titleFormat' => 'string(255) COLLATE utf8_unicode_ci DEFAULT NULL',
					'sortOrder' => 'smallint(4) DEFAULT NULL',
				],
				'indexes' => [
					['name,sectionId', true],
					['handle,sectionId', true],
					['sectionId', false],
					['fieldLayoutId', false],
				],
				'foreignKeys' => [
					['fieldLayoutId', '{{%fieldlayouts}}', 'id', 'SET NULL', null],
					['sectionId', '{{%sections}}', 'id', 'CASCADE', null],
				],
			],
			'{{%entryversions}}' => [
				'columns' => [
					'entryId' => 'integer(11) NOT NULL',
					'sectionId' => 'integer(11) NOT NULL',
					'creatorId' => 'integer(11) DEFAULT NULL',
					'locale' => 'char(12) COLLATE utf8_unicode_ci NOT NULL',
					'num' => 'smallint(6) unsigned NOT NULL',
					'notes' => 'text COLLATE utf8_unicode_ci',
					'data' => 'mediumtext COLLATE utf8_unicode_ci NOT NULL',
				],
				'indexes' => [
					['sectionId', false],
					['entryId,locale', false],
					['locale', false],
					['creatorId', false],
				],
				'foreignKeys' => [
					['creatorId', '{{%users}}', 'id', 'SET NULL', null],
					['entryId', '{{%entries}}', 'id', 'CASCADE', null],
					['locale', '{{%locales}}', 'locale', 'CASCADE', 'CASCADE'],
					['sectionId', '{{%sections}}', 'id', 'CASCADE', null],
				],
			],
			'{{%fieldgroups}}' => [
				'columns' => [
					'name' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				],
				'indexes' => [
					['name', true],
				],
			],
			'{{%fieldlayoutfields}}' => [
				'columns' => [
					'layoutId' => 'integer(11) NOT NULL',
					'tabId' => 'integer(11) NOT NULL',
					'fieldId' => 'integer(11) NOT NULL',
					'required' => 'smallint(1) unsigned NOT NULL DEFAULT \'0\'',
					'sortOrder' => 'smallint(4) DEFAULT NULL',
				],
				'indexes' => [
					['layoutId,fieldId', true],
					['sortOrder', false],
					['tabId', false],
					['fieldId', false],
				],
				'foreignKeys' => [
					['tabId', '{{%fieldlayouttabs}}', 'id', 'CASCADE', null],
					['fieldId', '{{%fields}}', 'id', 'CASCADE', null],
					['layoutId', '{{%fieldlayouts}}', 'id', 'CASCADE', null],
				],
			],
			'{{%fieldlayouts}}' => [
				'columns' => [
					'type' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				],
				'indexes' => [
					['type', false],
				],
			],
			'{{%fieldlayouttabs}}' => [
				'columns' => [
					'layoutId' => 'integer(11) NOT NULL',
					'name' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
					'sortOrder' => 'smallint(4) DEFAULT NULL',
				],
				'indexes' => [
					['sortOrder', false],
					['layoutId', false],
				],
				'foreignKeys' => [
					['layoutId', '{{%fieldlayouts}}', 'id', 'CASCADE', null],
				],
			],
			'{{%fields}}' => [
				'columns' => [
					'groupId' => 'integer(11) DEFAULT NULL',
					'name' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
					'handle' => 'string(64) COLLATE utf8_unicode_ci NOT NULL',
					'context' => 'string(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'global\'',
					'instructions' => 'text COLLATE utf8_unicode_ci',
					'translatable' => 'smallint(1) unsigned NOT NULL DEFAULT \'0\'',
					'type' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
					'settings' => 'text COLLATE utf8_unicode_ci',
				],
				'indexes' => [
					['handle,context', true],
					['groupId', false],
					['context', false],
				],
				'foreignKeys' => [
					['groupId', '{{%fieldgroups}}', 'id', 'CASCADE', null],
				],
			],
			'{{%globalsets}}' => [
				'columns' => [
					'name' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
					'handle' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
					'fieldLayoutId' => 'integer(10) DEFAULT NULL',
				],
				'indexes' => [
					['name', true],
					['handle', true],
					['fieldLayoutId', false],
				],
				'foreignKeys' => [
					['fieldLayoutId', '{{%fieldlayouts}}', 'id', 'SET NULL', null],
					['id', '{{%elements}}', 'id', 'CASCADE', null],
				],
			],
			'{{%info}}' => [
				'columns' => [
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
					'fieldVersion' => 'char(12) NOT NULL DEFAULT \'1\'',
				],
			],
			'{{%locales}}' => [
				'columns' => [
					'locale' => 'char(12) COLLATE utf8_unicode_ci NOT NULL',
					'sortOrder' => 'smallint(4) DEFAULT NULL',
				],
				'addIdColumn' => false,
				'primaryKey' => 'locale',
				'indexes' => [
					['sortOrder', false],
				],
			],
			'{{%matrixblocks}}' => [
				'columns' => [
					'ownerId' => 'integer(11) NOT NULL',
					'ownerLocale' => 'char(12) COLLATE utf8_unicode_ci DEFAULT NULL',
					'fieldId' => 'integer(11) NOT NULL',
					'typeId' => 'integer(11) DEFAULT NULL',
					'sortOrder' => 'smallint(4) DEFAULT NULL',
				],
				'indexes' => [
					['ownerId', false],
					['fieldId', false],
					['typeId', false],
					['sortOrder', false],
					['ownerLocale', false],
				],
				'foreignKeys' => [
					['ownerLocale', '{{%locales}}', 'locale', 'CASCADE', 'CASCADE'],
					['fieldId', '{{%fields}}', 'id', 'CASCADE', null],
					['id', '{{%elements}}', 'id', 'CASCADE', null],
					['ownerId', '{{%elements}}', 'id', 'CASCADE', null],
					['typeId', '{{%matrixblocktypes}}', 'id', 'CASCADE', null],
				],
			],
			'{{%matrixblocktypes}}' => [
				'columns' => [
					'fieldId' => 'integer(11) NOT NULL',
					'fieldLayoutId' => 'integer(11) DEFAULT NULL',
					'name' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
					'handle' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
					'sortOrder' => 'smallint(4) DEFAULT NULL',
				],
				'indexes' => [
					['name,fieldId', true],
					['handle,fieldId', true],
					['fieldId', false],
					['fieldLayoutId', false],
				],
				'foreignKeys' => [
					['fieldLayoutId', '{{%fieldlayouts}}', 'id', 'SET NULL', null],
					['fieldId', '{{%fields}}', 'id', 'CASCADE', null],
				],
			],
			'{{%migrations}}' => [
				'columns' => [
					'pluginId' => 'integer(11) DEFAULT NULL',
					'type' => 'enum(\'app\',\'plugin\',\'content\') COLLATE utf8_unicode_ci NOT NULL DEFAULT \'app\'',
					'name' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
					'applyTime' => 'datetime NOT NULL',
				],
				'indexes' => [
					['pluginId', false],
					['type,pluginId', false],
				],
				'foreignKeys' => [
					['pluginId', '{{%plugins}}', 'id', 'CASCADE', null],
				],
			],
			'{{%plugins}}' => [
				'columns' => [
					'handle' => 'string(150) COLLATE utf8_unicode_ci NOT NULL',
					'version' => 'char(15) COLLATE utf8_unicode_ci NOT NULL',
					'enabled' => 'smallint(1) unsigned NOT NULL DEFAULT \'0\'',
					'settings' => 'text COLLATE utf8_unicode_ci',
					'installDate' => 'datetime NOT NULL',
				],
				'indexes' => [
					['handle', true],
				],
			],
			'{{%rackspaceaccess}}' => [
				'columns' => [
					'connectionKey' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
					'token' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
					'storageUrl' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
					'cdnUrl' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				],
				'indexes' => [
					['connectionKey', true],
				],
			],
			'{{%relations}}' => [
				'columns' => [
					'fieldId' => 'integer(11) NOT NULL',
					'sourceId' => 'integer(11) NOT NULL',
					'sourceLocale' => 'char(12) COLLATE utf8_unicode_ci DEFAULT NULL',
					'targetId' => 'integer(11) NOT NULL',
					'sortOrder' => 'smallint(6) DEFAULT NULL',
				],
				'indexes' => [
					['fieldId,sourceId,sourceLocale,targetId', true],
					['sourceId', false],
					['targetId', false],
					['sourceLocale', false],
				],
				'foreignKeys' => [
					['fieldId', '{{%fields}}', 'id', 'CASCADE', null],
					['sourceId', '{{%elements}}', 'id', 'CASCADE', null],
					['sourceLocale', '{{%locales}}', 'locale', 'CASCADE', 'CASCADE'],
					['targetId', '{{%elements}}', 'id', 'CASCADE', null],
				],
			],
			'{{%routes}}' => [
				'columns' => [
					'locale' => 'char(12) COLLATE utf8_unicode_ci DEFAULT NULL',
					'urlParts' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
					'urlPattern' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
					'template' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
					'sortOrder' => 'smallint(4) DEFAULT NULL',
				],
				'indexes' => [
					['urlPattern', true],
					['locale', false],
				],
				'foreignKeys' => [
					['locale', '{{%locales}}', 'locale', 'CASCADE', 'CASCADE'],
				],
			],
			'{{%searchindex}}' => [
				'columns' => [
					'elementId' => 'integer(11) NOT NULL',
					'attribute' => 'string(25) COLLATE utf8_unicode_ci NOT NULL',
					'fieldId' => 'integer(11) NOT NULL',
					'locale' => 'char(12) COLLATE utf8_unicode_ci NOT NULL',
					'keywords' => 'text COLLATE utf8_unicode_ci NOT NULL',
				],
				'addIdColumn' => false,
				'addAuditColumns' => false,
				'primaryKey' => 'elementId,attribute,fieldId,locale',
				'options' => 'ENGINE=MyISAM',
			],
			'{{%sections}}' => [
				'columns' => [
					'structureId' => 'integer(11) DEFAULT NULL',
					'name' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
					'handle' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
					'type' => 'enum(\'single\',\'channel\',\'structure\') COLLATE utf8_unicode_ci NOT NULL DEFAULT \'channel\'',
					'hasUrls' => 'smallint(1) unsigned NOT NULL DEFAULT \'1\'',
					'template' => 'string(500) COLLATE utf8_unicode_ci DEFAULT NULL',
					'enableVersioning' => 'smallint(1) unsigned NOT NULL DEFAULT \'0\'',
				],
				'indexes' => [
					['handle', true],
					['name', true],
					['structureId', false],
				],
				'foreignKeys' => [
					['structureId', '{{%structures}}', 'id', 'SET NULL', null],
				],
			],
			'{{%sections_i18n}}' => [
				'columns' => [
					'sectionId' => 'integer(11) NOT NULL',
					'locale' => 'char(12) COLLATE utf8_unicode_ci NOT NULL',
					'enabledByDefault' => 'smallint(1) DEFAULT \'1\'',
					'urlFormat' => 'string(255) COLLATE utf8_unicode_ci DEFAULT NULL',
					'nestedUrlFormat' => 'string(255) COLLATE utf8_unicode_ci DEFAULT NULL',
				],
				'indexes' => [
					['sectionId,locale', true],
					['locale', false],
				],
				'foreignKeys' => [
					['locale', '{{%locales}}', 'locale', 'CASCADE', 'CASCADE'],
					['sectionId', '{{%sections}}', 'id', 'CASCADE', null],
				],
			],
			'{{%sessions}}' => [
				'columns' => [
					'userId' => 'integer(11) NOT NULL',
					'token' => 'char(100) COLLATE utf8_unicode_ci NOT NULL',
				],
				'indexes' => [
					['uid', false],
					['token', false],
					['dateUpdated', false],
					['userId', false],
				],
				'foreignKeys' => [
					['userId', '{{%users}}', 'id', 'CASCADE', null],
				],
			],
			'{{%shunnedmessages}}' => [
				'columns' => [
					'userId' => 'integer(11) NOT NULL',
					'message' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
					'expiryDate' => 'datetime DEFAULT NULL',
				],
				'indexes' => [
					['userId,message', true],
				],
				'foreignKeys' => [
					['userId', '{{%users}}', 'id', 'CASCADE', null],
				],
			],
			'{{%structureelements}}' => [
				'columns' => [
					'structureId' => 'integer(11) NOT NULL',
					'elementId' => 'integer(11) DEFAULT NULL',
					'root' => 'integer(10) unsigned DEFAULT NULL',
					'lft' => 'integer(10) unsigned NOT NULL',
					'rgt' => 'integer(10) unsigned NOT NULL',
					'level' => 'smallint(6) unsigned NOT NULL',
				],
				'indexes' => [
					['structureId,elementId', true],
					['root', false],
					['lft', false],
					['rgt', false],
					['level', false],
					['elementId', false],
				],
				'foreignKeys' => [
					['elementId', '{{%elements}}', 'id', 'CASCADE', null],
					['structureId', '{{%structures}}', 'id', 'CASCADE', null],
				],
			],
			'{{%structures}}' => [
				'columns' => [
					'maxLevels' => 'smallint(6) unsigned DEFAULT NULL',
				],
			],
			'{{%systemsettings}}' => [
				'columns' => [
					'category' => 'string(15) COLLATE utf8_unicode_ci NOT NULL',
					'settings' => 'text COLLATE utf8_unicode_ci',
				],
				'indexes' => [
					['category', true],
				],
			],
			'{{%taggroups}}' => [
				'columns' => [
					'name' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
					'handle' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
					'fieldLayoutId' => 'integer(10) DEFAULT NULL',
				],
				'indexes' => [
					['name', true],
					['handle', true],
				],
			],
			'{{%tags}}' => [
				'columns' => [
					'groupId' => 'integer(11) NOT NULL',
				],
				'indexes' => [
					['groupId', false],
				],
				'foreignKeys' => [
					['groupId', '{{%taggroups}}', 'id', 'CASCADE', null],
					['id', '{{%elements}}', 'id', 'CASCADE', null],
				],
			],
			'{{%tasks}}' => [
				'columns' => [
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
				],
				'indexes' => [
					['root', false],
					['lft', false],
					['rgt', false],
					['level', false],
				],
			],
			'{{%templatecachecriteria}}' => [
				'columns' => [
					'cacheId' => 'integer(11) NOT NULL',
					'type' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
					'criteria' => 'text COLLATE utf8_unicode_ci NOT NULL',
				],
				'addAuditColumns' => false,
				'indexes' => [
					['cacheId', false],
					['type', false],
				],
				'foreignKeys' => [
					['cacheId', '{{%templatecaches}}', 'id', 'CASCADE', null],
				],
			],
			'{{%templatecacheelements}}' => [
				'columns' => [
					'cacheId' => 'integer(11) NOT NULL',
					'elementId' => 'integer(11) NOT NULL',
				],
				'addIdColumn' => false,
				'addAuditColumns' => false,
				'indexes' => [
					['cacheId', false],
					['elementId', false],
				],
				'foreignKeys' => [
					['elementId', '{{%elements}}', 'id', 'CASCADE', null],
					['cacheId', '{{%templatecaches}}', 'id', 'CASCADE', null],
				],
			],
			'{{%templatecaches}}' => [
				'columns' => [
					'cacheKey' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
					'locale' => 'char(12) COLLATE utf8_unicode_ci NOT NULL',
					'path' => 'string(255) COLLATE utf8_unicode_ci DEFAULT NULL',
					'expiryDate' => 'datetime NOT NULL',
					'body' => 'mediumtext COLLATE utf8_unicode_ci NOT NULL',
				],
				'addAuditColumns' => false,
				'indexes' => [
					['expiryDate,cacheKey,locale,path', false],
					['locale', false],
				],
				'foreignKeys' => [
					['locale', '{{%locales}}', 'locale', 'CASCADE', 'CASCADE'],
				],
			],
			'{{%tokens}}' => [
				'columns' => [
					'token' => 'char(32) COLLATE utf8_unicode_ci NOT NULL',
					'route' => 'text COLLATE utf8_unicode_ci',
					'usageLimit' => 'smallint(3) unsigned DEFAULT NULL',
					'usageCount' => 'smallint(3) unsigned DEFAULT NULL',
					'expiryDate' => 'datetime NOT NULL',
				],
				'indexes' => [
					['token', true],
					['expiryDate', false],
				],
			],
			'{{%usergroups}}' => [
				'columns' => [
					'name' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
					'handle' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				],
			],
			'{{%usergroups_users}}' => [
				'columns' => [
					'groupId' => 'integer(11) NOT NULL',
					'userId' => 'integer(11) NOT NULL',
				],
				'indexes' => [
					['groupId,userId', true],
					['userId', false],
				],
				'foreignKeys' => [
					['groupId', '{{%usergroups}}', 'id', 'CASCADE', null],
					['userId', '{{%users}}', 'id', 'CASCADE', null],
				],
			],
			'{{%userpermissions}}' => [
				'columns' => [
					'name' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
				],
				'indexes' => [
					['name', true],
				],
			],
			'{{%userpermissions_usergroups}}' => [
				'columns' => [
					'permissionId' => 'integer(11) NOT NULL',
					'groupId' => 'integer(11) NOT NULL',
				],
				'indexes' => [
					['permissionId,groupId', true],
					['groupId', false],
				],
				'foreignKeys' => [
					['groupId', '{{%usergroups}}', 'id', 'CASCADE', null],
					['permissionId', '{{%userpermissions}}', 'id', 'CASCADE', null],
				],
			],
			'{{%userpermissions_users}}' => [
				'columns' => [
					'permissionId' => 'integer(11) NOT NULL',
					'userId' => 'integer(11) NOT NULL',
				],
				'indexes' => [
					['permissionId,userId', true],
					['userId', false],
				],
				'foreignKeys' => [
					['permissionId', '{{%userpermissions}}', 'id', 'CASCADE', null],
					['userId', '{{%users}}', 'id', 'CASCADE', null],
				],
			],
			'{{%userpreferences}}' => [
				'columns' => [
					'userId' => 'integer(11) NOT NULL DEFAULT \'0\'',
					'preferences' => 'text COLLATE utf8_unicode_ci',
				],
				'addIdColumn' => false,
				'addAuditColumns' => false,
				'primaryKey' => 'userId',
				'foreignKeys' => [
					['userId', '{{%users}}', 'id', 'CASCADE', null],
				],
			],
			'{{%users}}' => [
				'columns' => [
					'username' => 'string(100) COLLATE utf8_unicode_ci NOT NULL',
					'photo' => 'string(100) COLLATE utf8_unicode_ci DEFAULT NULL',
					'firstName' => 'string(100) COLLATE utf8_unicode_ci DEFAULT NULL',
					'lastName' => 'string(100) COLLATE utf8_unicode_ci DEFAULT NULL',
					'email' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
					'password' => 'char(255) COLLATE utf8_unicode_ci DEFAULT NULL',
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
				],
				'indexes' => [
					['username', true],
					['email', true],
					['uid', false],
					['verificationCode', false],
				],
				'foreignKeys' => [
					['id', '{{%elements}}', 'id', 'CASCADE', null],
				],
			],
			'{{%volumefolders}}' => [
				'columns' => [
					'parentId' => 'integer(11) DEFAULT NULL',
					'volumeId' => 'integer(11) DEFAULT NULL',
					'name' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
					'path' => 'string(255) COLLATE utf8_unicode_ci DEFAULT NULL',
				],
				'indexes' => [
					['name,parentId,volumeId', true],
					['parentId', false],
					['volumeId', false],
				],
				'foreignKeys' => [
					['volumeId', '{{%volumes}}', 'id', 'CASCADE', null],
					['parentId', '{{%volumefolders}}', 'id', 'CASCADE', null],
				],
			],
			'{{%volumes}}' => [
				'columns' => [
					'fieldLayoutId' => 'integer(11) DEFAULT NULL',
					'name' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
					'handle' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
					'type' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
					'url' => 'string(255) COLLATE utf8_unicode_ci DEFAULT NULL',
					'settings' => 'text COLLATE utf8_unicode_ci',
					'sortOrder' => 'smallint(4) DEFAULT NULL',
				],
				'indexes' => [
					['name', true],
					['handle', true],
					['fieldLayoutId', false],
				],
				'foreignKeys' => [
					['fieldLayoutId', '{{%fieldlayouts}}', 'id', 'SET NULL', null],
				],
			],
			'{{%widgets}}' => [
				'columns' => [
					'userId' => 'integer(11) NOT NULL',
					'type' => 'string(255) COLLATE utf8_unicode_ci NOT NULL',
					'sortOrder' => 'smallint(4) DEFAULT NULL',
					'settings' => 'text COLLATE utf8_unicode_ci',
					'enabled' => 'smallint(1) DEFAULT \'1\'',
				],
				'indexes' => [
					['userId', false],
				],
				'foreignKeys' => [
					['userId', '{{%users}}', 'id', 'CASCADE', null],
				],
			],
		];
	}
}
