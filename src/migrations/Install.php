<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\migrations;

use Craft;
use craft\app\elements\User;
use craft\app\db\Migration;
use craft\app\helpers\StringHelper;
use craft\app\models\Info;

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
        $this->createTables();
        $this->createIndexes();
        $this->addForeignKeys();
        $this->insertDefaultData();

        // Craft, you are installed now.
        Craft::$app->setIsInstalled();

        // Set the language to the desired locale
        Craft::$app->language = $this->locale;

        // Save the first user
        echo "    > save the first user ...";
        $user = new User([
            'username' => $this->username,
            'newPassword' => $this->password,
            'email' => $this->email,
            'admin' => true
        ]);
        Craft::$app->getUsers()->saveUser($user);
        echo " done\n";

        // Log them in
        if (!Craft::$app->getRequest()->getIsConsoleRequest()) {
            Craft::$app->getUser()->login($user);
        }

        // Save the default email settings
        echo "    > save the email settings ...";
        Craft::$app->getSystemSettings()->saveSettings('email', [
            'fromEmail' => $this->email,
            'fromName' => $this->siteName,
            'transportType' => 'craft\app\mail\transportadaptors\Php'
        ]);
        Craft::$app->getSystemSettings()->saveSettings('mailer', [
            'from' => [$this->email => $this->siteName],
            'transport' => [
                'class' => 'craft\app\mail\transportadaptors\Php'
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
            'uri' => $this->string(),
            'size' => $this->bigInteger()->unsigned(),
            'timestamp' => $this->dateTime(),
            'recordId' => $this->integer(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%assets}}', [
            'id' => $this->primaryKey(),
            'volumeId' => $this->integer(),
            'folderId' => $this->integer()->notNull(),
            'filename' => $this->string()->notNull(),
            'kind' => $this->string(50)->notNull()->defaultValue('unknown'),
            'width' => $this->integer()->unsigned(),
            'height' => $this->integer()->unsigned(),
            'size' => $this->bigInteger()->unsigned(),
            'dateModified' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%assettransformindex}}', [
            'id' => $this->primaryKey(),
            'assetId' => $this->integer()->notNull(),
            'filename' => $this->string(),
            'format' => $this->string(),
            'location' => $this->string()->notNull(),
            'volumeId' => $this->integer(),
            'fileExists' => $this->boolean()->notNull()->defaultValue(0)->unsigned(),
            'inProgress' => $this->boolean()->notNull()->defaultValue(0)->unsigned(),
            'dateIndexed' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
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
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%categories}}', [
            'id' => $this->primaryKey(),
            'groupId' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%categorygroups}}', [
            'id' => $this->primaryKey(),
            'structureId' => $this->integer()->notNull(),
            'fieldLayoutId' => $this->integer(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'hasUrls' => $this->boolean()->notNull()->defaultValue(1),
            'template' => $this->string(500),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%categorygroups_i18n}}', [
            'id' => $this->primaryKey(),
            'groupId' => $this->integer()->notNull(),
            'locale' => $this->char(12)->notNull(),
            'urlFormat' => $this->text(),
            'nestedUrlFormat' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%content}}', [
            'id' => $this->primaryKey(),
            'elementId' => $this->integer()->notNull(),
            'locale' => $this->char(12)->notNull(),
            'title' => $this->string(),
            'field_heading' => $this->string(),
            'field_siteIntro' => $this->text(),
            'field_body' => $this->text(),
            'field_description' => $this->text(),
            'field_metaDescription' => $this->text(),
            'field_linkColor' => $this->char(7),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
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
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%elementindexsettings}}', [
            'id' => $this->primaryKey(),
            'type' => $this->string()->notNull(),
            'settings' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%elements}}', [
            'id' => $this->primaryKey(),
            'type' => $this->string()->notNull(),
            'enabled' => $this->boolean()->notNull()->defaultValue(1)->unsigned(),
            'archived' => $this->boolean()->notNull()->defaultValue(0)->unsigned(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%elements_i18n}}', [
            'id' => $this->primaryKey(),
            'elementId' => $this->integer()->notNull(),
            'locale' => $this->char(12)->notNull(),
            'slug' => $this->string(),
            'uri' => $this->string(),
            'enabled' => $this->boolean()->defaultValue(1),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%emailmessages}}', [
            'id' => $this->primaryKey(),
            'key' => $this->string()->notNull(),
            'locale' => $this->char(12)->notNull(),
            'subject' => $this->text()->notNull(),
            'body' => $this->text()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%entries}}', [
            'id' => $this->primaryKey(),
            'sectionId' => $this->integer()->notNull(),
            'typeId' => $this->integer(),
            'authorId' => $this->integer(),
            'postDate' => $this->dateTime(),
            'expiryDate' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%entrydrafts}}', [
            'id' => $this->primaryKey(),
            'entryId' => $this->integer()->notNull(),
            'sectionId' => $this->integer()->notNull(),
            'creatorId' => $this->integer()->notNull(),
            'locale' => $this->char(12)->notNull(),
            'name' => $this->string()->notNull(),
            'notes' => $this->text(),
            'data' => $this->mediumText()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%entrytypes}}', [
            'id' => $this->primaryKey(),
            'sectionId' => $this->integer()->notNull(),
            'fieldLayoutId' => $this->integer(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'hasTitleField' => $this->boolean()->notNull()->defaultValue(1),
            'titleLabel' => $this->string(),
            'titleFormat' => $this->string(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%entryversions}}', [
            'id' => $this->primaryKey(),
            'entryId' => $this->integer()->notNull(),
            'sectionId' => $this->integer()->notNull(),
            'creatorId' => $this->integer(),
            'locale' => $this->char(12)->notNull(),
            'num' => $this->smallInteger()->notNull()->unsigned(),
            'notes' => $this->text(),
            'data' => $this->mediumText()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%fieldgroups}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%fieldlayoutfields}}', [
            'id' => $this->primaryKey(),
            'layoutId' => $this->integer()->notNull(),
            'tabId' => $this->integer()->notNull(),
            'fieldId' => $this->integer()->notNull(),
            'required' => $this->boolean()->notNull()->defaultValue(0)->unsigned(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%fieldlayouts}}', [
            'id' => $this->primaryKey(),
            'type' => $this->string()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%fieldlayouttabs}}', [
            'id' => $this->primaryKey(),
            'layoutId' => $this->integer()->notNull(),
            'name' => $this->string()->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%fields}}', [
            'id' => $this->primaryKey(),
            'groupId' => $this->integer(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string(64)->notNull(),
            'context' => $this->string()->notNull()->defaultValue('global'),
            'instructions' => $this->text(),
            'translatable' => $this->boolean()->notNull()->defaultValue(0)->unsigned(),
            'type' => $this->string()->notNull(),
            'settings' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%globalsets}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'fieldLayoutId' => $this->integer(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%info}}', [
            'id' => $this->primaryKey(),
            'version' => $this->string(15)->notNull(),
            'build' => $this->integer()->notNull()->unsigned(),
            'schemaVersion' => $this->string(15)->notNull(),
            'releaseDate' => $this->dateTime()->notNull(),
            'edition' => $this->boolean()->notNull()->defaultValue(0)->unsigned(),
            'siteName' => $this->string(100)->notNull(),
            'siteUrl' => $this->string()->notNull(),
            'timezone' => $this->string(30),
            'on' => $this->boolean()->notNull()->defaultValue(0)->unsigned(),
            'maintenance' => $this->boolean()->notNull()->defaultValue(0)->unsigned(),
            'track' => $this->string(40)->notNull(),
            'fieldVersion' => $this->char(12)->notNull()->defaultValue('1'),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%locales}}', [
            'locale' => $this->char(12)->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
            'PRIMARY KEY(locale)'
        ], null, false, false);
        $this->createTable('{{%matrixblocks}}', [
            'id' => $this->primaryKey(),
            'ownerId' => $this->integer()->notNull(),
            'ownerLocale' => $this->char(12),
            'fieldId' => $this->integer()->notNull(),
            'typeId' => $this->integer(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%matrixblocktypes}}', [
            'id' => $this->primaryKey(),
            'fieldId' => $this->integer()->notNull(),
            'fieldLayoutId' => $this->integer(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%migrations}}', [
            'id' => $this->primaryKey(),
            'pluginId' => $this->integer(),
            'type' => $this->enum('type', ['app', 'plugin', 'content'])->notNull()->defaultValue('app'),
            'name' => $this->string()->notNull(),
            'applyTime' => $this->dateTime()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%plugins}}', [
            'id' => $this->primaryKey(),
            'handle' => $this->string(150)->notNull(),
            'version' => $this->string(15)->notNull(),
            'schemaVersion' => $this->string(15),
            'licenseKey' => $this->char(24),
            'licenseKeyStatus' => $this->enum('licenseKeyStatus', ['valid', 'invalid', 'mismatched', 'unknown'])->notNull()->defaultValue('unknown'),
            'enabled' => $this->boolean()->notNull()->defaultValue(0)->unsigned(),
            'settings' => $this->text(),
            'installDate' => $this->dateTime()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%rackspaceaccess}}', [
            'id' => $this->primaryKey(),
            'connectionKey' => $this->string()->notNull(),
            'token' => $this->string()->notNull(),
            'storageUrl' => $this->string()->notNull(),
            'cdnUrl' => $this->string()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%relations}}', [
            'id' => $this->primaryKey(),
            'fieldId' => $this->integer()->notNull(),
            'sourceId' => $this->integer()->notNull(),
            'sourceLocale' => $this->char(12),
            'targetId' => $this->integer()->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%routes}}', [
            'id' => $this->primaryKey(),
            'locale' => $this->char(12),
            'urlParts' => $this->string()->notNull(),
            'urlPattern' => $this->string()->notNull(),
            'template' => $this->string(500)->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%searchindex}}', [
            'elementId' => $this->integer()->notNull(),
            'attribute' => $this->string(25)->notNull(),
            'fieldId' => $this->integer()->notNull(),
            'locale' => $this->char(12)->notNull(),
            'keywords' => $this->text()->notNull(),
            'PRIMARY KEY(elementId, attribute, fieldId, locale)'
        ], 'ENGINE=MyISAM', false, false);
        $this->createTable('{{%sections}}', [
            'id' => $this->primaryKey(),
            'structureId' => $this->integer(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'type' => $this->enum('type', ['single', 'channel', 'structure'])->notNull()->defaultValue('channel'),
            'hasUrls' => $this->boolean()->notNull()->defaultValue(1)->unsigned(),
            'template' => $this->string(500),
            'enableVersioning' => $this->boolean()->notNull()->defaultValue(0)->unsigned(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%sections_i18n}}', [
            'id' => $this->primaryKey(),
            'sectionId' => $this->integer()->notNull(),
            'locale' => $this->char(12)->notNull(),
            'enabledByDefault' => $this->boolean()->defaultValue(1),
            'urlFormat' => $this->text(),
            'nestedUrlFormat' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%sessions}}', [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'token' => $this->char(100)->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%shunnedmessages}}', [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'message' => $this->string()->notNull(),
            'expiryDate' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
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
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%structures}}', [
            'id' => $this->primaryKey(),
            'maxLevels' => $this->smallInteger()->unsigned(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%systemsettings}}', [
            'id' => $this->primaryKey(),
            'category' => $this->string(15)->notNull(),
            'settings' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%taggroups}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'fieldLayoutId' => $this->integer(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%tags}}', [
            'id' => $this->primaryKey(),
            'groupId' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
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
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%templatecacheelements}}', [
            'cacheId' => $this->integer()->notNull(),
            'elementId' => $this->integer()->notNull(),
        ], null, false, false);
        $this->createTable('{{%templatecachequeries}}', [
            'id' => $this->primaryKey(),
            'cacheId' => $this->integer()->notNull(),
            'type' => $this->string()->notNull(),
            'query' => $this->text()->notNull(),
        ], null, false, false);
        $this->createTable('{{%templatecaches}}', [
            'id' => $this->primaryKey(),
            'cacheKey' => $this->string()->notNull(),
            'locale' => $this->char(12)->notNull(),
            'path' => $this->string(),
            'expiryDate' => $this->dateTime()->notNull(),
            'body' => $this->mediumText()->notNull(),
        ], null, false, false);
        $this->createTable('{{%tokens}}', [
            'id' => $this->primaryKey(),
            'token' => $this->char(32)->notNull(),
            'route' => $this->text(),
            'usageLimit' => $this->smallInteger()->unsigned(),
            'usageCount' => $this->smallInteger()->unsigned(),
            'expiryDate' => $this->dateTime()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%usergroups}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%usergroups_users}}', [
            'id' => $this->primaryKey(),
            'groupId' => $this->integer()->notNull(),
            'userId' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%userpermissions}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%userpermissions_usergroups}}', [
            'id' => $this->primaryKey(),
            'permissionId' => $this->integer()->notNull(),
            'groupId' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%userpermissions_users}}', [
            'id' => $this->primaryKey(),
            'permissionId' => $this->integer()->notNull(),
            'userId' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%userpreferences}}', [
            'userId' => $this->integer()->notNull(),
            'preferences' => $this->text(),
            'PRIMARY KEY(userId)'
        ], null, false, false);
        $this->createTable('{{%users}}', [
            'id' => $this->primaryKey(),
            'username' => $this->string(100)->notNull(),
            'photo' => $this->string(100),
            'firstName' => $this->string(100),
            'lastName' => $this->string(100),
            'email' => $this->string()->notNull(),
            'password' => $this->char(255),
            'admin' => $this->boolean()->notNull()->defaultValue(0)->unsigned(),
            'client' => $this->boolean()->notNull(),
            'locked' => $this->boolean()->notNull(),
            'suspended' => $this->boolean()->notNull(),
            'pending' => $this->boolean()->notNull(),
            'archived' => $this->boolean()->notNull(),
            'lastLoginDate' => $this->dateTime(),
            'lastLoginAttemptIp' => $this->string(45),
            'invalidLoginWindowStart' => $this->dateTime(),
            'invalidLoginCount' => $this->smallInteger()->unsigned(),
            'lastInvalidLoginDate' => $this->dateTime(),
            'lockoutDate' => $this->dateTime(),
            'verificationCode' => $this->char(100),
            'verificationCodeIssuedDate' => $this->dateTime(),
            'unverifiedEmail' => $this->string(),
            'passwordResetRequired' => $this->boolean()->notNull()->defaultValue(0)->unsigned(),
            'lastPasswordChangeDate' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%volumefolders}}', [
            'id' => $this->primaryKey(),
            'parentId' => $this->integer(),
            'volumeId' => $this->integer(),
            'name' => $this->string()->notNull(),
            'path' => $this->string(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%volumes}}', [
            'id' => $this->primaryKey(),
            'fieldLayoutId' => $this->integer(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'type' => $this->string()->notNull(),
            'hasUrls' => $this->boolean()->unsigned(),
            'url' => $this->string(),
            'settings' => $this->text(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
        $this->createTable('{{%widgets}}', [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'type' => $this->string()->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'colspan' => $this->boolean()->unsigned(),
            'settings' => $this->text(),
            'enabled' => $this->boolean()->defaultValue(1),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->char(36)->notNull()->defaultValue('0'),
        ], null, false, false);
    }

    /**
     * Creates the indexes.
     *
     * @return void
     */
    protected function createIndexes()
    {
        $this->createIndex($this->db->getIndexName('{{%assetindexdata}}', 'sessionId,volumeId,offset', true), '{{%assetindexdata}}', 'sessionId,volumeId,offset', true);
        $this->createIndex($this->db->getIndexName('{{%assetindexdata}}', 'volumeId', false), '{{%assetindexdata}}', 'volumeId', false);
        $this->createIndex($this->db->getIndexName('{{%assets}}', 'filename,folderId', true), '{{%assets}}', 'filename,folderId', true);
        $this->createIndex($this->db->getIndexName('{{%assets}}', 'folderId', false), '{{%assets}}', 'folderId', false);
        $this->createIndex($this->db->getIndexName('{{%assets}}', 'volumeId', false), '{{%assets}}', 'volumeId', false);
        $this->createIndex($this->db->getIndexName('{{%assettransformindex}}', 'volumeId,assetId,location', false), '{{%assettransformindex}}', 'volumeId,assetId,location', false);
        $this->createIndex($this->db->getIndexName('{{%assettransforms}}', 'name', true), '{{%assettransforms}}', 'name', true);
        $this->createIndex($this->db->getIndexName('{{%assettransforms}}', 'handle', true), '{{%assettransforms}}', 'handle', true);
        $this->createIndex($this->db->getIndexName('{{%categories}}', 'groupId', false), '{{%categories}}', 'groupId', false);
        $this->createIndex($this->db->getIndexName('{{%categorygroups}}', 'name', true), '{{%categorygroups}}', 'name', true);
        $this->createIndex($this->db->getIndexName('{{%categorygroups}}', 'handle', true), '{{%categorygroups}}', 'handle', true);
        $this->createIndex($this->db->getIndexName('{{%categorygroups}}', 'structureId', false), '{{%categorygroups}}', 'structureId', false);
        $this->createIndex($this->db->getIndexName('{{%categorygroups}}', 'fieldLayoutId', false), '{{%categorygroups}}', 'fieldLayoutId', false);
        $this->createIndex($this->db->getIndexName('{{%categorygroups_i18n}}', 'groupId,locale', true), '{{%categorygroups_i18n}}', 'groupId,locale', true);
        $this->createIndex($this->db->getIndexName('{{%categorygroups_i18n}}', 'locale', false), '{{%categorygroups_i18n}}', 'locale', false);
        $this->createIndex($this->db->getIndexName('{{%content}}', 'elementId,locale', true), '{{%content}}', 'elementId,locale', true);
        $this->createIndex($this->db->getIndexName('{{%content}}', 'locale', false), '{{%content}}', 'locale', false);
        $this->createIndex($this->db->getIndexName('{{%content}}', 'title', false), '{{%content}}', 'title', false);
        $this->createIndex($this->db->getIndexName('{{%deprecationerrors}}', 'key,fingerprint', true), '{{%deprecationerrors}}', 'key,fingerprint', true);
        $this->createIndex($this->db->getIndexName('{{%elementindexsettings}}', 'type', true), '{{%elementindexsettings}}', 'type', true);
        $this->createIndex($this->db->getIndexName('{{%elements}}', 'type', false), '{{%elements}}', 'type', false);
        $this->createIndex($this->db->getIndexName('{{%elements}}', 'enabled', false), '{{%elements}}', 'enabled', false);
        $this->createIndex($this->db->getIndexName('{{%elements}}', 'archived,dateCreated', false), '{{%elements}}', 'archived,dateCreated', false);
        $this->createIndex($this->db->getIndexName('{{%elements_i18n}}', 'elementId,locale', true), '{{%elements_i18n}}', 'elementId,locale', true);
        $this->createIndex($this->db->getIndexName('{{%elements_i18n}}', 'uri,locale', true), '{{%elements_i18n}}', 'uri,locale', true);
        $this->createIndex($this->db->getIndexName('{{%elements_i18n}}', 'locale', false), '{{%elements_i18n}}', 'locale', false);
        $this->createIndex($this->db->getIndexName('{{%elements_i18n}}', 'slug,locale', false), '{{%elements_i18n}}', 'slug,locale', false);
        $this->createIndex($this->db->getIndexName('{{%elements_i18n}}', 'enabled', false), '{{%elements_i18n}}', 'enabled', false);
        $this->createIndex($this->db->getIndexName('{{%emailmessages}}', 'key,locale', true), '{{%emailmessages}}', 'key,locale', true);
        $this->createIndex($this->db->getIndexName('{{%emailmessages}}', 'locale', false), '{{%emailmessages}}', 'locale', false);
        $this->createIndex($this->db->getIndexName('{{%entries}}', 'postDate', false), '{{%entries}}', 'postDate', false);
        $this->createIndex($this->db->getIndexName('{{%entries}}', 'expiryDate', false), '{{%entries}}', 'expiryDate', false);
        $this->createIndex($this->db->getIndexName('{{%entries}}', 'authorId', false), '{{%entries}}', 'authorId', false);
        $this->createIndex($this->db->getIndexName('{{%entries}}', 'sectionId', false), '{{%entries}}', 'sectionId', false);
        $this->createIndex($this->db->getIndexName('{{%entries}}', 'typeId', false), '{{%entries}}', 'typeId', false);
        $this->createIndex($this->db->getIndexName('{{%entrydrafts}}', 'sectionId', false), '{{%entrydrafts}}', 'sectionId', false);
        $this->createIndex($this->db->getIndexName('{{%entrydrafts}}', 'entryId,locale', false), '{{%entrydrafts}}', 'entryId,locale', false);
        $this->createIndex($this->db->getIndexName('{{%entrydrafts}}', 'locale', false), '{{%entrydrafts}}', 'locale', false);
        $this->createIndex($this->db->getIndexName('{{%entrydrafts}}', 'creatorId', false), '{{%entrydrafts}}', 'creatorId', false);
        $this->createIndex($this->db->getIndexName('{{%entrytypes}}', 'name,sectionId', true), '{{%entrytypes}}', 'name,sectionId', true);
        $this->createIndex($this->db->getIndexName('{{%entrytypes}}', 'handle,sectionId', true), '{{%entrytypes}}', 'handle,sectionId', true);
        $this->createIndex($this->db->getIndexName('{{%entrytypes}}', 'sectionId', false), '{{%entrytypes}}', 'sectionId', false);
        $this->createIndex($this->db->getIndexName('{{%entrytypes}}', 'fieldLayoutId', false), '{{%entrytypes}}', 'fieldLayoutId', false);
        $this->createIndex($this->db->getIndexName('{{%entryversions}}', 'sectionId', false), '{{%entryversions}}', 'sectionId', false);
        $this->createIndex($this->db->getIndexName('{{%entryversions}}', 'entryId,locale', false), '{{%entryversions}}', 'entryId,locale', false);
        $this->createIndex($this->db->getIndexName('{{%entryversions}}', 'locale', false), '{{%entryversions}}', 'locale', false);
        $this->createIndex($this->db->getIndexName('{{%entryversions}}', 'creatorId', false), '{{%entryversions}}', 'creatorId', false);
        $this->createIndex($this->db->getIndexName('{{%fieldgroups}}', 'name', true), '{{%fieldgroups}}', 'name', true);
        $this->createIndex($this->db->getIndexName('{{%fieldlayoutfields}}', 'layoutId,fieldId', true), '{{%fieldlayoutfields}}', 'layoutId,fieldId', true);
        $this->createIndex($this->db->getIndexName('{{%fieldlayoutfields}}', 'sortOrder', false), '{{%fieldlayoutfields}}', 'sortOrder', false);
        $this->createIndex($this->db->getIndexName('{{%fieldlayoutfields}}', 'tabId', false), '{{%fieldlayoutfields}}', 'tabId', false);
        $this->createIndex($this->db->getIndexName('{{%fieldlayoutfields}}', 'fieldId', false), '{{%fieldlayoutfields}}', 'fieldId', false);
        $this->createIndex($this->db->getIndexName('{{%fieldlayouts}}', 'type', false), '{{%fieldlayouts}}', 'type', false);
        $this->createIndex($this->db->getIndexName('{{%fieldlayouttabs}}', 'sortOrder', false), '{{%fieldlayouttabs}}', 'sortOrder', false);
        $this->createIndex($this->db->getIndexName('{{%fieldlayouttabs}}', 'layoutId', false), '{{%fieldlayouttabs}}', 'layoutId', false);
        $this->createIndex($this->db->getIndexName('{{%fields}}', 'handle,context', true), '{{%fields}}', 'handle,context', true);
        $this->createIndex($this->db->getIndexName('{{%fields}}', 'groupId', false), '{{%fields}}', 'groupId', false);
        $this->createIndex($this->db->getIndexName('{{%fields}}', 'context', false), '{{%fields}}', 'context', false);
        $this->createIndex($this->db->getIndexName('{{%globalsets}}', 'name', true), '{{%globalsets}}', 'name', true);
        $this->createIndex($this->db->getIndexName('{{%globalsets}}', 'handle', true), '{{%globalsets}}', 'handle', true);
        $this->createIndex($this->db->getIndexName('{{%globalsets}}', 'fieldLayoutId', false), '{{%globalsets}}', 'fieldLayoutId', false);
        $this->createIndex($this->db->getIndexName('{{%locales}}', 'sortOrder', false), '{{%locales}}', 'sortOrder', false);
        $this->createIndex($this->db->getIndexName('{{%matrixblocks}}', 'ownerId', false), '{{%matrixblocks}}', 'ownerId', false);
        $this->createIndex($this->db->getIndexName('{{%matrixblocks}}', 'fieldId', false), '{{%matrixblocks}}', 'fieldId', false);
        $this->createIndex($this->db->getIndexName('{{%matrixblocks}}', 'typeId', false), '{{%matrixblocks}}', 'typeId', false);
        $this->createIndex($this->db->getIndexName('{{%matrixblocks}}', 'sortOrder', false), '{{%matrixblocks}}', 'sortOrder', false);
        $this->createIndex($this->db->getIndexName('{{%matrixblocks}}', 'ownerLocale', false), '{{%matrixblocks}}', 'ownerLocale', false);
        $this->createIndex($this->db->getIndexName('{{%matrixblocktypes}}', 'name,fieldId', true), '{{%matrixblocktypes}}', 'name,fieldId', true);
        $this->createIndex($this->db->getIndexName('{{%matrixblocktypes}}', 'handle,fieldId', true), '{{%matrixblocktypes}}', 'handle,fieldId', true);
        $this->createIndex($this->db->getIndexName('{{%matrixblocktypes}}', 'fieldId', false), '{{%matrixblocktypes}}', 'fieldId', false);
        $this->createIndex($this->db->getIndexName('{{%matrixblocktypes}}', 'fieldLayoutId', false), '{{%matrixblocktypes}}', 'fieldLayoutId', false);
        $this->createIndex($this->db->getIndexName('{{%migrations}}', 'pluginId', false), '{{%migrations}}', 'pluginId', false);
        $this->createIndex($this->db->getIndexName('{{%migrations}}', 'type,pluginId', false), '{{%migrations}}', 'type,pluginId', false);
        $this->createIndex($this->db->getIndexName('{{%plugins}}', 'handle', true), '{{%plugins}}', 'handle', true);
        $this->createIndex($this->db->getIndexName('{{%rackspaceaccess}}', 'connectionKey', true), '{{%rackspaceaccess}}', 'connectionKey', true);
        $this->createIndex($this->db->getIndexName('{{%relations}}', 'fieldId,sourceId,sourceLocale,targetId', true), '{{%relations}}', 'fieldId,sourceId,sourceLocale,targetId', true);
        $this->createIndex($this->db->getIndexName('{{%relations}}', 'sourceId', false), '{{%relations}}', 'sourceId', false);
        $this->createIndex($this->db->getIndexName('{{%relations}}', 'targetId', false), '{{%relations}}', 'targetId', false);
        $this->createIndex($this->db->getIndexName('{{%relations}}', 'sourceLocale', false), '{{%relations}}', 'sourceLocale', false);
        $this->createIndex($this->db->getIndexName('{{%routes}}', 'urlPattern', true), '{{%routes}}', 'urlPattern', true);
        $this->createIndex($this->db->getIndexName('{{%routes}}', 'locale', false), '{{%routes}}', 'locale', false);
        $this->createIndex($this->db->getIndexName('{{%sections}}', 'handle', true), '{{%sections}}', 'handle', true);
        $this->createIndex($this->db->getIndexName('{{%sections}}', 'name', true), '{{%sections}}', 'name', true);
        $this->createIndex($this->db->getIndexName('{{%sections}}', 'structureId', false), '{{%sections}}', 'structureId', false);
        $this->createIndex($this->db->getIndexName('{{%sections_i18n}}', 'sectionId,locale', true), '{{%sections_i18n}}', 'sectionId,locale', true);
        $this->createIndex($this->db->getIndexName('{{%sections_i18n}}', 'locale', false), '{{%sections_i18n}}', 'locale', false);
        $this->createIndex($this->db->getIndexName('{{%sessions}}', 'uid', false), '{{%sessions}}', 'uid', false);
        $this->createIndex($this->db->getIndexName('{{%sessions}}', 'token', false), '{{%sessions}}', 'token', false);
        $this->createIndex($this->db->getIndexName('{{%sessions}}', 'dateUpdated', false), '{{%sessions}}', 'dateUpdated', false);
        $this->createIndex($this->db->getIndexName('{{%sessions}}', 'userId', false), '{{%sessions}}', 'userId', false);
        $this->createIndex($this->db->getIndexName('{{%shunnedmessages}}', 'userId,message', true), '{{%shunnedmessages}}', 'userId,message', true);
        $this->createIndex($this->db->getIndexName('{{%structureelements}}', 'structureId,elementId', true), '{{%structureelements}}', 'structureId,elementId', true);
        $this->createIndex($this->db->getIndexName('{{%structureelements}}', 'root', false), '{{%structureelements}}', 'root', false);
        $this->createIndex($this->db->getIndexName('{{%structureelements}}', 'lft', false), '{{%structureelements}}', 'lft', false);
        $this->createIndex($this->db->getIndexName('{{%structureelements}}', 'rgt', false), '{{%structureelements}}', 'rgt', false);
        $this->createIndex($this->db->getIndexName('{{%structureelements}}', 'level', false), '{{%structureelements}}', 'level', false);
        $this->createIndex($this->db->getIndexName('{{%structureelements}}', 'elementId', false), '{{%structureelements}}', 'elementId', false);
        $this->createIndex($this->db->getIndexName('{{%systemsettings}}', 'category', true), '{{%systemsettings}}', 'category', true);
        $this->createIndex($this->db->getIndexName('{{%taggroups}}', 'name', true), '{{%taggroups}}', 'name', true);
        $this->createIndex($this->db->getIndexName('{{%taggroups}}', 'handle', true), '{{%taggroups}}', 'handle', true);
        $this->createIndex($this->db->getIndexName('{{%tags}}', 'groupId', false), '{{%tags}}', 'groupId', false);
        $this->createIndex($this->db->getIndexName('{{%tasks}}', 'root', false), '{{%tasks}}', 'root', false);
        $this->createIndex($this->db->getIndexName('{{%tasks}}', 'lft', false), '{{%tasks}}', 'lft', false);
        $this->createIndex($this->db->getIndexName('{{%tasks}}', 'rgt', false), '{{%tasks}}', 'rgt', false);
        $this->createIndex($this->db->getIndexName('{{%tasks}}', 'level', false), '{{%tasks}}', 'level', false);
        $this->createIndex($this->db->getIndexName('{{%templatecacheelements}}', 'cacheId', false), '{{%templatecacheelements}}', 'cacheId', false);
        $this->createIndex($this->db->getIndexName('{{%templatecacheelements}}', 'elementId', false), '{{%templatecacheelements}}', 'elementId', false);
        $this->createIndex($this->db->getIndexName('{{%templatecachequeries}}', 'cacheId', false), '{{%templatecachequeries}}', 'cacheId', false);
        $this->createIndex($this->db->getIndexName('{{%templatecachequeries}}', 'type', false), '{{%templatecachequeries}}', 'type', false);
        $this->createIndex($this->db->getIndexName('{{%templatecaches}}', 'expiryDate,cacheKey,locale,path', false), '{{%templatecaches}}', 'expiryDate,cacheKey,locale,path', false);
        $this->createIndex($this->db->getIndexName('{{%templatecaches}}', 'locale', false), '{{%templatecaches}}', 'locale', false);
        $this->createIndex($this->db->getIndexName('{{%tokens}}', 'token', true), '{{%tokens}}', 'token', true);
        $this->createIndex($this->db->getIndexName('{{%tokens}}', 'expiryDate', false), '{{%tokens}}', 'expiryDate', false);
        $this->createIndex($this->db->getIndexName('{{%usergroups_users}}', 'groupId,userId', true), '{{%usergroups_users}}', 'groupId,userId', true);
        $this->createIndex($this->db->getIndexName('{{%usergroups_users}}', 'userId', false), '{{%usergroups_users}}', 'userId', false);
        $this->createIndex($this->db->getIndexName('{{%userpermissions}}', 'name', true), '{{%userpermissions}}', 'name', true);
        $this->createIndex($this->db->getIndexName('{{%userpermissions_usergroups}}', 'permissionId,groupId', true), '{{%userpermissions_usergroups}}', 'permissionId,groupId', true);
        $this->createIndex($this->db->getIndexName('{{%userpermissions_usergroups}}', 'groupId', false), '{{%userpermissions_usergroups}}', 'groupId', false);
        $this->createIndex($this->db->getIndexName('{{%userpermissions_users}}', 'permissionId,userId', true), '{{%userpermissions_users}}', 'permissionId,userId', true);
        $this->createIndex($this->db->getIndexName('{{%userpermissions_users}}', 'userId', false), '{{%userpermissions_users}}', 'userId', false);
        $this->createIndex($this->db->getIndexName('{{%users}}', 'username', true), '{{%users}}', 'username', true);
        $this->createIndex($this->db->getIndexName('{{%users}}', 'email', true), '{{%users}}', 'email', true);
        $this->createIndex($this->db->getIndexName('{{%users}}', 'uid', false), '{{%users}}', 'uid', false);
        $this->createIndex($this->db->getIndexName('{{%users}}', 'verificationCode', false), '{{%users}}', 'verificationCode', false);
        $this->createIndex($this->db->getIndexName('{{%volumefolders}}', 'name,parentId,volumeId', true), '{{%volumefolders}}', 'name,parentId,volumeId', true);
        $this->createIndex($this->db->getIndexName('{{%volumefolders}}', 'parentId', false), '{{%volumefolders}}', 'parentId', false);
        $this->createIndex($this->db->getIndexName('{{%volumefolders}}', 'volumeId', false), '{{%volumefolders}}', 'volumeId', false);
        $this->createIndex($this->db->getIndexName('{{%volumes}}', 'name', true), '{{%volumes}}', 'name', true);
        $this->createIndex($this->db->getIndexName('{{%volumes}}', 'handle', true), '{{%volumes}}', 'handle', true);
        $this->createIndex($this->db->getIndexName('{{%volumes}}', 'fieldLayoutId', false), '{{%volumes}}', 'fieldLayoutId', false);
        $this->createIndex($this->db->getIndexName('{{%widgets}}', 'userId', false), '{{%widgets}}', 'userId', false);

        // Add the FULLTEXT index on searchindex.keywords
        // TODO: MySQL specific
        $sql = 'CREATE FULLTEXT INDEX '.
            $this->db->quoteTableName($this->db->getIndexName('{{%searchindex}}', 'keywords')).' ON '.
            $this->db->quoteTableName('{{%searchindex}}').' '.
            '('.$this->db->quoteColumnName('keywords').')';
        $this->db->createCommand($sql)->execute();
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
        $this->addForeignKey($this->db->getForeignKeyName('{{%categorygroups_i18n}}', 'locale'), '{{%categorygroups_i18n}}', 'locale', '{{%locales}}', 'locale', 'CASCADE', 'CASCADE');
        $this->addForeignKey($this->db->getForeignKeyName('{{%content}}', 'elementId'), '{{%content}}', 'elementId', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%content}}', 'locale'), '{{%content}}', 'locale', '{{%locales}}', 'locale', 'CASCADE', 'CASCADE');
        $this->addForeignKey($this->db->getForeignKeyName('{{%elements_i18n}}', 'elementId'), '{{%elements_i18n}}', 'elementId', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%elements_i18n}}', 'locale'), '{{%elements_i18n}}', 'locale', '{{%locales}}', 'locale', 'CASCADE', 'CASCADE');
        $this->addForeignKey($this->db->getForeignKeyName('{{%emailmessages}}', 'locale'), '{{%emailmessages}}', 'locale', '{{%locales}}', 'locale', 'CASCADE', 'CASCADE');
        $this->addForeignKey($this->db->getForeignKeyName('{{%entries}}', 'authorId'), '{{%entries}}', 'authorId', '{{%users}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%entries}}', 'id'), '{{%entries}}', 'id', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%entries}}', 'sectionId'), '{{%entries}}', 'sectionId', '{{%sections}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%entries}}', 'typeId'), '{{%entries}}', 'typeId', '{{%entrytypes}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%entrydrafts}}', 'creatorId'), '{{%entrydrafts}}', 'creatorId', '{{%users}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%entrydrafts}}', 'entryId'), '{{%entrydrafts}}', 'entryId', '{{%entries}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%entrydrafts}}', 'locale'), '{{%entrydrafts}}', 'locale', '{{%locales}}', 'locale', 'CASCADE', 'CASCADE');
        $this->addForeignKey($this->db->getForeignKeyName('{{%entrydrafts}}', 'sectionId'), '{{%entrydrafts}}', 'sectionId', '{{%sections}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%entrytypes}}', 'fieldLayoutId'), '{{%entrytypes}}', 'fieldLayoutId', '{{%fieldlayouts}}', 'id', 'SET NULL', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%entrytypes}}', 'sectionId'), '{{%entrytypes}}', 'sectionId', '{{%sections}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%entryversions}}', 'creatorId'), '{{%entryversions}}', 'creatorId', '{{%users}}', 'id', 'SET NULL', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%entryversions}}', 'entryId'), '{{%entryversions}}', 'entryId', '{{%entries}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%entryversions}}', 'locale'), '{{%entryversions}}', 'locale', '{{%locales}}', 'locale', 'CASCADE', 'CASCADE');
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
        $this->addForeignKey($this->db->getForeignKeyName('{{%matrixblocks}}', 'ownerLocale'), '{{%matrixblocks}}', 'ownerLocale', '{{%locales}}', 'locale', 'CASCADE', 'CASCADE');
        $this->addForeignKey($this->db->getForeignKeyName('{{%matrixblocks}}', 'typeId'), '{{%matrixblocks}}', 'typeId', '{{%matrixblocktypes}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%matrixblocktypes}}', 'fieldId'), '{{%matrixblocktypes}}', 'fieldId', '{{%fields}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%matrixblocktypes}}', 'fieldLayoutId'), '{{%matrixblocktypes}}', 'fieldLayoutId', '{{%fieldlayouts}}', 'id', 'SET NULL', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%migrations}}', 'pluginId'), '{{%migrations}}', 'pluginId', '{{%plugins}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%relations}}', 'fieldId'), '{{%relations}}', 'fieldId', '{{%fields}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%relations}}', 'sourceId'), '{{%relations}}', 'sourceId', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%relations}}', 'sourceLocale'), '{{%relations}}', 'sourceLocale', '{{%locales}}', 'locale', 'CASCADE', 'CASCADE');
        $this->addForeignKey($this->db->getForeignKeyName('{{%relations}}', 'targetId'), '{{%relations}}', 'targetId', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%routes}}', 'locale'), '{{%routes}}', 'locale', '{{%locales}}', 'locale', 'CASCADE', 'CASCADE');
        $this->addForeignKey($this->db->getForeignKeyName('{{%sections}}', 'structureId'), '{{%sections}}', 'structureId', '{{%structures}}', 'id', 'SET NULL', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%sections_i18n}}', 'locale'), '{{%sections_i18n}}', 'locale', '{{%locales}}', 'locale', 'CASCADE', 'CASCADE');
        $this->addForeignKey($this->db->getForeignKeyName('{{%sections_i18n}}', 'sectionId'), '{{%sections_i18n}}', 'sectionId', '{{%sections}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%sessions}}', 'userId'), '{{%sessions}}', 'userId', '{{%users}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%shunnedmessages}}', 'userId'), '{{%shunnedmessages}}', 'userId', '{{%users}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%structureelements}}', 'elementId'), '{{%structureelements}}', 'elementId', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%structureelements}}', 'structureId'), '{{%structureelements}}', 'structureId', '{{%structures}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%tags}}', 'groupId'), '{{%tags}}', 'groupId', '{{%taggroups}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%tags}}', 'id'), '{{%tags}}', 'id', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%templatecacheelements}}', 'cacheId'), '{{%templatecacheelements}}', 'cacheId', '{{%templatecaches}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%templatecacheelements}}', 'elementId'), '{{%templatecacheelements}}', 'elementId', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%templatecachequeries}}', 'cacheId'), '{{%templatecachequeries}}', 'cacheId', '{{%templatecaches}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%templatecaches}}', 'locale'), '{{%templatecaches}}', 'locale', '{{%locales}}', 'locale', 'CASCADE', 'CASCADE');
        $this->addForeignKey($this->db->getForeignKeyName('{{%usergroups_users}}', 'groupId'), '{{%usergroups_users}}', 'groupId', '{{%usergroups}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%usergroups_users}}', 'userId'), '{{%usergroups_users}}', 'userId', '{{%users}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%userpermissions_usergroups}}', 'groupId'), '{{%userpermissions_usergroups}}', 'groupId', '{{%usergroups}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%userpermissions_usergroups}}', 'permissionId'), '{{%userpermissions_usergroups}}', 'permissionId', '{{%userpermissions}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%userpermissions_users}}', 'permissionId'), '{{%userpermissions_users}}', 'permissionId', '{{%userpermissions}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%userpermissions_users}}', 'userId'), '{{%userpermissions_users}}', 'userId', '{{%users}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%userpreferences}}', 'userId'), '{{%userpreferences}}', 'userId', '{{%users}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%users}}', 'id'), '{{%users}}', 'id', '{{%elements}}', 'id', 'CASCADE', null);
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
        // Add the site locale
        $this->insert(
            '{{%locales}}',
            [
                'locale' => $this->locale,
                'sortOrder' => 1
            ]
        );

        // Populate the info table
        echo "    > populate the info table ...";
        Craft::$app->saveInfo(new Info([
            'version' => Craft::$app->version,
            'build' => Craft::$app->build,
            'schemaVersion' => Craft::$app->schemaVersion,
            'releaseDate' => Craft::$app->releaseDate,
            'edition' => '0',
            'siteName' => $this->siteName,
            'siteUrl' => $this->siteUrl,
            'on' => '1',
            'maintenance' => '0',
            'track' => Craft::$app->track,
            'fieldVersion' => StringHelper::randomString(12),
        ]));
        echo " done\n";
    }
}
