<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\migrations;

use Craft;
use craft\db\Connection;
use craft\db\Migration;
use craft\elements\User;
use craft\helpers\StringHelper;
use craft\mail\Mailer;
use craft\mail\transportadapters\Php;
use craft\models\Info;
use craft\models\Site;
use craft\services\Config;

/**
 * Installation Migration
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
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
     * @var string|null The database driver to use
     */
    public $driver;

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
        $this->driver = Craft::$app->getConfig()->get('driver', Config::CATEGORY_DB);
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

        // Log them in
        if (!Craft::$app->getRequest()->getIsConsoleRequest()) {
            Craft::$app->getUser()->login($user);
        }

        // Save the default email settings
        echo '    > save the email settings ...';
        Craft::$app->getSystemSettings()->saveSettings('email', [
            'fromEmail' => $this->email,
            'fromName' => $this->site->name,
            'transportType' => Php::class
        ]);
        Craft::$app->getSystemSettings()->saveSettings('mailer', [
            'class' => Mailer::class,
            'from' => [$this->email => $this->site->name],
            'transport' => [
                'class' => \Swift_MailTransport::class
            ]
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

    // Protected Methods
    // =========================================================================

    /**
     * Creates the tables.
     *
     * @return void
     */
    protected function createTables()
    {
        $this->createTable('{{%assetindexdata}}', [
            'id' => $this->primaryKey(),
            'sessionId' => $this->string(36)->notNull()->defaultValue(''),
            'volumeId' => $this->integer()->notNull(),
            'offset' => $this->integer()->notNull(),
            'uri' => $this->text(),
            'size' => $this->bigInteger()->unsigned(),
            'timestamp' => $this->dateTime(),
            'recordId' => $this->integer(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable('{{%assets}}', [
            'id' => $this->integer()->notNull(),
            'volumeId' => $this->integer(),
            'folderId' => $this->integer()->notNull(),
            'filename' => $this->string()->notNull(),
            'kind' => $this->string(50)->notNull()->defaultValue('unknown'),
            'width' => $this->integer()->unsigned(),
            'height' => $this->integer()->unsigned(),
            'size' => $this->bigInteger()->unsigned(),
            'focalPoint' => $this->string(20)->null(),
            'dateModified' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
            'PRIMARY KEY(id)',
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
            'PRIMARY KEY(id)',
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
        $this->createTable('{{%categorygroups_i18n}}', [
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
        $this->createTable('{{%deprecationerrors}}', [
            'id' => $this->primaryKey(),
            'key' => $this->string()->notNull(),
            'fingerprint' => $this->string()->notNull(),
            'lastOccurrence' => $this->dateTime()->notNull(),
            'file' => $this->string()->notNull(),
            'line' => $this->smallInteger()->notNull()->unsigned(),
            'class' => $this->string(),
            'method' => $this->string(),
            'template' => $this->string(500),
            'templateLine' => $this->smallInteger()->unsigned(),
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
            'type' => $this->string()->notNull(),
            'enabled' => $this->boolean()->defaultValue(true)->notNull(),
            'archived' => $this->boolean()->defaultValue(false)->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable('{{%elements_i18n}}', [
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
        $this->createTable('{{%emailmessages}}', [
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
            'PRIMARY KEY(id)',
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
            'translationMethod' => $this->enum('translationMethod', ['none', 'language', 'site', 'custom'])->notNull()->defaultValue('none'),
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
            'edition' => $this->smallInteger()->unsigned()->notNull(),
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
            'PRIMARY KEY(id)',
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
            'handle' => $this->string(150)->notNull(),
            'version' => $this->string(15)->notNull(),
            'schemaVersion' => $this->string(15)->notNull(),
            'licenseKey' => $this->char(24),
            'licenseKeyStatus' => $this->enum('licenseKeyStatus', ['valid', 'invalid', 'mismatched', 'unknown'])->notNull()->defaultValue('unknown'),
            'enabled' => $this->boolean()->defaultValue(false)->notNull(),
            'settings' => $this->text(),
            'installDate' => $this->dateTime()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
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
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable('{{%sections_i18n}}', [
            'id' => $this->primaryKey(),
            'sectionId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'enabledByDefault' => $this->boolean()->defaultValue(true)->notNull(),
            'hasUrls' => $this->boolean()->defaultValue(true)->notNull(),
            'uriFormat' => $this->text(),
            'template' => $this->string(500),
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
            'PRIMARY KEY(id)',
        ]);
        $this->createTable('{{%tasks}}', [
            'id' => $this->primaryKey(),
            'root' => $this->integer()->unsigned(),
            'lft' => $this->integer()->notNull()->unsigned(),
            'rgt' => $this->integer()->notNull()->unsigned(),
            'level' => $this->smallInteger()->notNull()->unsigned(),
            'currentStep' => $this->integer()->unsigned(),
            'totalSteps' => $this->integer()->unsigned(),
            'status' => $this->enum('status', ['pending', 'error', 'running']),
            'type' => $this->string()->notNull(),
            'description' => $this->string(),
            'settings' => $this->mediumText()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable('{{%templatecacheelements}}', [
            'cacheId' => $this->integer()->notNull(),
            'elementId' => $this->integer()->notNull(),
        ]);
        $this->createTable('{{%templatecachequeries}}', [
            'id' => $this->primaryKey(),
            'cacheId' => $this->integer()->notNull(),
            'type' => $this->string()->notNull(),
            'query' => $this->text()->notNull(),
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
            'usageLimit' => $this->smallInteger()->unsigned(),
            'usageCount' => $this->smallInteger()->unsigned(),
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
            'client' => $this->boolean()->defaultValue(false)->notNull(),
            'locked' => $this->boolean()->defaultValue(false)->notNull(),
            'suspended' => $this->boolean()->defaultValue(false)->notNull(),
            'pending' => $this->boolean()->defaultValue(false)->notNull(),
            'archived' => $this->boolean()->defaultValue(false)->notNull(),
            'lastLoginDate' => $this->dateTime(),
            'lastLoginAttemptIp' => $this->string(45),
            'invalidLoginWindowStart' => $this->dateTime(),
            'invalidLoginCount' => $this->smallInteger()->unsigned(),
            'lastInvalidLoginDate' => $this->dateTime(),
            'lockoutDate' => $this->dateTime(),
            'verificationCode' => $this->string(),
            'verificationCodeIssuedDate' => $this->dateTime(),
            'unverifiedEmail' => $this->string(),
            'passwordResetRequired' => $this->boolean()->defaultValue(false)->notNull(),
            'lastPasswordChangeDate' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
            'PRIMARY KEY(id)',
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
     *
     * @return void
     */
    protected function createIndexes()
    {
        $this->createIndex($this->db->getIndexName('{{%assetindexdata}}', 'sessionId,volumeId,offset', true), '{{%assetindexdata}}', 'sessionId,volumeId,offset', true);
        $this->createIndex($this->db->getIndexName('{{%assetindexdata}}', 'volumeId', false, true), '{{%assetindexdata}}', 'volumeId', false);
        $this->createIndex($this->db->getIndexName('{{%assets}}', 'filename,folderId', true), '{{%assets}}', 'filename,folderId', true);
        $this->createIndex($this->db->getIndexName('{{%assets}}', 'folderId', false, true), '{{%assets}}', 'folderId', false);
        $this->createIndex($this->db->getIndexName('{{%assets}}', 'volumeId', false, true), '{{%assets}}', 'volumeId', false);
        $this->createIndex($this->db->getIndexName('{{%assettransformindex}}', 'volumeId,assetId,location', false), '{{%assettransformindex}}', 'volumeId,assetId,location', false);
        $this->createIndex($this->db->getIndexName('{{%assettransforms}}', 'name', true), '{{%assettransforms}}', 'name', true);
        $this->createIndex($this->db->getIndexName('{{%assettransforms}}', 'handle', true), '{{%assettransforms}}', 'handle', true);
        $this->createIndex($this->db->getIndexName('{{%categories}}', 'groupId', false, true), '{{%categories}}', 'groupId', false);
        $this->createIndex($this->db->getIndexName('{{%categorygroups}}', 'name', true), '{{%categorygroups}}', 'name', true);
        $this->createIndex($this->db->getIndexName('{{%categorygroups}}', 'handle', true), '{{%categorygroups}}', 'handle', true);
        $this->createIndex($this->db->getIndexName('{{%categorygroups}}', 'structureId', false, true), '{{%categorygroups}}', 'structureId', false);
        $this->createIndex($this->db->getIndexName('{{%categorygroups}}', 'fieldLayoutId', false, true), '{{%categorygroups}}', 'fieldLayoutId', false);
        $this->createIndex($this->db->getIndexName('{{%categorygroups_i18n}}', 'groupId,siteId', true), '{{%categorygroups_i18n}}', 'groupId,siteId', true);
        $this->createIndex($this->db->getIndexName('{{%categorygroups_i18n}}', 'siteId', false, true), '{{%categorygroups_i18n}}', 'siteId', false);
        $this->createIndex($this->db->getIndexName('{{%content}}', 'elementId,siteId', true), '{{%content}}', 'elementId,siteId', true);
        $this->createIndex($this->db->getIndexName('{{%content}}', 'siteId', false, true), '{{%content}}', 'siteId', false);
        $this->createIndex($this->db->getIndexName('{{%content}}', 'title', false, true), '{{%content}}', 'title', false);
        $this->createIndex($this->db->getIndexName('{{%deprecationerrors}}', 'key,fingerprint', true), '{{%deprecationerrors}}', 'key,fingerprint', true);
        $this->createIndex($this->db->getIndexName('{{%elementindexsettings}}', 'type', true), '{{%elementindexsettings}}', 'type', true);
        $this->createIndex($this->db->getIndexName('{{%elements}}', 'type', false), '{{%elements}}', 'type', false);
        $this->createIndex($this->db->getIndexName('{{%elements}}', 'enabled', false), '{{%elements}}', 'enabled', false);
        $this->createIndex($this->db->getIndexName('{{%elements}}', 'archived,dateCreated', false), '{{%elements}}', 'archived,dateCreated', false);
        $this->createIndex($this->db->getIndexName('{{%elements_i18n}}', 'elementId,siteId', true), '{{%elements_i18n}}', 'elementId,siteId', true);
        $this->createIndex($this->db->getIndexName('{{%elements_i18n}}', 'uri,siteId', true), '{{%elements_i18n}}', 'uri,siteId', true);
        $this->createIndex($this->db->getIndexName('{{%elements_i18n}}', 'siteId', false, true), '{{%elements_i18n}}', 'siteId', false);
        $this->createIndex($this->db->getIndexName('{{%elements_i18n}}', 'slug,siteId', false), '{{%elements_i18n}}', 'slug,siteId', false);
        $this->createIndex($this->db->getIndexName('{{%elements_i18n}}', 'enabled', false), '{{%elements_i18n}}', 'enabled', false);
        $this->createIndex($this->db->getIndexName('{{%emailmessages}}', 'key,language', true), '{{%emailmessages}}', 'key,language', true);
        $this->createIndex($this->db->getIndexName('{{%emailmessages}}', 'language', false), '{{%emailmessages}}', 'language', false);
        $this->createIndex($this->db->getIndexName('{{%entries}}', 'postDate', false), '{{%entries}}', 'postDate', false);
        $this->createIndex($this->db->getIndexName('{{%entries}}', 'expiryDate', false), '{{%entries}}', 'expiryDate', false);
        $this->createIndex($this->db->getIndexName('{{%entries}}', 'authorId', false, true), '{{%entries}}', 'authorId', false);
        $this->createIndex($this->db->getIndexName('{{%entries}}', 'sectionId', false, true), '{{%entries}}', 'sectionId', false);
        $this->createIndex($this->db->getIndexName('{{%entries}}', 'typeId', false, true), '{{%entries}}', 'typeId', false);
        $this->createIndex($this->db->getIndexName('{{%entrydrafts}}', 'sectionId', false, true), '{{%entrydrafts}}', 'sectionId', false);
        $this->createIndex($this->db->getIndexName('{{%entrydrafts}}', 'entryId,siteId', false), '{{%entrydrafts}}', 'entryId,siteId', false);
        $this->createIndex($this->db->getIndexName('{{%entrydrafts}}', 'siteId', false, true), '{{%entrydrafts}}', 'siteId', false);
        $this->createIndex($this->db->getIndexName('{{%entrydrafts}}', 'creatorId', false, true), '{{%entrydrafts}}', 'creatorId', false);
        $this->createIndex($this->db->getIndexName('{{%entrytypes}}', 'name,sectionId', true), '{{%entrytypes}}', 'name,sectionId', true);
        $this->createIndex($this->db->getIndexName('{{%entrytypes}}', 'handle,sectionId', true), '{{%entrytypes}}', 'handle,sectionId', true);
        $this->createIndex($this->db->getIndexName('{{%entrytypes}}', 'sectionId', false, true), '{{%entrytypes}}', 'sectionId', false);
        $this->createIndex($this->db->getIndexName('{{%entrytypes}}', 'fieldLayoutId', false, true), '{{%entrytypes}}', 'fieldLayoutId', false);
        $this->createIndex($this->db->getIndexName('{{%entryversions}}', 'sectionId', false, true), '{{%entryversions}}', 'sectionId', false);
        $this->createIndex($this->db->getIndexName('{{%entryversions}}', 'entryId,siteId', false), '{{%entryversions}}', 'entryId,siteId', false);
        $this->createIndex($this->db->getIndexName('{{%entryversions}}', 'siteId', false, true), '{{%entryversions}}', 'siteId', false);
        $this->createIndex($this->db->getIndexName('{{%entryversions}}', 'creatorId', false, true), '{{%entryversions}}', 'creatorId', false);
        $this->createIndex($this->db->getIndexName('{{%fieldgroups}}', 'name', true), '{{%fieldgroups}}', 'name', true);
        $this->createIndex($this->db->getIndexName('{{%fieldlayoutfields}}', 'layoutId,fieldId', true), '{{%fieldlayoutfields}}', 'layoutId,fieldId', true);
        $this->createIndex($this->db->getIndexName('{{%fieldlayoutfields}}', 'sortOrder', false), '{{%fieldlayoutfields}}', 'sortOrder', false);
        $this->createIndex($this->db->getIndexName('{{%fieldlayoutfields}}', 'tabId', false, true), '{{%fieldlayoutfields}}', 'tabId', false);
        $this->createIndex($this->db->getIndexName('{{%fieldlayoutfields}}', 'fieldId', false, true), '{{%fieldlayoutfields}}', 'fieldId', false);
        $this->createIndex($this->db->getIndexName('{{%fieldlayouts}}', 'type', false), '{{%fieldlayouts}}', 'type', false);
        $this->createIndex($this->db->getIndexName('{{%fieldlayouttabs}}', 'sortOrder', false), '{{%fieldlayouttabs}}', 'sortOrder', false);
        $this->createIndex($this->db->getIndexName('{{%fieldlayouttabs}}', 'layoutId', false, true), '{{%fieldlayouttabs}}', 'layoutId', false);
        $this->createIndex($this->db->getIndexName('{{%fields}}', 'handle,context', true), '{{%fields}}', 'handle,context', true);
        $this->createIndex($this->db->getIndexName('{{%fields}}', 'groupId', false, true), '{{%fields}}', 'groupId', false);
        $this->createIndex($this->db->getIndexName('{{%fields}}', 'context', false), '{{%fields}}', 'context', false);
        $this->createIndex($this->db->getIndexName('{{%globalsets}}', 'name', true), '{{%globalsets}}', 'name', true);
        $this->createIndex($this->db->getIndexName('{{%globalsets}}', 'handle', true), '{{%globalsets}}', 'handle', true);
        $this->createIndex($this->db->getIndexName('{{%globalsets}}', 'fieldLayoutId', false, true), '{{%globalsets}}', 'fieldLayoutId', false);
        $this->createIndex($this->db->getIndexName('{{%matrixblocks}}', 'ownerId', false, true), '{{%matrixblocks}}', 'ownerId', false);
        $this->createIndex($this->db->getIndexName('{{%matrixblocks}}', 'fieldId', false, true), '{{%matrixblocks}}', 'fieldId', false);
        $this->createIndex($this->db->getIndexName('{{%matrixblocks}}', 'typeId', false, true), '{{%matrixblocks}}', 'typeId', false);
        $this->createIndex($this->db->getIndexName('{{%matrixblocks}}', 'sortOrder', false), '{{%matrixblocks}}', 'sortOrder', false);
        $this->createIndex($this->db->getIndexName('{{%matrixblocks}}', 'ownerSiteId', false, true), '{{%matrixblocks}}', 'ownerSiteId', false);
        $this->createIndex($this->db->getIndexName('{{%matrixblocktypes}}', 'name,fieldId', true), '{{%matrixblocktypes}}', 'name,fieldId', true);
        $this->createIndex($this->db->getIndexName('{{%matrixblocktypes}}', 'handle,fieldId', true), '{{%matrixblocktypes}}', 'handle,fieldId', true);
        $this->createIndex($this->db->getIndexName('{{%matrixblocktypes}}', 'fieldId', false, true), '{{%matrixblocktypes}}', 'fieldId', false);
        $this->createIndex($this->db->getIndexName('{{%matrixblocktypes}}', 'fieldLayoutId', false, true), '{{%matrixblocktypes}}', 'fieldLayoutId', false);
        $this->createIndex($this->db->getIndexName('{{%migrations}}', 'pluginId', false, true), '{{%migrations}}', 'pluginId', false);
        $this->createIndex($this->db->getIndexName('{{%migrations}}', 'type,pluginId', false), '{{%migrations}}', 'type,pluginId', false);
        $this->createIndex($this->db->getIndexName('{{%plugins}}', 'handle', true), '{{%plugins}}', 'handle', true);
        $this->createIndex($this->db->getIndexName('{{%relations}}', 'fieldId,sourceId,sourceSiteId,targetId', true), '{{%relations}}', 'fieldId,sourceId,sourceSiteId,targetId', true);
        $this->createIndex($this->db->getIndexName('{{%relations}}', 'sourceId', false, true), '{{%relations}}', 'sourceId', false);
        $this->createIndex($this->db->getIndexName('{{%relations}}', 'targetId', false, true), '{{%relations}}', 'targetId', false);
        $this->createIndex($this->db->getIndexName('{{%relations}}', 'sourceSiteId', false, true), '{{%relations}}', 'sourceSiteId', false);
        $this->createIndex($this->db->getIndexName('{{%routes}}', 'uriPattern', true), '{{%routes}}', 'uriPattern', true);
        $this->createIndex($this->db->getIndexName('{{%routes}}', 'siteId', false, true), '{{%routes}}', 'siteId', false);
        $this->createIndex($this->db->getIndexName('{{%sections}}', 'handle', true), '{{%sections}}', 'handle', true);
        $this->createIndex($this->db->getIndexName('{{%sections}}', 'name', true), '{{%sections}}', 'name', true);
        $this->createIndex($this->db->getIndexName('{{%sections}}', 'structureId', false, true), '{{%sections}}', 'structureId', false);
        $this->createIndex($this->db->getIndexName('{{%sections_i18n}}', 'sectionId,siteId', true), '{{%sections_i18n}}', 'sectionId,siteId', true);
        $this->createIndex($this->db->getIndexName('{{%sections_i18n}}', 'siteId', false, true), '{{%sections_i18n}}', 'siteId', false);
        $this->createIndex($this->db->getIndexName('{{%sessions}}', 'uid', false), '{{%sessions}}', 'uid', false);
        $this->createIndex($this->db->getIndexName('{{%sessions}}', 'token', false), '{{%sessions}}', 'token', false);
        $this->createIndex($this->db->getIndexName('{{%sessions}}', 'dateUpdated', false), '{{%sessions}}', 'dateUpdated', false);
        $this->createIndex($this->db->getIndexName('{{%sessions}}', 'userId', false, true), '{{%sessions}}', 'userId', false);
        $this->createIndex($this->db->getIndexName('{{%shunnedmessages}}', 'userId,message', true), '{{%shunnedmessages}}', 'userId,message', true);
        $this->createIndex($this->db->getIndexName('{{%sites}}', 'handle', true), '{{%sites}}', 'handle', true);
        $this->createIndex($this->db->getIndexName('{{%sites}}', 'sortOrder', false), '{{%sites}}', 'sortOrder', false);
        $this->createIndex($this->db->getIndexName('{{%structureelements}}', 'structureId,elementId', true), '{{%structureelements}}', 'structureId,elementId', true);
        $this->createIndex($this->db->getIndexName('{{%structureelements}}', 'root', false), '{{%structureelements}}', 'root', false);
        $this->createIndex($this->db->getIndexName('{{%structureelements}}', 'lft', false), '{{%structureelements}}', 'lft', false);
        $this->createIndex($this->db->getIndexName('{{%structureelements}}', 'rgt', false), '{{%structureelements}}', 'rgt', false);
        $this->createIndex($this->db->getIndexName('{{%structureelements}}', 'level', false), '{{%structureelements}}', 'level', false);
        $this->createIndex($this->db->getIndexName('{{%structureelements}}', 'elementId', false, true), '{{%structureelements}}', 'elementId', false);
        $this->createIndex($this->db->getIndexName('{{%systemsettings}}', 'category', true), '{{%systemsettings}}', 'category', true);
        $this->createIndex($this->db->getIndexName('{{%taggroups}}', 'name', true), '{{%taggroups}}', 'name', true);
        $this->createIndex($this->db->getIndexName('{{%taggroups}}', 'handle', true), '{{%taggroups}}', 'handle', true);
        $this->createIndex($this->db->getIndexName('{{%tags}}', 'groupId', false, true), '{{%tags}}', 'groupId', false);
        $this->createIndex($this->db->getIndexName('{{%tasks}}', 'root', false), '{{%tasks}}', 'root', false);
        $this->createIndex($this->db->getIndexName('{{%tasks}}', 'lft', false), '{{%tasks}}', 'lft', false);
        $this->createIndex($this->db->getIndexName('{{%tasks}}', 'rgt', false), '{{%tasks}}', 'rgt', false);
        $this->createIndex($this->db->getIndexName('{{%tasks}}', 'level', false), '{{%tasks}}', 'level', false);
        $this->createIndex($this->db->getIndexName('{{%templatecacheelements}}', 'cacheId', false, true), '{{%templatecacheelements}}', 'cacheId', false);
        $this->createIndex($this->db->getIndexName('{{%templatecacheelements}}', 'elementId', false, true), '{{%templatecacheelements}}', 'elementId', false);
        $this->createIndex($this->db->getIndexName('{{%templatecachequeries}}', 'cacheId', false, true), '{{%templatecachequeries}}', 'cacheId', false);
        $this->createIndex($this->db->getIndexName('{{%templatecachequeries}}', 'type', false), '{{%templatecachequeries}}', 'type', false);
        $this->createIndex($this->db->getIndexName('{{%templatecaches}}', 'expiryDate,cacheKey,siteId,path', false), '{{%templatecaches}}', 'expiryDate,cacheKey,siteId,path', false);
        $this->createIndex($this->db->getIndexName('{{%templatecaches}}', 'siteId', false, true), '{{%templatecaches}}', 'siteId', false);
        $this->createIndex($this->db->getIndexName('{{%tokens}}', 'token', true), '{{%tokens}}', 'token', true);
        $this->createIndex($this->db->getIndexName('{{%tokens}}', 'expiryDate', false), '{{%tokens}}', 'expiryDate', false);
        $this->createIndex($this->db->getIndexName('{{%usergroups}}', 'handle', true), '{{%usergroups}}', 'handle', true);
        $this->createIndex($this->db->getIndexName('{{%usergroups}}', 'name', true), '{{%usergroups}}', 'name', true);
        $this->createIndex($this->db->getIndexName('{{%usergroups_users}}', 'groupId,userId', true), '{{%usergroups_users}}', 'groupId,userId', true);
        $this->createIndex($this->db->getIndexName('{{%usergroups_users}}', 'userId', false, true), '{{%usergroups_users}}', 'userId', false);
        $this->createIndex($this->db->getIndexName('{{%userpermissions}}', 'name', true), '{{%userpermissions}}', 'name', true);
        $this->createIndex($this->db->getIndexName('{{%userpermissions_usergroups}}', 'permissionId,groupId', true), '{{%userpermissions_usergroups}}', 'permissionId,groupId', true);
        $this->createIndex($this->db->getIndexName('{{%userpermissions_usergroups}}', 'groupId', false, true), '{{%userpermissions_usergroups}}', 'groupId', false);
        $this->createIndex($this->db->getIndexName('{{%userpermissions_users}}', 'permissionId,userId', true), '{{%userpermissions_users}}', 'permissionId,userId', true);
        $this->createIndex($this->db->getIndexName('{{%userpermissions_users}}', 'userId', false, true), '{{%userpermissions_users}}', 'userId', false);
        $this->createIndex($this->db->getIndexName('{{%users}}', 'username', true), '{{%users}}', 'username', true);
        $this->createIndex($this->db->getIndexName('{{%users}}', 'email', true), '{{%users}}', 'email', true);
        $this->createIndex($this->db->getIndexName('{{%users}}', 'uid', false), '{{%users}}', 'uid', false);
        $this->createIndex($this->db->getIndexName('{{%users}}', 'verificationCode', false), '{{%users}}', 'verificationCode', false);
        $this->createIndex($this->db->getIndexName('{{%volumefolders}}', 'name,parentId,volumeId', true), '{{%volumefolders}}', 'name,parentId,volumeId', true);
        $this->createIndex($this->db->getIndexName('{{%volumefolders}}', 'parentId', false, true), '{{%volumefolders}}', 'parentId', false);
        $this->createIndex($this->db->getIndexName('{{%volumefolders}}', 'volumeId', false, true), '{{%volumefolders}}', 'volumeId', false);
        $this->createIndex($this->db->getIndexName('{{%volumes}}', 'name', true), '{{%volumes}}', 'name', true);
        $this->createIndex($this->db->getIndexName('{{%volumes}}', 'handle', true), '{{%volumes}}', 'handle', true);
        $this->createIndex($this->db->getIndexName('{{%volumes}}', 'fieldLayoutId', false, true), '{{%volumes}}', 'fieldLayoutId', false);
        $this->createIndex($this->db->getIndexName('{{%widgets}}', 'userId', false, true), '{{%widgets}}', 'userId', false);

        // If we're using MySQL, add the FULLTEXT index on searchindex.keywords
        switch ($this->driver) {
            case Connection::DRIVER_MYSQL:
                $this->createTable('{{%searchindex}}', [
                    'elementId' => $this->integer()->notNull(),
                    'attribute' => $this->string(25)->notNull(),
                    'fieldId' => $this->integer()->notNull(),
                    'siteId' => $this->integer()->notNull(),
                    'keywords' => $this->text()->notNull(),
                ], ' ENGINE=MyISAM');

                $this->addPrimaryKey($this->db->getIndexName('{{%searchindex}}', 'elementId,attribute,fieldId,siteId', true), '{{%searchindex}}', 'elementId,attribute,fieldId,siteId');

                $sql = 'CREATE FULLTEXT INDEX '.
                    $this->db->quoteTableName($this->db->getIndexName('{{%searchindex}}', 'keywords')).' ON '.
                    $this->db->quoteTableName('{{%searchindex}}').' '.
                    '('.$this->db->quoteColumnName('keywords').')';

                $this->db->createCommand($sql)->execute();
                break;
            case Connection::DRIVER_PGSQL:
                $this->createTable('{{%searchindex}}', [
                    'elementId' => $this->integer()->notNull(),
                    'attribute' => $this->string(25)->notNull(),
                    'fieldId' => $this->integer()->notNull(),
                    'siteId' => $this->integer()->notNull(),
                    'keywords' => $this->text()->notNull(),
                    'keywords_vector' => $this->getDb()->getSchema()->createColumnSchemaBuilder('tsvector')->notNull(),
                ]);

                $this->addPrimaryKey($this->db->getIndexName('{{%searchindex}}', 'elementId,attribute,fieldId,siteId', true), '{{%searchindex}}', 'elementId,attribute,fieldId,siteId');

                $sql = 'CREATE INDEX '.$this->db->quoteTableName($this->db->getIndexName('{{%searchindex}}', 'keywords_vector')).' ON {{%searchindex}} USING GIN([[keywords_vector]] [[pg_catalog]].[[tsvector_ops]]) WITH (FASTUPDATE=YES)';
                $this->db->createCommand($sql)->execute();

                $sql = 'CREATE INDEX '.$this->db->quoteTableName($this->db->getIndexName('{{%searchindex}}', 'keywords')).' ON {{%searchindex}} USING btree(keywords)';
                $this->db->createCommand($sql)->execute();
                break;
        }
    }

    /**
     * Adds the foreign keys.
     *
     * @return void
     */
    protected function addForeignKeys()
    {
        $this->addForeignKey($this->db->getForeignKeyName('{{%assetindexdata}}', 'volumeId'), '{{%assetindexdata}}', 'volumeId', '{{%volumes}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%assets}}', 'folderId'), '{{%assets}}', 'folderId', '{{%volumefolders}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%assets}}', 'id'), '{{%assets}}', 'id', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%assets}}', 'volumeId'), '{{%assets}}', 'volumeId', '{{%volumes}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%categories}}', 'groupId'), '{{%categories}}', 'groupId', '{{%categorygroups}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%categories}}', 'id'), '{{%categories}}', 'id', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%categorygroups}}', 'fieldLayoutId'), '{{%categorygroups}}', 'fieldLayoutId', '{{%fieldlayouts}}', 'id', 'SET NULL', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%categorygroups}}', 'structureId'), '{{%categorygroups}}', 'structureId', '{{%structures}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%categorygroups_i18n}}', 'groupId'), '{{%categorygroups_i18n}}', 'groupId', '{{%categorygroups}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%categorygroups_i18n}}', 'siteId'), '{{%categorygroups_i18n}}', 'siteId', '{{%sites}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey($this->db->getForeignKeyName('{{%content}}', 'elementId'), '{{%content}}', 'elementId', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%content}}', 'siteId'), '{{%content}}', 'siteId', '{{%sites}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey($this->db->getForeignKeyName('{{%elements_i18n}}', 'elementId'), '{{%elements_i18n}}', 'elementId', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%elements_i18n}}', 'siteId'), '{{%elements_i18n}}', 'siteId', '{{%sites}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey($this->db->getForeignKeyName('{{%entries}}', 'authorId'), '{{%entries}}', 'authorId', '{{%users}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%entries}}', 'id'), '{{%entries}}', 'id', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%entries}}', 'sectionId'), '{{%entries}}', 'sectionId', '{{%sections}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%entries}}', 'typeId'), '{{%entries}}', 'typeId', '{{%entrytypes}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%entrydrafts}}', 'creatorId'), '{{%entrydrafts}}', 'creatorId', '{{%users}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%entrydrafts}}', 'entryId'), '{{%entrydrafts}}', 'entryId', '{{%entries}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%entrydrafts}}', 'siteId'), '{{%entrydrafts}}', 'siteId', '{{%sites}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey($this->db->getForeignKeyName('{{%entrydrafts}}', 'sectionId'), '{{%entrydrafts}}', 'sectionId', '{{%sections}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%entrytypes}}', 'fieldLayoutId'), '{{%entrytypes}}', 'fieldLayoutId', '{{%fieldlayouts}}', 'id', 'SET NULL', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%entrytypes}}', 'sectionId'), '{{%entrytypes}}', 'sectionId', '{{%sections}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%entryversions}}', 'creatorId'), '{{%entryversions}}', 'creatorId', '{{%users}}', 'id', 'SET NULL', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%entryversions}}', 'entryId'), '{{%entryversions}}', 'entryId', '{{%entries}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%entryversions}}', 'siteId'), '{{%entryversions}}', 'siteId', '{{%sites}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey($this->db->getForeignKeyName('{{%entryversions}}', 'sectionId'), '{{%entryversions}}', 'sectionId', '{{%sections}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%fieldlayoutfields}}', 'fieldId'), '{{%fieldlayoutfields}}', 'fieldId', '{{%fields}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%fieldlayoutfields}}', 'layoutId'), '{{%fieldlayoutfields}}', 'layoutId', '{{%fieldlayouts}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%fieldlayoutfields}}', 'tabId'), '{{%fieldlayoutfields}}', 'tabId', '{{%fieldlayouttabs}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%fieldlayouttabs}}', 'layoutId'), '{{%fieldlayouttabs}}', 'layoutId', '{{%fieldlayouts}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%fields}}', 'groupId'), '{{%fields}}', 'groupId', '{{%fieldgroups}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%globalsets}}', 'fieldLayoutId'), '{{%globalsets}}', 'fieldLayoutId', '{{%fieldlayouts}}', 'id', 'SET NULL', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%globalsets}}', 'id'), '{{%globalsets}}', 'id', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%matrixblocks}}', 'fieldId'), '{{%matrixblocks}}', 'fieldId', '{{%fields}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%matrixblocks}}', 'id'), '{{%matrixblocks}}', 'id', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%matrixblocks}}', 'ownerId'), '{{%matrixblocks}}', 'ownerId', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%matrixblocks}}', 'ownerSiteId'), '{{%matrixblocks}}', 'ownerSiteId', '{{%sites}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey($this->db->getForeignKeyName('{{%matrixblocks}}', 'typeId'), '{{%matrixblocks}}', 'typeId', '{{%matrixblocktypes}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%matrixblocktypes}}', 'fieldId'), '{{%matrixblocktypes}}', 'fieldId', '{{%fields}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%matrixblocktypes}}', 'fieldLayoutId'), '{{%matrixblocktypes}}', 'fieldLayoutId', '{{%fieldlayouts}}', 'id', 'SET NULL', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%migrations}}', 'pluginId'), '{{%migrations}}', 'pluginId', '{{%plugins}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%relations}}', 'fieldId'), '{{%relations}}', 'fieldId', '{{%fields}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%relations}}', 'sourceId'), '{{%relations}}', 'sourceId', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%relations}}', 'sourceSiteId'), '{{%relations}}', 'sourceSiteId', '{{%sites}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey($this->db->getForeignKeyName('{{%relations}}', 'targetId'), '{{%relations}}', 'targetId', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%routes}}', 'siteId'), '{{%routes}}', 'siteId', '{{%sites}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey($this->db->getForeignKeyName('{{%sections}}', 'structureId'), '{{%sections}}', 'structureId', '{{%structures}}', 'id', 'SET NULL', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%sections_i18n}}', 'siteId'), '{{%sections_i18n}}', 'siteId', '{{%sites}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey($this->db->getForeignKeyName('{{%sections_i18n}}', 'sectionId'), '{{%sections_i18n}}', 'sectionId', '{{%sections}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%sessions}}', 'userId'), '{{%sessions}}', 'userId', '{{%users}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%shunnedmessages}}', 'userId'), '{{%shunnedmessages}}', 'userId', '{{%users}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%structureelements}}', 'elementId'), '{{%structureelements}}', 'elementId', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%structureelements}}', 'structureId'), '{{%structureelements}}', 'structureId', '{{%structures}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%taggroups}}', 'fieldLayoutId'), '{{%taggroups}}', 'fieldLayoutId', '{{%fieldlayouts}}', 'id', 'SET NULL', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%tags}}', 'groupId'), '{{%tags}}', 'groupId', '{{%taggroups}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%tags}}', 'id'), '{{%tags}}', 'id', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%templatecacheelements}}', 'cacheId'), '{{%templatecacheelements}}', 'cacheId', '{{%templatecaches}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%templatecacheelements}}', 'elementId'), '{{%templatecacheelements}}', 'elementId', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%templatecachequeries}}', 'cacheId'), '{{%templatecachequeries}}', 'cacheId', '{{%templatecaches}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%templatecaches}}', 'siteId'), '{{%templatecaches}}', 'siteId', '{{%sites}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey($this->db->getForeignKeyName('{{%usergroups_users}}', 'groupId'), '{{%usergroups_users}}', 'groupId', '{{%usergroups}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%usergroups_users}}', 'userId'), '{{%usergroups_users}}', 'userId', '{{%users}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%userpermissions_usergroups}}', 'groupId'), '{{%userpermissions_usergroups}}', 'groupId', '{{%usergroups}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%userpermissions_usergroups}}', 'permissionId'), '{{%userpermissions_usergroups}}', 'permissionId', '{{%userpermissions}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%userpermissions_users}}', 'permissionId'), '{{%userpermissions_users}}', 'permissionId', '{{%userpermissions}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%userpermissions_users}}', 'userId'), '{{%userpermissions_users}}', 'userId', '{{%users}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%userpreferences}}', 'userId'), '{{%userpreferences}}', 'userId', '{{%users}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%users}}', 'id'), '{{%users}}', 'id', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%users}}', 'photoId'), '{{%users}}', 'photoId', '{{%assets}}', 'id', 'SET NULL', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%volumefolders}}', 'parentId'), '{{%volumefolders}}', 'parentId', '{{%volumefolders}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%volumefolders}}', 'volumeId'), '{{%volumefolders}}', 'volumeId', '{{%volumes}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%volumes}}', 'fieldLayoutId'), '{{%volumes}}', 'fieldLayoutId', '{{%fieldlayouts}}', 'id', 'SET NULL', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%widgets}}', 'userId'), '{{%widgets}}', 'userId', '{{%users}}', 'id', 'CASCADE', null);
    }

    /**
     * Populates the DB with the default data.
     *
     * @return void
     */
    protected function insertDefaultData()
    {
        // Populate the info table
        echo '    > populate the info table ...';
        Craft::$app->saveInfo(new Info([
            'version' => Craft::$app->version,
            'schemaVersion' => Craft::$app->schemaVersion,
            'edition' => '0',
            'name' => $this->site->name,
            'on' => '1',
            'maintenance' => '0',
            'fieldVersion' => StringHelper::randomString(12),
        ]));
        echo " done\n";

        // Add the default site
        Craft::$app->getSites()->saveSite($this->site);
    }
}
