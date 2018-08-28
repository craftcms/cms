<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\elements\Asset;
use craft\elements\User;
use craft\helpers\StringHelper;
use craft\mail\transportadapters\Sendmail;
use craft\models\FieldGroup;
use craft\models\Info;
use craft\models\Site;
use craft\models\SiteGroup;

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

        // Craft, you are installed now.
        Craft::$app->setIsInstalled();

        // Set the app language
        Craft::$app->language = $this->site->language;

        // Save the first user
        echo '    > save the first user ...';
        $user = new User([
            'username' => $this->username,
            'newPassword' => $this->password,
            'email' => $this->email,
            'admin' => true
        ]);
        Craft::$app->getElements()->saveElement($user);
        echo " done\n";

        // Set their preferred language
        Craft::$app->getUsers()->saveUserPreferences($user, [
            'language' => $this->site->language,
        ]);

        // Log them in
        if (!Craft::$app->getRequest()->getIsConsoleRequest()) {
            Craft::$app->getUser()->login($user);
        }

        // Save the default email settings
        echo '    > save the email settings ...';
        Craft::$app->getSystemSettings()->saveSettings('email', [
            'fromEmail' => $this->email,
            'fromName' => $this->site->name,
            'transportType' => Sendmail::class
        ]);
        echo " done\n";
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
        $this->createTable('{{%assetindexdata}}', [
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
        $this->createTable('{{%assets}}', [
            'id' => $this->integer()->notNull(),
            'volumeId' => $this->integer(),
            'folderId' => $this->integer()->notNull(),
            'filename' => $this->string()->notNull(),
            'kind' => $this->string(50)->notNull()->defaultValue(Asset::KIND_UNKNOWN),
            'width' => $this->integer()->unsigned(),
            'height' => $this->integer()->unsigned(),
            'size' => $this->bigInteger()->unsigned(),
            'focalPoint' => $this->string(13)->null(),
            'dateModified' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
            'PRIMARY KEY([[id]])',
        ]);
        $this->createTable('{{%assettransformindex}}', [
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
        $this->createTable('{{%assettransforms}}', [
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
        $this->createTable('{{%categories}}', [
            'id' => $this->integer()->notNull(),
            'groupId' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
            'PRIMARY KEY([[id]])',
        ]);
        $this->createTable('{{%categorygroups}}', [
            'id' => $this->primaryKey(),
            'structureId' => $this->integer()->notNull(),
            'fieldLayoutId' => $this->integer(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable('{{%categorygroups_sites}}', [
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
        $this->createTable('{{%content}}', [
            'id' => $this->primaryKey(),
            'elementId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'title' => $this->string(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable('{{%craftidtokens}}', [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'accessToken' => $this->text()->notNull(),
            'expiryDate' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable('{{%deprecationerrors}}', [
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
        $this->createTable('{{%elementindexsettings}}', [
            'id' => $this->primaryKey(),
            'type' => $this->string()->notNull(),
            'settings' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable('{{%elements}}', [
            'id' => $this->primaryKey(),
            'fieldLayoutId' => $this->integer(),
            'type' => $this->string()->notNull(),
            'enabled' => $this->boolean()->defaultValue(true)->notNull(),
            'archived' => $this->boolean()->defaultValue(false)->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable('{{%elements_sites}}', [
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
        $this->createTable('{{%resourcepaths}}', [
            'hash' => $this->string()->notNull(),
            'path' => $this->string()->notNull(),
            'PRIMARY KEY([[hash]])',
        ]);
        $this->createTable('{{%systemmessages}}', [
            'id' => $this->primaryKey(),
            'language' => $this->string()->notNull(),
            'key' => $this->string()->notNull(),
            'subject' => $this->text()->notNull(),
            'body' => $this->text()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable('{{%entries}}', [
            'id' => $this->integer()->notNull(),
            'sectionId' => $this->integer()->notNull(),
            'typeId' => $this->integer()->notNull(),
            'authorId' => $this->integer(),
            'postDate' => $this->dateTime(),
            'expiryDate' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
            'PRIMARY KEY([[id]])',
        ]);
        $this->createTable('{{%entrydrafts}}', [
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
        $this->createTable('{{%entrytypes}}', [
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
            'uid' => $this->uid(),
        ]);
        $this->createTable('{{%entryversions}}', [
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
        $this->createTable('{{%fieldgroups}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable('{{%fieldlayoutfields}}', [
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
        $this->createTable('{{%fieldlayouts}}', [
            'id' => $this->primaryKey(),
            'type' => $this->string()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable('{{%fieldlayouttabs}}', [
            'id' => $this->primaryKey(),
            'layoutId' => $this->integer()->notNull(),
            'name' => $this->string()->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable('{{%fields}}', [
            'id' => $this->primaryKey(),
            'groupId' => $this->integer(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string(64)->notNull(),
            'context' => $this->string()->notNull()->defaultValue('global'),
            'instructions' => $this->text(),
            'translationMethod' => $this->string()->notNull()->defaultValue('none'),
            'translationKeyFormat' => $this->text(),
            'type' => $this->string()->notNull(),
            'settings' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable('{{%globalsets}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'fieldLayoutId' => $this->integer(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable('{{%info}}', [
            'id' => $this->primaryKey(),
            'version' => $this->string(50)->notNull(),
            'schemaVersion' => $this->string(15)->notNull(),
            'edition' => $this->tinyInteger()->unsigned()->notNull(),
            'timezone' => $this->string(30),
            'name' => $this->string()->notNull(),
            'on' => $this->boolean()->defaultValue(false)->notNull(),
            'maintenance' => $this->boolean()->defaultValue(false)->notNull(),
            'fieldVersion' => $this->char(12)->notNull()->defaultValue('000000000000'),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable('{{%matrixblocks}}', [
            'id' => $this->integer()->notNull(),
            'ownerId' => $this->integer()->notNull(),
            'ownerSiteId' => $this->integer(),
            'fieldId' => $this->integer()->notNull(),
            'typeId' => $this->integer()->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
            'PRIMARY KEY([[id]])',
        ]);
        $this->createTable('{{%matrixblocktypes}}', [
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
        $this->createTable('{{%migrations}}', [
            'id' => $this->primaryKey(),
            'pluginId' => $this->integer(),
            'type' => $this->enum('type', ['app', 'plugin', 'content'])->notNull()->defaultValue('app'),
            'name' => $this->string()->notNull(),
            'applyTime' => $this->dateTime()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable('{{%plugins}}', [
            'id' => $this->primaryKey(),
            'handle' => $this->string()->notNull(),
            'version' => $this->string()->notNull(),
            'schemaVersion' => $this->string()->notNull(),
            'licenseKey' => $this->char(24),
            'licenseKeyStatus' => $this->enum('licenseKeyStatus', ['valid', 'invalid', 'mismatched', 'astray', 'unknown'])->notNull()->defaultValue('unknown'),
            'enabled' => $this->boolean()->defaultValue(false)->notNull(),
            'settings' => $this->text(),
            'installDate' => $this->dateTime()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable('{{%queue}}', [
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
        $this->createTable('{{%relations}}', [
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
        $this->createTable('{{%routes}}', [
            'id' => $this->primaryKey(),
            'siteId' => $this->integer(),
            'uriParts' => $this->string()->notNull(),
            'uriPattern' => $this->string()->notNull(),
            'template' => $this->string(500)->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable('{{%sections}}', [
            'id' => $this->primaryKey(),
            'structureId' => $this->integer(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'type' => $this->enum('type', ['single', 'channel', 'structure'])->notNull()->defaultValue('channel'),
            'enableVersioning' => $this->boolean()->defaultValue(false)->notNull(),
            'propagateEntries' => $this->boolean()->defaultValue(true)->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable('{{%sections_sites}}', [
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
        $this->createTable('{{%sessions}}', [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'token' => $this->char(100)->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable('{{%shunnedmessages}}', [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'message' => $this->string()->notNull(),
            'expiryDate' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable('{{%sites}}', [
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
            'uid' => $this->uid(),
        ]);
        $this->createTable('{{%sitegroups}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable('{{%structureelements}}', [
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
        $this->createTable('{{%structures}}', [
            'id' => $this->primaryKey(),
            'maxLevels' => $this->smallInteger()->unsigned(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable('{{%systemsettings}}', [
            'id' => $this->primaryKey(),
            'category' => $this->string(15)->notNull(),
            'settings' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable('{{%taggroups}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'fieldLayoutId' => $this->integer(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable('{{%tags}}', [
            'id' => $this->integer()->notNull(),
            'groupId' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
            'PRIMARY KEY([[id]])',
        ]);
        $this->createTable('{{%templatecacheelements}}', [
            'cacheId' => $this->integer()->notNull(),
            'elementId' => $this->integer()->notNull(),
        ]);
        $this->createTable('{{%templatecachequeries}}', [
            'id' => $this->primaryKey(),
            'cacheId' => $this->integer()->notNull(),
            'type' => $this->string()->notNull(),
            'query' => $this->longText()->notNull(),
        ]);
        $this->createTable('{{%templatecaches}}', [
            'id' => $this->primaryKey(),
            'siteId' => $this->integer()->notNull(),
            'cacheKey' => $this->string()->notNull(),
            'path' => $this->string(),
            'expiryDate' => $this->dateTime()->notNull(),
            'body' => $this->mediumText()->notNull(),
        ]);
        $this->createTable('{{%tokens}}', [
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
        $this->createTable('{{%usergroups}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable('{{%usergroups_users}}', [
            'id' => $this->primaryKey(),
            'groupId' => $this->integer()->notNull(),
            'userId' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable('{{%userpermissions}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable('{{%userpermissions_usergroups}}', [
            'id' => $this->primaryKey(),
            'permissionId' => $this->integer()->notNull(),
            'groupId' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable('{{%userpermissions_users}}', [
            'id' => $this->primaryKey(),
            'permissionId' => $this->integer()->notNull(),
            'userId' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable('{{%userpreferences}}', [
            'userId' => $this->primaryKey(),
            'preferences' => $this->text(),
        ]);
        $this->createTable('{{%users}}', [
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
        $this->createTable('{{%volumefolders}}', [
            'id' => $this->primaryKey(),
            'parentId' => $this->integer(),
            'volumeId' => $this->integer(),
            'name' => $this->string()->notNull(),
            'path' => $this->string(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable('{{%volumes}}', [
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
            'uid' => $this->uid(),
        ]);
        $this->createTable('{{%widgets}}', [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'type' => $this->string()->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'colspan' => $this->boolean()->defaultValue(false)->notNull(),
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
        $this->createIndex(null, '{{%assetindexdata}}', ['sessionId', 'volumeId']);
        $this->createIndex(null, '{{%assetindexdata}}', ['volumeId'], false);
        $this->createIndex(null, '{{%assets}}', ['filename', 'folderId'], true);
        $this->createIndex(null, '{{%assets}}', ['folderId'], false);
        $this->createIndex(null, '{{%assets}}', ['volumeId'], false);
        $this->createIndex(null, '{{%assettransformindex}}', ['volumeId', 'assetId', 'location'], false);
        $this->createIndex(null, '{{%assettransforms}}', ['name'], true);
        $this->createIndex(null, '{{%assettransforms}}', ['handle'], true);
        $this->createIndex(null, '{{%categories}}', ['groupId'], false);
        $this->createIndex(null, '{{%categorygroups}}', ['name'], true);
        $this->createIndex(null, '{{%categorygroups}}', ['handle'], true);
        $this->createIndex(null, '{{%categorygroups}}', ['structureId'], false);
        $this->createIndex(null, '{{%categorygroups}}', ['fieldLayoutId'], false);
        $this->createIndex(null, '{{%categorygroups_sites}}', ['groupId', 'siteId'], true);
        $this->createIndex(null, '{{%categorygroups_sites}}', ['siteId'], false);
        $this->createIndex(null, '{{%content}}', ['elementId', 'siteId'], true);
        $this->createIndex(null, '{{%content}}', ['siteId'], false);
        $this->createIndex(null, '{{%content}}', ['title'], false);
        $this->createIndex(null, '{{%deprecationerrors}}', ['key', 'fingerprint'], true);
        $this->createIndex(null, '{{%elementindexsettings}}', ['type'], true);
        $this->createIndex(null, '{{%elements}}', ['fieldLayoutId'], false);
        $this->createIndex(null, '{{%elements}}', ['type'], false);
        $this->createIndex(null, '{{%elements}}', ['enabled'], false);
        $this->createIndex(null, '{{%elements}}', ['archived', 'dateCreated'], false);
        $this->createIndex(null, '{{%elements_sites}}', ['elementId', 'siteId'], true);
        $this->createIndex(null, '{{%elements_sites}}', ['siteId'], false);
        $this->createIndex(null, '{{%elements_sites}}', ['slug', 'siteId'], false);
        $this->createIndex(null, '{{%elements_sites}}', ['enabled'], false);
        $this->createIndex(null, '{{%systemmessages}}', ['key', 'language'], true);
        $this->createIndex(null, '{{%systemmessages}}', ['language'], false);
        $this->createIndex(null, '{{%entries}}', ['postDate'], false);
        $this->createIndex(null, '{{%entries}}', ['expiryDate'], false);
        $this->createIndex(null, '{{%entries}}', ['authorId'], false);
        $this->createIndex(null, '{{%entries}}', ['sectionId'], false);
        $this->createIndex(null, '{{%entries}}', ['typeId'], false);
        $this->createIndex(null, '{{%entrydrafts}}', ['sectionId'], false);
        $this->createIndex(null, '{{%entrydrafts}}', ['entryId', 'siteId'], false);
        $this->createIndex(null, '{{%entrydrafts}}', ['siteId'], false);
        $this->createIndex(null, '{{%entrydrafts}}', ['creatorId'], false);
        $this->createIndex(null, '{{%entrytypes}}', ['name', 'sectionId'], true);
        $this->createIndex(null, '{{%entrytypes}}', ['handle', 'sectionId'], true);
        $this->createIndex(null, '{{%entrytypes}}', ['sectionId'], false);
        $this->createIndex(null, '{{%entrytypes}}', ['fieldLayoutId'], false);
        $this->createIndex(null, '{{%entryversions}}', ['sectionId'], false);
        $this->createIndex(null, '{{%entryversions}}', ['entryId', 'siteId'], false);
        $this->createIndex(null, '{{%entryversions}}', ['siteId'], false);
        $this->createIndex(null, '{{%entryversions}}', ['creatorId'], false);
        $this->createIndex(null, '{{%fieldgroups}}', ['name'], true);
        $this->createIndex(null, '{{%fieldlayoutfields}}', ['layoutId', 'fieldId'], true);
        $this->createIndex(null, '{{%fieldlayoutfields}}', ['sortOrder'], false);
        $this->createIndex(null, '{{%fieldlayoutfields}}', ['tabId'], false);
        $this->createIndex(null, '{{%fieldlayoutfields}}', ['fieldId'], false);
        $this->createIndex(null, '{{%fieldlayouts}}', ['type'], false);
        $this->createIndex(null, '{{%fieldlayouttabs}}', ['sortOrder'], false);
        $this->createIndex(null, '{{%fieldlayouttabs}}', ['layoutId'], false);
        $this->createIndex(null, '{{%fields}}', ['handle', 'context'], true);
        $this->createIndex(null, '{{%fields}}', ['groupId'], false);
        $this->createIndex(null, '{{%fields}}', ['context'], false);
        $this->createIndex(null, '{{%globalsets}}', ['name'], true);
        $this->createIndex(null, '{{%globalsets}}', ['handle'], true);
        $this->createIndex(null, '{{%globalsets}}', ['fieldLayoutId'], false);
        $this->createIndex(null, '{{%matrixblocks}}', ['ownerId'], false);
        $this->createIndex(null, '{{%matrixblocks}}', ['fieldId'], false);
        $this->createIndex(null, '{{%matrixblocks}}', ['typeId'], false);
        $this->createIndex(null, '{{%matrixblocks}}', ['sortOrder'], false);
        $this->createIndex(null, '{{%matrixblocks}}', ['ownerSiteId'], false);
        $this->createIndex(null, '{{%matrixblocktypes}}', ['name', 'fieldId'], true);
        $this->createIndex(null, '{{%matrixblocktypes}}', ['handle', 'fieldId'], true);
        $this->createIndex(null, '{{%matrixblocktypes}}', ['fieldId'], false);
        $this->createIndex(null, '{{%matrixblocktypes}}', ['fieldLayoutId'], false);
        $this->createIndex(null, '{{%migrations}}', ['pluginId'], false);
        $this->createIndex(null, '{{%migrations}}', ['type', 'pluginId'], false);
        $this->createIndex(null, '{{%plugins}}', ['enabled']);
        $this->createIndex(null, '{{%plugins}}', ['handle'], true);
        $this->createIndex(null, '{{%queue}}', ['fail', 'timeUpdated', 'timePushed']);
        $this->createIndex(null, '{{%queue}}', ['fail', 'timeUpdated', 'delay']);
        $this->createIndex(null, '{{%relations}}', ['fieldId', 'sourceId', 'sourceSiteId', 'targetId'], true);
        $this->createIndex(null, '{{%relations}}', ['sourceId'], false);
        $this->createIndex(null, '{{%relations}}', ['targetId'], false);
        $this->createIndex(null, '{{%relations}}', ['sourceSiteId'], false);
        $this->createIndex(null, '{{%routes}}', ['uriPattern'], false);
        $this->createIndex(null, '{{%routes}}', ['siteId'], false);
        $this->createIndex(null, '{{%sections}}', ['handle'], true);
        $this->createIndex(null, '{{%sections}}', ['name'], true);
        $this->createIndex(null, '{{%sections}}', ['structureId'], false);
        $this->createIndex(null, '{{%sections_sites}}', ['sectionId', 'siteId'], true);
        $this->createIndex(null, '{{%sections_sites}}', ['siteId'], false);
        $this->createIndex(null, '{{%sessions}}', ['uid'], false);
        $this->createIndex(null, '{{%sessions}}', ['token'], false);
        $this->createIndex(null, '{{%sessions}}', ['dateUpdated'], false);
        $this->createIndex(null, '{{%sessions}}', ['userId'], false);
        $this->createIndex(null, '{{%shunnedmessages}}', ['userId', 'message'], true);
        $this->createIndex(null, '{{%sites}}', ['handle'], true);
        $this->createIndex(null, '{{%sites}}', ['sortOrder'], false);
        $this->createIndex(null, '{{%sitegroups}}', ['name'], true);
        $this->createIndex(null, '{{%structureelements}}', ['structureId', 'elementId'], true);
        $this->createIndex(null, '{{%structureelements}}', ['root'], false);
        $this->createIndex(null, '{{%structureelements}}', ['lft'], false);
        $this->createIndex(null, '{{%structureelements}}', ['rgt'], false);
        $this->createIndex(null, '{{%structureelements}}', ['level'], false);
        $this->createIndex(null, '{{%structureelements}}', ['elementId'], false);
        $this->createIndex(null, '{{%systemsettings}}', ['category'], true);
        $this->createIndex(null, '{{%taggroups}}', ['name'], true);
        $this->createIndex(null, '{{%taggroups}}', ['handle'], true);
        $this->createIndex(null, '{{%tags}}', ['groupId'], false);
        $this->createIndex(null, '{{%templatecacheelements}}', ['cacheId'], false);
        $this->createIndex(null, '{{%templatecacheelements}}', ['elementId'], false);
        $this->createIndex(null, '{{%templatecachequeries}}', ['cacheId'], false);
        $this->createIndex(null, '{{%templatecachequeries}}', ['type'], false);
        $this->createIndex(null, '{{%templatecaches}}', ['cacheKey', 'siteId', 'expiryDate', 'path'], false);
        $this->createIndex(null, '{{%templatecaches}}', ['cacheKey', 'siteId', 'expiryDate'], false);
        $this->createIndex(null, '{{%templatecaches}}', ['siteId'], false);
        $this->createIndex(null, '{{%tokens}}', ['token'], true);
        $this->createIndex(null, '{{%tokens}}', ['expiryDate'], false);
        $this->createIndex(null, '{{%usergroups}}', ['handle'], true);
        $this->createIndex(null, '{{%usergroups}}', ['name'], true);
        $this->createIndex(null, '{{%usergroups_users}}', ['groupId', 'userId'], true);
        $this->createIndex(null, '{{%usergroups_users}}', ['userId'], false);
        $this->createIndex(null, '{{%userpermissions}}', ['name'], true);
        $this->createIndex(null, '{{%userpermissions_usergroups}}', ['permissionId', 'groupId'], true);
        $this->createIndex(null, '{{%userpermissions_usergroups}}', ['groupId'], false);
        $this->createIndex(null, '{{%userpermissions_users}}', ['permissionId', 'userId'], true);
        $this->createIndex(null, '{{%userpermissions_users}}', ['userId'], false);
        $this->createIndex(null, '{{%users}}', ['uid'], false);
        $this->createIndex(null, '{{%users}}', ['verificationCode'], false);
        $this->createIndex(null, '{{%volumefolders}}', ['name', 'parentId', 'volumeId'], true);
        $this->createIndex(null, '{{%volumefolders}}', ['parentId'], false);
        $this->createIndex(null, '{{%volumefolders}}', ['volumeId'], false);
        $this->createIndex(null, '{{%volumes}}', ['name'], true);
        $this->createIndex(null, '{{%volumes}}', ['handle'], true);
        $this->createIndex(null, '{{%volumes}}', ['fieldLayoutId'], false);
        $this->createIndex(null, '{{%widgets}}', ['userId'], false);

        if ($this->db->getIsMysql()) {
            $this->createIndex(null, '{{%elements_sites}}', ['uri', 'siteId']);
            $this->createIndex(null, '{{%users}}', ['email']);
            $this->createIndex(null, '{{%users}}', ['username']);

            // Add the FULLTEXT index on searchindex.keywords
            $this->createTable('{{%searchindex}}', [
                'elementId' => $this->integer()->notNull(),
                'attribute' => $this->string(25)->notNull(),
                'fieldId' => $this->integer()->notNull(),
                'siteId' => $this->integer()->notNull(),
                'keywords' => $this->text()->notNull(),
            ], ' ENGINE=MyISAM');

            $this->addPrimaryKey($this->db->getIndexName('{{%searchindex}}', 'elementId,attribute,fieldId,siteId', true), '{{%searchindex}}', 'elementId,attribute,fieldId,siteId');

            $sql = 'CREATE FULLTEXT INDEX ' .
                $this->db->quoteTableName($this->db->getIndexName('{{%searchindex}}', 'keywords')) . ' ON ' .
                $this->db->quoteTableName('{{%searchindex}}') . ' ' .
                '(' . $this->db->quoteColumnName('keywords') . ')';

            $this->db->createCommand($sql)->execute();
        } else {
            // Postgres is case-sensitive
            $this->createIndex($this->db->getIndexName('{{%elements_sites}}', ['uri', 'siteId']), '{{%elements_sites}}', ['lower([[uri]])', 'siteId']);
            $this->createIndex($this->db->getIndexName('{{%users}}', ['email']), '{{%users}}', ['lower([[email]])']);
            $this->createIndex($this->db->getIndexName('{{%users}}', ['username']), '{{%users}}', ['lower([[username]])']);

            $this->createTable('{{%searchindex}}', [
                'elementId' => $this->integer()->notNull(),
                'attribute' => $this->string(25)->notNull(),
                'fieldId' => $this->integer()->notNull(),
                'siteId' => $this->integer()->notNull(),
                'keywords' => $this->text()->notNull(),
                'keywords_vector' => $this->getDb()->getSchema()->createColumnSchemaBuilder('tsvector')->notNull(),
            ]);

            $this->addPrimaryKey($this->db->getIndexName('{{%searchindex}}', 'elementId,attribute,fieldId,siteId', true), '{{%searchindex}}', 'elementId,attribute,fieldId,siteId');

            $sql = 'CREATE INDEX ' . $this->db->quoteTableName($this->db->getIndexName('{{%searchindex}}', 'keywords_vector')) . ' ON {{%searchindex}} USING GIN([[keywords_vector]] [[pg_catalog]].[[tsvector_ops]]) WITH (FASTUPDATE=YES)';
            $this->db->createCommand($sql)->execute();

            $sql = 'CREATE INDEX ' . $this->db->quoteTableName($this->db->getIndexName('{{%searchindex}}', 'keywords')) . ' ON {{%searchindex}} USING btree(keywords)';
            $this->db->createCommand($sql)->execute();
        }
    }

    /**
     * Adds the foreign keys.
     */
    public function addForeignKeys()
    {
        $this->addForeignKey(null, '{{%assetindexdata}}', ['volumeId'], '{{%volumes}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%assets}}', ['folderId'], '{{%volumefolders}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%assets}}', ['id'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%assets}}', ['volumeId'], '{{%volumes}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%categories}}', ['groupId'], '{{%categorygroups}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%categories}}', ['id'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%categorygroups}}', ['fieldLayoutId'], '{{%fieldlayouts}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, '{{%categorygroups}}', ['structureId'], '{{%structures}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%categorygroups_sites}}', ['groupId'], '{{%categorygroups}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%categorygroups_sites}}', ['siteId'], '{{%sites}}', ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, '{{%content}}', ['elementId'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%craftidtokens}}', ['userId'], '{{%users}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%content}}', ['siteId'], '{{%sites}}', ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, '{{%elements}}', ['fieldLayoutId'], '{{%fieldlayouts}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, '{{%elements_sites}}', ['elementId'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%elements_sites}}', ['siteId'], '{{%sites}}', ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, '{{%entries}}', ['authorId'], '{{%users}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%entries}}', ['id'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%entries}}', ['sectionId'], '{{%sections}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%entries}}', ['typeId'], '{{%entrytypes}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%entrydrafts}}', ['creatorId'], '{{%users}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%entrydrafts}}', ['entryId'], '{{%entries}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%entrydrafts}}', ['siteId'], '{{%sites}}', ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, '{{%entrydrafts}}', ['sectionId'], '{{%sections}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%entrytypes}}', ['fieldLayoutId'], '{{%fieldlayouts}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, '{{%entrytypes}}', ['sectionId'], '{{%sections}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%entryversions}}', ['creatorId'], '{{%users}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, '{{%entryversions}}', ['entryId'], '{{%entries}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%entryversions}}', ['siteId'], '{{%sites}}', ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, '{{%entryversions}}', ['sectionId'], '{{%sections}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%fieldlayoutfields}}', ['fieldId'], '{{%fields}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%fieldlayoutfields}}', ['layoutId'], '{{%fieldlayouts}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%fieldlayoutfields}}', ['tabId'], '{{%fieldlayouttabs}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%fieldlayouttabs}}', ['layoutId'], '{{%fieldlayouts}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%fields}}', ['groupId'], '{{%fieldgroups}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%globalsets}}', ['fieldLayoutId'], '{{%fieldlayouts}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, '{{%globalsets}}', ['id'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%matrixblocks}}', ['fieldId'], '{{%fields}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%matrixblocks}}', ['id'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%matrixblocks}}', ['ownerId'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%matrixblocks}}', ['ownerSiteId'], '{{%sites}}', ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, '{{%matrixblocks}}', ['typeId'], '{{%matrixblocktypes}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%matrixblocktypes}}', ['fieldId'], '{{%fields}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%matrixblocktypes}}', ['fieldLayoutId'], '{{%fieldlayouts}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, '{{%migrations}}', ['pluginId'], '{{%plugins}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%relations}}', ['fieldId'], '{{%fields}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%relations}}', ['sourceId'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%relations}}', ['sourceSiteId'], '{{%sites}}', ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, '{{%relations}}', ['targetId'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%routes}}', ['siteId'], '{{%sites}}', ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, '{{%sections}}', ['structureId'], '{{%structures}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, '{{%sections_sites}}', ['siteId'], '{{%sites}}', ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, '{{%sections_sites}}', ['sectionId'], '{{%sections}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%sessions}}', ['userId'], '{{%users}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%shunnedmessages}}', ['userId'], '{{%users}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%sites}}', ['groupId'], '{{%sitegroups}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%structureelements}}', ['elementId'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%structureelements}}', ['structureId'], '{{%structures}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%taggroups}}', ['fieldLayoutId'], '{{%fieldlayouts}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, '{{%tags}}', ['groupId'], '{{%taggroups}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%tags}}', ['id'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%templatecacheelements}}', ['cacheId'], '{{%templatecaches}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%templatecacheelements}}', ['elementId'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%templatecachequeries}}', ['cacheId'], '{{%templatecaches}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%templatecaches}}', ['siteId'], '{{%sites}}', ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, '{{%usergroups_users}}', ['groupId'], '{{%usergroups}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%usergroups_users}}', ['userId'], '{{%users}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%userpermissions_usergroups}}', ['groupId'], '{{%usergroups}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%userpermissions_usergroups}}', ['permissionId'], '{{%userpermissions}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%userpermissions_users}}', ['permissionId'], '{{%userpermissions}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%userpermissions_users}}', ['userId'], '{{%users}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%userpreferences}}', ['userId'], '{{%users}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%users}}', ['id'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%users}}', ['photoId'], '{{%assets}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, '{{%volumefolders}}', ['parentId'], '{{%volumefolders}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%volumefolders}}', ['volumeId'], '{{%volumes}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%volumes}}', ['fieldLayoutId'], '{{%fieldlayouts}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, '{{%widgets}}', ['userId'], '{{%users}}', ['id'], 'CASCADE', null);
    }

    /**
     * Populates the DB with the default data.
     */
    public function insertDefaultData()
    {
        // Populate the info table
        echo '    > populate the info table ...';
        Craft::$app->saveInfo(new Info([
            'version' => Craft::$app->getVersion(),
            'schemaVersion' => Craft::$app->schemaVersion,
            'edition' => 0,
            'name' => $this->site->name,
            'on' => true,
            'maintenance' => false,
            'fieldVersion' => StringHelper::randomString(12),
        ]));
        echo " done\n";

        // Add the "Common" field group
        Craft::$app->getFields()->saveGroup(new FieldGroup([
            'name' => 'Common',
        ]));

        // Add the initial site group
        $sitesService = Craft::$app->getSites();
        $siteGroup = new SiteGroup([
            'name' => $this->site->name,
        ]);
        $sitesService->saveGroup($siteGroup);

        // Add the default site
        $this->site->groupId = $siteGroup->id;
        $this->site->primary = true;
        Craft::$app->getSites()->saveSite($this->site);
    }
}
