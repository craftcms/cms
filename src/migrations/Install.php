<?php /** @noinspection RepetitiveMethodCallsInspection */

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\migrations;

use Craft;
use craft\base\Plugin;
use craft\db\Migration;
use craft\db\Table;
use craft\elements\Asset;
use craft\elements\User;
use craft\errors\InvalidPluginException;
use craft\helpers\App;
use craft\helpers\Json;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\StringHelper;
use craft\mail\transportadapters\Sendmail;
use craft\models\Info;
use craft\models\Section;
use craft\models\Site;
use craft\services\Plugins;
use craft\services\ProjectConfig;
use craft\web\Response;
use yii\base\InvalidConfigException;

/**
 * Installation Migration
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Install extends Migration
{
    // Properties
    // =========================================================================

    /**
     * @var string|null The admin user’s username
     */
    public $username;

    /**
     * @var string|null The admin user’s password
     */
    public $password;

    /**
     * @var string|null The admin user’s email
     */
    public $email;

    /**
     * @var Site|null The default site
     */
    public $site;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTables();
        $this->createIndexes();
        $this->addForeignKeys();
        $this->insertDefaultData();
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        return false;
    }

    /**
     * Creates the tables.
     */
    public function createTables()
    {
        $this->createTable(Table::ASSETINDEXDATA, [
            'id' => $this->primaryKey(),
            'sessionId' => $this->string(36)->notNull()->defaultValue(''),
            'volumeId' => $this->integer()->notNull(),
            'uri' => $this->text(),
            'size' => $this->bigInteger()->unsigned(),
            'timestamp' => $this->dateTime(),
            'recordId' => $this->integer(),
            'inProgress' => $this->boolean()->defaultValue(false),
            'completed' => $this->boolean()->defaultValue(false),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::ASSETS, [
            'id' => $this->integer()->notNull(),
            'volumeId' => $this->integer(),
            'folderId' => $this->integer()->notNull(),
            'filename' => $this->string()->notNull(),
            'kind' => $this->string(50)->notNull()->defaultValue(Asset::KIND_UNKNOWN),
            'width' => $this->integer()->unsigned(),
            'height' => $this->integer()->unsigned(),
            'size' => $this->bigInteger()->unsigned(),
            'focalPoint' => $this->string(13)->null(),
            'deletedWithVolume' => $this->boolean()->null(),
            'keptFile' => $this->boolean()->null(),
            'dateModified' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
            'PRIMARY KEY([[id]])',
        ]);
        $this->createTable(Table::ASSETTRANSFORMINDEX, [
            'id' => $this->primaryKey(),
            'assetId' => $this->integer()->notNull(),
            'filename' => $this->string(),
            'format' => $this->string(),
            'location' => $this->string()->notNull(),
            'volumeId' => $this->integer(),
            'fileExists' => $this->boolean()->defaultValue(false)->notNull(),
            'inProgress' => $this->boolean()->defaultValue(false)->notNull(),
            'dateIndexed' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::ASSETTRANSFORMS, [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'mode' => $this->enum('mode', ['stretch', 'fit', 'crop'])->notNull()->defaultValue('crop'),
            'position' => $this->enum('position', ['top-left', 'top-center', 'top-right', 'center-left', 'center-center', 'center-right', 'bottom-left', 'bottom-center', 'bottom-right'])->notNull()->defaultValue('center-center'),
            'width' => $this->integer()->unsigned(),
            'height' => $this->integer()->unsigned(),
            'format' => $this->string(),
            'quality' => $this->integer(),
            'interlace' => $this->enum('interlace', ['none', 'line', 'plane', 'partition'])->notNull()->defaultValue('none'),
            'dimensionChangeTime' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::CATEGORIES, [
            'id' => $this->integer()->notNull(),
            'groupId' => $this->integer()->notNull(),
            'parentId' => $this->integer(),
            'deletedWithGroup' => $this->boolean()->null(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
            'PRIMARY KEY([[id]])',
        ]);
        $this->createTable(Table::CATEGORYGROUPS, [
            'id' => $this->primaryKey(),
            'structureId' => $this->integer()->notNull(),
            'fieldLayoutId' => $this->integer(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'dateDeleted' => $this->dateTime()->null(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::CATEGORYGROUPS_SITES, [
            'id' => $this->primaryKey(),
            'groupId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'hasUrls' => $this->boolean()->defaultValue(true)->notNull(),
            'uriFormat' => $this->text(),
            'template' => $this->string(500),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::CONTENT, [
            'id' => $this->primaryKey(),
            'elementId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'title' => $this->string(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::CRAFTIDTOKENS, [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'accessToken' => $this->text()->notNull(),
            'expiryDate' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::DEPRECATIONERRORS, [
            'id' => $this->primaryKey(),
            'key' => $this->string()->notNull(),
            'fingerprint' => $this->string()->notNull(),
            'lastOccurrence' => $this->dateTime()->notNull(),
            'file' => $this->string()->notNull(),
            'line' => $this->smallInteger()->unsigned(),
            'message' => $this->string(),
            'traces' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::ELEMENTINDEXSETTINGS, [
            'id' => $this->primaryKey(),
            'type' => $this->string()->notNull(),
            'settings' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::ELEMENTS, [
            'id' => $this->primaryKey(),
            'fieldLayoutId' => $this->integer(),
            'type' => $this->string()->notNull(),
            'enabled' => $this->boolean()->defaultValue(true)->notNull(),
            'archived' => $this->boolean()->defaultValue(false)->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'dateDeleted' => $this->dateTime()->null(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::ELEMENTS_SITES, [
            'id' => $this->primaryKey(),
            'elementId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'slug' => $this->string(),
            'uri' => $this->string(),
            'enabled' => $this->boolean()->defaultValue(true)->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::RESOURCEPATHS, [
            'hash' => $this->string()->notNull(),
            'path' => $this->string()->notNull(),
            'PRIMARY KEY([[hash]])',
        ]);
        $this->createTable(Table::SEQUENCES, [
            'name' => $this->string()->notNull(),
            'next' => $this->integer()->unsigned()->notNull()->defaultValue(1),
            'PRIMARY KEY([[name]])',
        ]);
        $this->createTable(Table::SYSTEMMESSAGES, [
            'id' => $this->primaryKey(),
            'language' => $this->string()->notNull(),
            'key' => $this->string()->notNull(),
            'subject' => $this->text()->notNull(),
            'body' => $this->text()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::ENTRIES, [
            'id' => $this->integer()->notNull(),
            'sectionId' => $this->integer()->notNull(),
            'parentId' => $this->integer(),
            'typeId' => $this->integer()->notNull(),
            'authorId' => $this->integer(),
            'postDate' => $this->dateTime(),
            'expiryDate' => $this->dateTime(),
            'deletedWithEntryType' => $this->boolean()->null(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
            'PRIMARY KEY([[id]])',
        ]);
        $this->createTable(Table::ENTRYDRAFTS, [
            'id' => $this->primaryKey(),
            'entryId' => $this->integer()->notNull(),
            'sectionId' => $this->integer()->notNull(),
            'creatorId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'name' => $this->string()->notNull(),
            'notes' => $this->text(),
            'data' => $this->mediumText()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::ENTRYTYPES, [
            'id' => $this->primaryKey(),
            'sectionId' => $this->integer()->notNull(),
            'fieldLayoutId' => $this->integer(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'hasTitleField' => $this->boolean()->defaultValue(true)->notNull(),
            'titleLabel' => $this->string()->defaultValue('Title'),
            'titleFormat' => $this->string(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'dateDeleted' => $this->dateTime()->null(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::ENTRYVERSIONS, [
            'id' => $this->primaryKey(),
            'entryId' => $this->integer()->notNull(),
            'sectionId' => $this->integer()->notNull(),
            'creatorId' => $this->integer(),
            'siteId' => $this->integer()->notNull(),
            'num' => $this->smallInteger()->notNull()->unsigned(),
            'notes' => $this->text(),
            'data' => $this->mediumText()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::FIELDGROUPS, [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::FIELDLAYOUTFIELDS, [
            'id' => $this->primaryKey(),
            'layoutId' => $this->integer()->notNull(),
            'tabId' => $this->integer()->notNull(),
            'fieldId' => $this->integer()->notNull(),
            'required' => $this->boolean()->defaultValue(false)->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::FIELDLAYOUTS, [
            'id' => $this->primaryKey(),
            'type' => $this->string()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'dateDeleted' => $this->dateTime()->null(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::FIELDLAYOUTTABS, [
            'id' => $this->primaryKey(),
            'layoutId' => $this->integer()->notNull(),
            'name' => $this->string()->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::FIELDS, [
            'id' => $this->primaryKey(),
            'groupId' => $this->integer(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string(64)->notNull(),
            'context' => $this->string()->notNull()->defaultValue('global'),
            'instructions' => $this->text(),
            'searchable' => $this->boolean()->notNull()->defaultValue(true),
            'translationMethod' => $this->string()->notNull()->defaultValue('none'),
            'translationKeyFormat' => $this->text(),
            'type' => $this->string()->notNull(),
            'settings' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::GLOBALSETS, [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'fieldLayoutId' => $this->integer(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::INFO, [
            'id' => $this->primaryKey(),
            'version' => $this->string(50)->notNull(),
            'schemaVersion' => $this->string(15)->notNull(),
            'maintenance' => $this->boolean()->defaultValue(false)->notNull(),
            'config' => $this->mediumText()->null(),
            'configMap' => $this->mediumText()->null(),
            'fieldVersion' => $this->char(12)->notNull()->defaultValue('000000000000'),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::MATRIXBLOCKS, [
            'id' => $this->integer()->notNull(),
            'ownerId' => $this->integer()->notNull(),
            'ownerSiteId' => $this->integer(),
            'fieldId' => $this->integer()->notNull(),
            'typeId' => $this->integer()->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'deletedWithOwner' => $this->boolean()->null(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
            'PRIMARY KEY([[id]])',
        ]);
        $this->createTable(Table::MATRIXBLOCKTYPES, [
            'id' => $this->primaryKey(),
            'fieldId' => $this->integer()->notNull(),
            'fieldLayoutId' => $this->integer(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::MIGRATIONS, [
            'id' => $this->primaryKey(),
            'pluginId' => $this->integer(),
            'type' => $this->enum('type', ['app', 'plugin', 'content'])->notNull()->defaultValue('app'),
            'name' => $this->string()->notNull(),
            'applyTime' => $this->dateTime()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::PLUGINS, [
            'id' => $this->primaryKey(),
            'handle' => $this->string()->notNull(),
            'version' => $this->string()->notNull(),
            'schemaVersion' => $this->string()->notNull(),
            'licenseKeyStatus' => $this->enum('licenseKeyStatus', ['valid', 'invalid', 'mismatched', 'astray', 'unknown'])->notNull()->defaultValue('unknown'),
            'licensedEdition' => $this->string(),
            'installDate' => $this->dateTime()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::QUEUE, [
            'id' => $this->primaryKey(),
            'job' => $this->binary()->notNull(),
            'description' => $this->text(),
            'timePushed' => $this->integer()->notNull(),
            'ttr' => $this->integer()->notNull(),
            'delay' => $this->integer()->defaultValue(0)->notNull(),
            'priority' => $this->integer()->unsigned()->notNull()->defaultValue(1024),
            'dateReserved' => $this->dateTime(),
            'timeUpdated' => $this->integer(),
            'progress' => $this->smallInteger()->notNull()->defaultValue(0),
            'attempt' => $this->integer(),
            'fail' => $this->boolean()->defaultValue(false),
            'dateFailed' => $this->dateTime(),
            'error' => $this->text(),
        ]);
        $this->createTable(Table::RELATIONS, [
            'id' => $this->primaryKey(),
            'fieldId' => $this->integer()->notNull(),
            'sourceId' => $this->integer()->notNull(),
            'sourceSiteId' => $this->integer(),
            'targetId' => $this->integer()->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::SECTIONS, [
            'id' => $this->primaryKey(),
            'structureId' => $this->integer(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'type' => $this->enum('type', [Section::TYPE_SINGLE, Section::TYPE_CHANNEL, Section::TYPE_STRUCTURE])->notNull()->defaultValue('channel'),
            'enableVersioning' => $this->boolean()->defaultValue(false)->notNull(),
            'propagateEntries' => $this->boolean()->defaultValue(true)->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'dateDeleted' => $this->dateTime()->null(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::SECTIONS_SITES, [
            'id' => $this->primaryKey(),
            'sectionId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'hasUrls' => $this->boolean()->defaultValue(true)->notNull(),
            'uriFormat' => $this->text(),
            'template' => $this->string(500),
            'enabledByDefault' => $this->boolean()->defaultValue(true)->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::SESSIONS, [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'token' => $this->char(100)->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::SHUNNEDMESSAGES, [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'message' => $this->string()->notNull(),
            'expiryDate' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::SITES, [
            'id' => $this->primaryKey(),
            'groupId' => $this->integer()->notNull(),
            'primary' => $this->boolean()->notNull(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'language' => $this->string(12)->notNull(),
            'hasUrls' => $this->boolean()->defaultValue(false)->notNull(),
            'baseUrl' => $this->string(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'dateDeleted' => $this->dateTime()->null(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::SITEGROUPS, [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'dateDeleted' => $this->dateTime()->null(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::STRUCTUREELEMENTS, [
            'id' => $this->primaryKey(),
            'structureId' => $this->integer()->notNull(),
            'elementId' => $this->integer(),
            'root' => $this->integer()->unsigned(),
            'lft' => $this->integer()->notNull()->unsigned(),
            'rgt' => $this->integer()->notNull()->unsigned(),
            'level' => $this->smallInteger()->notNull()->unsigned(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::STRUCTURES, [
            'id' => $this->primaryKey(),
            'maxLevels' => $this->smallInteger()->unsigned(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'dateDeleted' => $this->dateTime()->null(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::TAGGROUPS, [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'fieldLayoutId' => $this->integer(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'dateDeleted' => $this->dateTime()->null(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::TAGS, [
            'id' => $this->integer()->notNull(),
            'groupId' => $this->integer()->notNull(),
            'deletedWithGroup' => $this->boolean()->null(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
            'PRIMARY KEY([[id]])',
        ]);
        $this->createTable(Table::TEMPLATECACHEELEMENTS, [
            'cacheId' => $this->integer()->notNull(),
            'elementId' => $this->integer()->notNull(),
        ]);
        $this->createTable(Table::TEMPLATECACHEQUERIES, [
            'id' => $this->primaryKey(),
            'cacheId' => $this->integer()->notNull(),
            'type' => $this->string()->notNull(),
            'query' => $this->longText()->notNull(),
        ]);
        $this->createTable(Table::TEMPLATECACHES, [
            'id' => $this->primaryKey(),
            'siteId' => $this->integer()->notNull(),
            'cacheKey' => $this->string()->notNull(),
            'path' => $this->string(),
            'expiryDate' => $this->dateTime()->notNull(),
            'body' => $this->mediumText()->notNull(),
        ]);
        $this->createTable(Table::TOKENS, [
            'id' => $this->primaryKey(),
            'token' => $this->char(32)->notNull(),
            'route' => $this->text(),
            'usageLimit' => $this->tinyInteger()->unsigned(),
            'usageCount' => $this->tinyInteger()->unsigned(),
            'expiryDate' => $this->dateTime()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::USERGROUPS, [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::USERGROUPS_USERS, [
            'id' => $this->primaryKey(),
            'groupId' => $this->integer()->notNull(),
            'userId' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::USERPERMISSIONS, [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::USERPERMISSIONS_USERGROUPS, [
            'id' => $this->primaryKey(),
            'permissionId' => $this->integer()->notNull(),
            'groupId' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::USERPERMISSIONS_USERS, [
            'id' => $this->primaryKey(),
            'permissionId' => $this->integer()->notNull(),
            'userId' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::USERPREFERENCES, [
            'userId' => $this->primaryKey(),
            'preferences' => $this->text(),
        ]);
        $this->createTable(Table::USERS, [
            'id' => $this->integer()->notNull(),
            'username' => $this->string(100)->notNull(),
            'photoId' => $this->integer(),
            'firstName' => $this->string(100),
            'lastName' => $this->string(100),
            'email' => $this->string()->notNull(),
            'password' => $this->string(),
            'admin' => $this->boolean()->defaultValue(false)->notNull(),
            'locked' => $this->boolean()->defaultValue(false)->notNull(),
            'suspended' => $this->boolean()->defaultValue(false)->notNull(),
            'pending' => $this->boolean()->defaultValue(false)->notNull(),
            'lastLoginDate' => $this->dateTime(),
            'lastLoginAttemptIp' => $this->string(45),
            'invalidLoginWindowStart' => $this->dateTime(),
            'invalidLoginCount' => $this->tinyInteger()->unsigned(),
            'lastInvalidLoginDate' => $this->dateTime(),
            'lockoutDate' => $this->dateTime(),
            'hasDashboard' => $this->boolean()->notNull()->defaultValue(false),
            'verificationCode' => $this->string(),
            'verificationCodeIssuedDate' => $this->dateTime(),
            'unverifiedEmail' => $this->string(),
            'passwordResetRequired' => $this->boolean()->defaultValue(false)->notNull(),
            'lastPasswordChangeDate' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
            'PRIMARY KEY([[id]])',
        ]);
        $this->createTable(Table::VOLUMEFOLDERS, [
            'id' => $this->primaryKey(),
            'parentId' => $this->integer(),
            'volumeId' => $this->integer(),
            'name' => $this->string()->notNull(),
            'path' => $this->string(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::VOLUMES, [
            'id' => $this->primaryKey(),
            'fieldLayoutId' => $this->integer(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'type' => $this->string()->notNull(),
            'hasUrls' => $this->boolean()->defaultValue(true)->notNull(),
            'url' => $this->string(),
            'settings' => $this->text(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'dateDeleted' => $this->dateTime()->null(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::WIDGETS, [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'type' => $this->string()->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'colspan' => $this->tinyInteger(),
            'settings' => $this->text(),
            'enabled' => $this->boolean()->defaultValue(true)->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
    }

    /**
     * Creates the indexes.
     */
    public function createIndexes()
    {
        $this->createIndex(null, Table::ASSETINDEXDATA, ['sessionId', 'volumeId']);
        $this->createIndex(null, Table::ASSETINDEXDATA, ['volumeId'], false);
        $this->createIndex(null, Table::ASSETS, ['filename', 'folderId'], false);
        $this->createIndex(null, Table::ASSETS, ['folderId'], false);
        $this->createIndex(null, Table::ASSETS, ['volumeId'], false);
        $this->createIndex(null, Table::ASSETTRANSFORMINDEX, ['volumeId', 'assetId', 'location'], false);
        $this->createIndex(null, Table::ASSETTRANSFORMS, ['name'], true);
        $this->createIndex(null, Table::ASSETTRANSFORMS, ['handle'], true);
        $this->createIndex(null, Table::CATEGORIES, ['groupId'], false);
        $this->createIndex(null, Table::CATEGORYGROUPS, ['name'], false);
        $this->createIndex(null, Table::CATEGORYGROUPS, ['handle'], false);
        $this->createIndex(null, Table::CATEGORYGROUPS, ['structureId'], false);
        $this->createIndex(null, Table::CATEGORYGROUPS, ['fieldLayoutId'], false);
        $this->createIndex(null, Table::CATEGORYGROUPS, ['dateDeleted'], false);
        $this->createIndex(null, Table::CATEGORYGROUPS_SITES, ['groupId', 'siteId'], true);
        $this->createIndex(null, Table::CATEGORYGROUPS_SITES, ['siteId'], false);
        $this->createIndex(null, Table::CONTENT, ['elementId', 'siteId'], true);
        $this->createIndex(null, Table::CONTENT, ['siteId'], false);
        $this->createIndex(null, Table::CONTENT, ['title'], false);
        $this->createIndex(null, Table::DEPRECATIONERRORS, ['key', 'fingerprint'], true);
        $this->createIndex(null, Table::ELEMENTINDEXSETTINGS, ['type'], true);
        $this->createIndex(null, Table::ELEMENTS, ['dateDeleted'], false);
        $this->createIndex(null, Table::ELEMENTS, ['fieldLayoutId'], false);
        $this->createIndex(null, Table::ELEMENTS, ['type'], false);
        $this->createIndex(null, Table::ELEMENTS, ['enabled'], false);
        $this->createIndex(null, Table::ELEMENTS, ['archived', 'dateCreated'], false);
        $this->createIndex(null, Table::ELEMENTS_SITES, ['elementId', 'siteId'], true);
        $this->createIndex(null, Table::ELEMENTS_SITES, ['siteId'], false);
        $this->createIndex(null, Table::ELEMENTS_SITES, ['slug', 'siteId'], false);
        $this->createIndex(null, Table::ELEMENTS_SITES, ['enabled'], false);
        $this->createIndex(null, Table::SYSTEMMESSAGES, ['key', 'language'], true);
        $this->createIndex(null, Table::SYSTEMMESSAGES, ['language'], false);
        $this->createIndex(null, Table::ENTRIES, ['postDate'], false);
        $this->createIndex(null, Table::ENTRIES, ['expiryDate'], false);
        $this->createIndex(null, Table::ENTRIES, ['authorId'], false);
        $this->createIndex(null, Table::ENTRIES, ['sectionId'], false);
        $this->createIndex(null, Table::ENTRIES, ['typeId'], false);
        $this->createIndex(null, Table::ENTRYDRAFTS, ['sectionId'], false);
        $this->createIndex(null, Table::ENTRYDRAFTS, ['entryId', 'siteId'], false);
        $this->createIndex(null, Table::ENTRYDRAFTS, ['siteId'], false);
        $this->createIndex(null, Table::ENTRYDRAFTS, ['creatorId'], false);
        $this->createIndex(null, Table::ENTRYTYPES, ['name', 'sectionId'], false);
        $this->createIndex(null, Table::ENTRYTYPES, ['handle', 'sectionId'], false);
        $this->createIndex(null, Table::ENTRYTYPES, ['sectionId'], false);
        $this->createIndex(null, Table::ENTRYTYPES, ['fieldLayoutId'], false);
        $this->createIndex(null, Table::ENTRYTYPES, ['dateDeleted'], false);
        $this->createIndex(null, Table::ENTRYVERSIONS, ['sectionId'], false);
        $this->createIndex(null, Table::ENTRYVERSIONS, ['entryId', 'siteId'], false);
        $this->createIndex(null, Table::ENTRYVERSIONS, ['siteId'], false);
        $this->createIndex(null, Table::ENTRYVERSIONS, ['creatorId'], false);
        $this->createIndex(null, Table::FIELDGROUPS, ['name'], true);
        $this->createIndex(null, Table::FIELDLAYOUTFIELDS, ['layoutId', 'fieldId'], true);
        $this->createIndex(null, Table::FIELDLAYOUTFIELDS, ['sortOrder'], false);
        $this->createIndex(null, Table::FIELDLAYOUTFIELDS, ['tabId'], false);
        $this->createIndex(null, Table::FIELDLAYOUTFIELDS, ['fieldId'], false);
        $this->createIndex(null, Table::FIELDLAYOUTS, ['dateDeleted'], false);
        $this->createIndex(null, Table::FIELDLAYOUTS, ['type'], false);
        $this->createIndex(null, Table::FIELDLAYOUTTABS, ['sortOrder'], false);
        $this->createIndex(null, Table::FIELDLAYOUTTABS, ['layoutId'], false);
        $this->createIndex(null, Table::FIELDS, ['handle', 'context'], true);
        $this->createIndex(null, Table::FIELDS, ['groupId'], false);
        $this->createIndex(null, Table::FIELDS, ['context'], false);
        $this->createIndex(null, Table::GLOBALSETS, ['name'], true);
        $this->createIndex(null, Table::GLOBALSETS, ['handle'], true);
        $this->createIndex(null, Table::GLOBALSETS, ['fieldLayoutId'], false);
        $this->createIndex(null, Table::MATRIXBLOCKS, ['ownerId'], false);
        $this->createIndex(null, Table::MATRIXBLOCKS, ['fieldId'], false);
        $this->createIndex(null, Table::MATRIXBLOCKS, ['typeId'], false);
        $this->createIndex(null, Table::MATRIXBLOCKS, ['sortOrder'], false);
        $this->createIndex(null, Table::MATRIXBLOCKS, ['ownerSiteId'], false);
        $this->createIndex(null, Table::MATRIXBLOCKTYPES, ['name', 'fieldId'], true);
        $this->createIndex(null, Table::MATRIXBLOCKTYPES, ['handle', 'fieldId'], true);
        $this->createIndex(null, Table::MATRIXBLOCKTYPES, ['fieldId'], false);
        $this->createIndex(null, Table::MATRIXBLOCKTYPES, ['fieldLayoutId'], false);
        $this->createIndex(null, Table::MIGRATIONS, ['pluginId'], false);
        $this->createIndex(null, Table::MIGRATIONS, ['type', 'pluginId'], false);
        $this->createIndex(null, Table::PLUGINS, ['handle'], true);
        $this->createIndex(null, Table::QUEUE, ['fail', 'timeUpdated', 'timePushed']);
        $this->createIndex(null, Table::QUEUE, ['fail', 'timeUpdated', 'delay']);
        $this->createIndex(null, Table::RELATIONS, ['fieldId', 'sourceId', 'sourceSiteId', 'targetId'], true);
        $this->createIndex(null, Table::RELATIONS, ['sourceId'], false);
        $this->createIndex(null, Table::RELATIONS, ['targetId'], false);
        $this->createIndex(null, Table::RELATIONS, ['sourceSiteId'], false);
        $this->createIndex(null, Table::SECTIONS, ['handle'], false);
        $this->createIndex(null, Table::SECTIONS, ['name'], false);
        $this->createIndex(null, Table::SECTIONS, ['structureId'], false);
        $this->createIndex(null, Table::SECTIONS, ['dateDeleted'], false);
        $this->createIndex(null, Table::SECTIONS_SITES, ['sectionId', 'siteId'], true);
        $this->createIndex(null, Table::SECTIONS_SITES, ['siteId'], false);
        $this->createIndex(null, Table::SESSIONS, ['uid'], false);
        $this->createIndex(null, Table::SESSIONS, ['token'], false);
        $this->createIndex(null, Table::SESSIONS, ['dateUpdated'], false);
        $this->createIndex(null, Table::SESSIONS, ['userId'], false);
        $this->createIndex(null, Table::SHUNNEDMESSAGES, ['userId', 'message'], true);
        $this->createIndex(null, Table::SITES, ['dateDeleted'], false);
        $this->createIndex(null, Table::SITES, ['handle'], false);
        $this->createIndex(null, Table::SITES, ['sortOrder'], false);
        $this->createIndex(null, Table::SITEGROUPS, ['name'], false);
        $this->createIndex(null, Table::STRUCTUREELEMENTS, ['structureId', 'elementId'], true);
        $this->createIndex(null, Table::STRUCTUREELEMENTS, ['root'], false);
        $this->createIndex(null, Table::STRUCTUREELEMENTS, ['lft'], false);
        $this->createIndex(null, Table::STRUCTUREELEMENTS, ['rgt'], false);
        $this->createIndex(null, Table::STRUCTUREELEMENTS, ['level'], false);
        $this->createIndex(null, Table::STRUCTUREELEMENTS, ['elementId'], false);
        $this->createIndex(null, Table::STRUCTURES, ['dateDeleted'], false);
        $this->createIndex(null, Table::TAGGROUPS, ['name'], false);
        $this->createIndex(null, Table::TAGGROUPS, ['handle'], false);
        $this->createIndex(null, Table::TAGGROUPS, ['dateDeleted'], false);
        $this->createIndex(null, Table::TAGS, ['groupId'], false);
        $this->createIndex(null, Table::TEMPLATECACHEELEMENTS, ['cacheId'], false);
        $this->createIndex(null, Table::TEMPLATECACHEELEMENTS, ['elementId'], false);
        $this->createIndex(null, Table::TEMPLATECACHEQUERIES, ['cacheId'], false);
        $this->createIndex(null, Table::TEMPLATECACHEQUERIES, ['type'], false);
        $this->createIndex(null, Table::TEMPLATECACHES, ['cacheKey', 'siteId', 'expiryDate', 'path'], false);
        $this->createIndex(null, Table::TEMPLATECACHES, ['cacheKey', 'siteId', 'expiryDate'], false);
        $this->createIndex(null, Table::TEMPLATECACHES, ['siteId'], false);
        $this->createIndex(null, Table::TOKENS, ['token'], true);
        $this->createIndex(null, Table::TOKENS, ['expiryDate'], false);
        $this->createIndex(null, Table::USERGROUPS, ['handle'], true);
        $this->createIndex(null, Table::USERGROUPS, ['name'], true);
        $this->createIndex(null, Table::USERGROUPS_USERS, ['groupId', 'userId'], true);
        $this->createIndex(null, Table::USERGROUPS_USERS, ['userId'], false);
        $this->createIndex(null, Table::USERPERMISSIONS, ['name'], true);
        $this->createIndex(null, Table::USERPERMISSIONS_USERGROUPS, ['permissionId', 'groupId'], true);
        $this->createIndex(null, Table::USERPERMISSIONS_USERGROUPS, ['groupId'], false);
        $this->createIndex(null, Table::USERPERMISSIONS_USERS, ['permissionId', 'userId'], true);
        $this->createIndex(null, Table::USERPERMISSIONS_USERS, ['userId'], false);
        $this->createIndex(null, Table::USERS, ['uid'], false);
        $this->createIndex(null, Table::USERS, ['verificationCode'], false);
        $this->createIndex(null, Table::VOLUMEFOLDERS, ['name', 'parentId', 'volumeId'], true);
        $this->createIndex(null, Table::VOLUMEFOLDERS, ['parentId'], false);
        $this->createIndex(null, Table::VOLUMEFOLDERS, ['volumeId'], false);
        $this->createIndex(null, Table::VOLUMES, ['name'], false);
        $this->createIndex(null, Table::VOLUMES, ['handle'], false);
        $this->createIndex(null, Table::VOLUMES, ['fieldLayoutId'], false);
        $this->createIndex(null, Table::VOLUMES, ['dateDeleted'], false);
        $this->createIndex(null, Table::WIDGETS, ['userId'], false);

        if ($this->db->getIsMysql()) {
            $this->createIndex(null, Table::ELEMENTS_SITES, ['uri', 'siteId']);
            $this->createIndex(null, Table::USERS, ['email']);
            $this->createIndex(null, Table::USERS, ['username']);

            // Add the FULLTEXT index on searchindex.keywords
            $this->createTable(Table::SEARCHINDEX, [
                'elementId' => $this->integer()->notNull(),
                'attribute' => $this->string(25)->notNull(),
                'fieldId' => $this->integer()->notNull(),
                'siteId' => $this->integer()->notNull(),
                'keywords' => $this->text()->notNull(),
            ], ' ENGINE=MyISAM');

            $this->addPrimaryKey($this->db->getIndexName(Table::SEARCHINDEX, 'elementId,attribute,fieldId,siteId', true), Table::SEARCHINDEX, 'elementId,attribute,fieldId,siteId');

            $sql = 'CREATE FULLTEXT INDEX ' .
                $this->db->quoteTableName($this->db->getIndexName(Table::SEARCHINDEX, 'keywords')) . ' ON ' .
                $this->db->quoteTableName(Table::SEARCHINDEX) . ' ' .
                '(' . $this->db->quoteColumnName('keywords') . ')';

            $this->db->createCommand($sql)->execute();
        } else {
            // Postgres is case-sensitive
            $this->createIndex($this->db->getIndexName(Table::ELEMENTS_SITES, ['uri', 'siteId']), Table::ELEMENTS_SITES, ['lower([[uri]])', 'siteId']);
            $this->createIndex($this->db->getIndexName(Table::USERS, ['email']), Table::USERS, ['lower([[email]])']);
            $this->createIndex($this->db->getIndexName(Table::USERS, ['username']), Table::USERS, ['lower([[username]])']);

            $this->createTable(Table::SEARCHINDEX, [
                'elementId' => $this->integer()->notNull(),
                'attribute' => $this->string(25)->notNull(),
                'fieldId' => $this->integer()->notNull(),
                'siteId' => $this->integer()->notNull(),
                'keywords' => $this->text()->notNull(),
                'keywords_vector' => $this->getDb()->getSchema()->createColumnSchemaBuilder('tsvector')->notNull(),
            ]);

            $this->addPrimaryKey($this->db->getIndexName(Table::SEARCHINDEX, 'elementId,attribute,fieldId,siteId', true), Table::SEARCHINDEX, 'elementId,attribute,fieldId,siteId');

            $sql = 'CREATE INDEX ' . $this->db->quoteTableName($this->db->getIndexName(Table::SEARCHINDEX, 'keywords_vector')) . ' ON {{%searchindex}} USING GIN([[keywords_vector]] [[pg_catalog]].[[tsvector_ops]]) WITH (FASTUPDATE=YES)';
            $this->db->createCommand($sql)->execute();

            $sql = 'CREATE INDEX ' . $this->db->quoteTableName($this->db->getIndexName(Table::SEARCHINDEX, 'keywords')) . ' ON {{%searchindex}} USING btree(keywords)';
            $this->db->createCommand($sql)->execute();
        }
    }

    /**
     * Adds the foreign keys.
     */
    public function addForeignKeys()
    {
        $this->addForeignKey(null, Table::ASSETINDEXDATA, ['volumeId'], Table::VOLUMES, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ASSETS, ['folderId'], Table::VOLUMEFOLDERS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ASSETS, ['id'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ASSETS, ['volumeId'], Table::VOLUMES, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::CATEGORIES, ['groupId'], Table::CATEGORYGROUPS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::CATEGORIES, ['id'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::CATEGORIES, ['parentId'], Table::CATEGORIES, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::CATEGORYGROUPS, ['fieldLayoutId'], Table::FIELDLAYOUTS, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::CATEGORYGROUPS, ['structureId'], Table::STRUCTURES, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::CATEGORYGROUPS_SITES, ['groupId'], Table::CATEGORYGROUPS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::CATEGORYGROUPS_SITES, ['siteId'], Table::SITES, ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, Table::CONTENT, ['elementId'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::CRAFTIDTOKENS, ['userId'], Table::USERS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::CONTENT, ['siteId'], Table::SITES, ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, Table::ELEMENTS, ['fieldLayoutId'], Table::FIELDLAYOUTS, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::ELEMENTS_SITES, ['elementId'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ELEMENTS_SITES, ['siteId'], Table::SITES, ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, Table::ENTRIES, ['authorId'], Table::USERS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ENTRIES, ['id'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ENTRIES, ['sectionId'], Table::SECTIONS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ENTRIES, ['parentId'], Table::ENTRIES, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::ENTRIES, ['typeId'], Table::ENTRYTYPES, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ENTRYDRAFTS, ['creatorId'], Table::USERS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ENTRYDRAFTS, ['entryId'], Table::ENTRIES, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ENTRYDRAFTS, ['siteId'], Table::SITES, ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, Table::ENTRYDRAFTS, ['sectionId'], Table::SECTIONS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ENTRYTYPES, ['fieldLayoutId'], Table::FIELDLAYOUTS, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::ENTRYTYPES, ['sectionId'], Table::SECTIONS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ENTRYVERSIONS, ['creatorId'], Table::USERS, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::ENTRYVERSIONS, ['entryId'], Table::ENTRIES, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ENTRYVERSIONS, ['siteId'], Table::SITES, ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, Table::ENTRYVERSIONS, ['sectionId'], Table::SECTIONS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FIELDLAYOUTFIELDS, ['fieldId'], Table::FIELDS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FIELDLAYOUTFIELDS, ['layoutId'], Table::FIELDLAYOUTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FIELDLAYOUTFIELDS, ['tabId'], Table::FIELDLAYOUTTABS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FIELDLAYOUTTABS, ['layoutId'], Table::FIELDLAYOUTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FIELDS, ['groupId'], Table::FIELDGROUPS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::GLOBALSETS, ['fieldLayoutId'], Table::FIELDLAYOUTS, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::GLOBALSETS, ['id'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::MATRIXBLOCKS, ['fieldId'], Table::FIELDS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::MATRIXBLOCKS, ['id'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::MATRIXBLOCKS, ['ownerId'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::MATRIXBLOCKS, ['ownerSiteId'], Table::SITES, ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, Table::MATRIXBLOCKS, ['typeId'], Table::MATRIXBLOCKTYPES, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::MATRIXBLOCKTYPES, ['fieldId'], Table::FIELDS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::MATRIXBLOCKTYPES, ['fieldLayoutId'], Table::FIELDLAYOUTS, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::MIGRATIONS, ['pluginId'], Table::PLUGINS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::RELATIONS, ['fieldId'], Table::FIELDS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::RELATIONS, ['sourceId'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::RELATIONS, ['sourceSiteId'], Table::SITES, ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, Table::RELATIONS, ['targetId'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::SECTIONS, ['structureId'], Table::STRUCTURES, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::SECTIONS_SITES, ['siteId'], Table::SITES, ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, Table::SECTIONS_SITES, ['sectionId'], Table::SECTIONS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::SESSIONS, ['userId'], Table::USERS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::SHUNNEDMESSAGES, ['userId'], Table::USERS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::SITES, ['groupId'], Table::SITEGROUPS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::STRUCTUREELEMENTS, ['elementId'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::STRUCTUREELEMENTS, ['structureId'], Table::STRUCTURES, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::TAGGROUPS, ['fieldLayoutId'], Table::FIELDLAYOUTS, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::TAGS, ['groupId'], Table::TAGGROUPS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::TAGS, ['id'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::TEMPLATECACHEELEMENTS, ['cacheId'], Table::TEMPLATECACHES, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::TEMPLATECACHEELEMENTS, ['elementId'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::TEMPLATECACHEQUERIES, ['cacheId'], Table::TEMPLATECACHES, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::TEMPLATECACHES, ['siteId'], Table::SITES, ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, Table::USERGROUPS_USERS, ['groupId'], Table::USERGROUPS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::USERGROUPS_USERS, ['userId'], Table::USERS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::USERPERMISSIONS_USERGROUPS, ['groupId'], Table::USERGROUPS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::USERPERMISSIONS_USERGROUPS, ['permissionId'], Table::USERPERMISSIONS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::USERPERMISSIONS_USERS, ['permissionId'], Table::USERPERMISSIONS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::USERPERMISSIONS_USERS, ['userId'], Table::USERS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::USERPREFERENCES, ['userId'], Table::USERS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::USERS, ['id'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::USERS, ['photoId'], Table::ASSETS, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::VOLUMEFOLDERS, ['parentId'], Table::VOLUMEFOLDERS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::VOLUMEFOLDERS, ['volumeId'], Table::VOLUMES, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::VOLUMES, ['fieldLayoutId'], Table::FIELDLAYOUTS, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::WIDGETS, ['userId'], Table::USERS, ['id'], 'CASCADE', null);
    }

    /**
     * Populates the DB with the default data.
     */
    public function insertDefaultData()
    {
        // Populate the info table
        echo '    > populating the info table ... ';
        Craft::$app->saveInfo(new Info([
            'version' => Craft::$app->getVersion(),
            'schemaVersion' => Craft::$app->schemaVersion,
            'maintenance' => false,
            'fieldVersion' => StringHelper::randomString(12),
            'config' => serialize([]),
            'configMap' => Json::encode([]),
        ]));
        echo "done\n";

        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $projectConfig = Craft::$app->getProjectConfig();
        $applyExistingProjectConfig = false;

        if ($generalConfig->useProjectConfigFile) {
            $configFile = Craft::$app->getPath()->getProjectConfigFilePath();
            if (file_exists($configFile)) {
                try {
                    $expectedSchemaVersion = (string)$projectConfig->get(ProjectConfig::CONFIG_SCHEMA_VERSION_KEY, true);
                    $craftSchemaVersion = (string)Craft::$app->schemaVersion;

                    // Compare existing Craft schema version with the one that is being applied.
                    if (!version_compare($craftSchemaVersion, $expectedSchemaVersion, '=')) {
                        throw new InvalidConfigException("Craft is installed at the wrong schema version ({$craftSchemaVersion}, but project.yaml lists {$expectedSchemaVersion}).");
                    }

                    // Make sure at least sites are processed
                    ProjectConfigHelper::ensureAllSitesProcessed();

                    $this->_installPlugins();
                    $applyExistingProjectConfig = true;
                } catch (\Throwable $e) {
                    echo "    > can't apply existing project config: {$e->getMessage()}\n";
                    Craft::$app->getErrorHandler()->logException($e);

                    // Rename project.yaml so we can create a new one
                    $backupFile = pathinfo(ProjectConfig::CONFIG_FILENAME, PATHINFO_FILENAME) . date('-Ymh-His') . '.yaml';
                    echo "    > renaming project.yaml to {$backupFile} and moving to config backup folder ... ";
                    rename($configFile, Craft::$app->getPath()->getConfigBackupPath() . '/' . $backupFile);
                    echo "done\n";

                    // Forget everything we knew about the old config
                    $projectConfig->reset();
                }
            }
        }

        if ($applyExistingProjectConfig) {
            // Save the existing system settings
            echo '    > applying existing project config ... ';
            $projectConfig->applyYamlChanges();
            echo "done\n";
        } else {
            // Save the default system settings
            echo '    > saving default site data ... ';
            $configData = $this->_generateInitialConfig();
            $projectConfig->applyConfigChanges($configData);
            echo "done\n";
        }

        // Craft, you are installed now.
        Craft::$app->setIsInstalled();

        if ($applyExistingProjectConfig) {
            // Update the primary site with the installer settings
            $sitesService = Craft::$app->getSites();
            $site = $sitesService->getPrimarySite();
            $site->baseUrl = $this->site->baseUrl;
            $site->hasUrls = $this->site->hasUrls;
            $site->language = $this->site->language;
            $site->name = $this->site->name;
            $sitesService->saveSite($site);
        }

        // Set the app language
        Craft::$app->language = $this->site->language;

        // Save the first user
        echo '    > saving the first user ... ';
        $user = new User([
            'username' => $this->username,
            'newPassword' => $this->password,
            'email' => $this->email,
            'admin' => true
        ]);
        Craft::$app->getElements()->saveElement($user);
        echo "done\n";

        // Set their preferred language
        Craft::$app->getUsers()->saveUserPreferences($user, [
            'language' => $this->site->language,
        ]);

        // Log them in
        if (!Craft::$app->getRequest()->getIsConsoleRequest()) {
            Craft::$app->getUser()->login($user, $generalConfig->userSessionDuration);
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * Attempts to install any plugins listed in project.yaml.
     *
     * @throws \Throwable if reasons
     */
    private function _installPlugins()
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $pluginsService = Craft::$app->getPlugins();
        $pluginConfigs = $projectConfig->get(Plugins::CONFIG_PLUGINS_KEY, true) ?? [];

        // Make sure that all to-be-installed plugins actually exist,
        // and that they have the same schema as project.yaml
        foreach ($pluginConfigs as $handle => $config) {
            $plugin = $pluginsService->createPlugin($handle);
            $expectedSchemaVersion = $projectConfig->get(Plugins::CONFIG_PLUGINS_KEY . '.' . $handle . '.schemaVersion', true);

            /** @var Plugin|null $plugin */
            if ($plugin->schemaVersion && $expectedSchemaVersion && $plugin->schemaVersion != $expectedSchemaVersion) {
                throw new InvalidPluginException($handle, "{$handle} is installed at the wrong schema version ({$plugin->schemaVersion}, but project.yaml lists {$expectedSchemaVersion}).");
            }
        }

        // Prevent the plugin from sending any headers, etc.
        $realResponse = Craft::$app->getResponse();
        $tempResponse = new Response(['isSent' => true]);
        Craft::$app->set('response', $tempResponse);

        $e = null;

        try {
            foreach ($pluginConfigs as $handle => $pluginConfig) {
                echo "    > installing {$handle} ... ";
                $pluginsService->installPlugin($handle);
                echo "done\n";
            }
        } catch (\Throwable $e) {
        }

        // Put the real response back
        Craft::$app->set('response', $realResponse);

        if ($e !== null) {
            throw $e;
        }
    }

    /**
     * Generates the initial project config.
     *
     * @return array
     */
    private function _generateInitialConfig(): array
    {
        $siteGroupUid = StringHelper::UUID();

        return [
            'fieldGroups' => [
                StringHelper::UUID() => [
                    'name' => 'Common',
                ],
            ],
            'email' => [
                'fromEmail' => $this->email,
                'fromName' => $this->site->name,
                'transportType' => Sendmail::class,
            ],
            'siteGroups' => [
                $siteGroupUid => [
                    'name' => $this->site->name,
                ],
            ],
            'sites' => [
                StringHelper::UUID() => [
                    'baseUrl' => $this->site->baseUrl,
                    'handle' => $this->site->handle,
                    'hasUrls' => $this->site->hasUrls,
                    'language' => $this->site->language,
                    'name' => $this->site->name,
                    'primary' => true,
                    'siteGroup' => $siteGroupUid,
                    'sortOrder' => 1,
                ],
            ],
            'system' => [
                'edition' => App::editionHandle(Craft::Solo),
                'name' => $this->site->name,
                'live' => true,
                'schemaVersion' => Craft::$app->schemaVersion,
                'timeZone' => 'America/Los_Angeles',
            ],
            'users' => [
                'requireEmailVerification' => true,
                'allowPublicRegistration' => false,
                'defaultGroup' => null,
                'photoVolumeUid' => null,
                'photoSubpath' => '',
            ],
        ];
    }
}
