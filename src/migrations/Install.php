<?php

/** @noinspection RepetitiveMethodCallsInspection */

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\migrations;

use Craft;
use craft\base\Field;
use craft\db\Migration;
use craft\db\Table;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\elements\User;
use craft\enums\CmsEdition;
use craft\enums\PropagationMethod;
use craft\errors\InvalidPluginException;
use craft\errors\OperationAbortedException;
use craft\helpers\App;
use craft\helpers\DateTimeHelper;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\StringHelper;
use craft\mail\transportadapters\Sendmail;
use craft\models\CategoryGroup;
use craft\models\Info;
use craft\models\Section;
use craft\models\Site;
use craft\services\ProjectConfig;
use craft\web\Response;
use ReflectionClass;

/**
 * Installation Migration
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Install extends Migration
{
    /**
     * @var string|null The admin user’s username
     */
    public ?string $username = null;

    /**
     * @var string|null The admin user’s password
     */
    public ?string $password = null;

    /**
     * @var string|null The admin user’s email
     */
    public ?string $email = null;

    /**
     * @var Site|null The default site
     */
    public ?Site $site = null;

    /**
     * @var bool Whether to apply the existing project config YAML files, if they exist
     * @since 3.5.9
     */
    public bool $applyProjectConfigYaml = true;

    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->_validateProjectConfig($error)) {
            $message = "Project config validation failed: $error\n\nRun `composer install` or remove your `config/project/` folder and try again.";
            echo "\n$message\n\nAborting install.\n\n";
            throw new OperationAbortedException($message);
        }

        $this->createTables();
        $this->createIndexes();
        $this->addForeignKeys();
        $this->db->getSchema()->refresh();
        return true;
    }

    /**
     * @inheritdoc
     */
    protected function afterUp(): void
    {
        $this->insertDefaultData();
        parent::afterUp();
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        return false;
    }

    /**
     * Creates the tables.
     */
    public function createTables(): void
    {
        $this->createTable(Table::ADDRESSES, [
            'id' => $this->integer()->notNull(),
            'primaryOwnerId' => $this->integer(),
            'fieldId' => $this->integer(),
            'countryCode' => $this->string()->notNull(),
            'administrativeArea' => $this->string(),
            'locality' => $this->string(),
            'dependentLocality' => $this->string(),
            'postalCode' => $this->string(),
            'sortingCode' => $this->string(),
            'addressLine1' => $this->string(),
            'addressLine2' => $this->string(),
            'addressLine3' => $this->string(),
            'organization' => $this->string(),
            'organizationTaxId' => $this->string(),
            'fullName' => $this->string(),
            'firstName' => $this->string(),
            'lastName' => $this->string(),
            'latitude' => $this->string(),
            'longitude' => $this->string(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'PRIMARY KEY([[id]])',
        ]);
        $this->createTable(Table::ANNOUNCEMENTS, [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'pluginId' => $this->integer(),
            'heading' => $this->string()->notNull(),
            'body' => $this->text()->notNull(),
            'unread' => $this->boolean()->defaultValue(true)->notNull(),
            'dateRead' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
        ]);
        $this->createTable(Table::ASSETINDEXDATA, [
            'id' => $this->primaryKey(),
            'sessionId' => $this->integer()->notNull(),
            'volumeId' => $this->integer()->notNull(),
            'uri' => $this->text(),
            'size' => $this->bigInteger()->unsigned(),
            'timestamp' => $this->dateTime(),
            'isDir' => $this->boolean()->defaultValue(false),
            'recordId' => $this->integer(),
            'isSkipped' => $this->boolean()->defaultValue(false),
            'inProgress' => $this->boolean()->defaultValue(false),
            'completed' => $this->boolean()->defaultValue(false),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::ASSETINDEXINGSESSIONS, [
            'id' => $this->primaryKey(),
            'indexedVolumes' => $this->text(),
            'totalEntries' => $this->integer(),
            'processedEntries' => $this->integer()->notNull()->defaultValue(0),
            'cacheRemoteImages' => $this->boolean(),
            'listEmptyFolders' => $this->boolean()->defaultValue(false),
            'isCli' => $this->boolean()->defaultValue(false),
            'actionRequired' => $this->boolean()->defaultValue(false),
            'processIfRootEmpty' => $this->boolean()->defaultValue(false),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::ASSETS, [
            'id' => $this->integer()->notNull(),
            'volumeId' => $this->integer(),
            'folderId' => $this->integer()->notNull(),
            'uploaderId' => $this->integer(),
            'filename' => $this->string()->notNull(),
            'mimeType' => $this->string(),
            'kind' => $this->string(50)->notNull()->defaultValue(Asset::KIND_UNKNOWN),
            'alt' => $this->text(),
            'width' => $this->integer()->unsigned(),
            'height' => $this->integer()->unsigned(),
            'size' => $this->bigInteger()->unsigned(),
            'focalPoint' => $this->string(13)->null(),
            'deletedWithVolume' => $this->boolean()->null(),
            'keptFile' => $this->boolean()->null(),
            'dateModified' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'PRIMARY KEY([[id]])',
        ]);
        $this->createTable(Table::ASSETS_SITES, [
            'assetId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'alt' => $this->text(),
            'PRIMARY KEY([[assetId]], [[siteId]])',
        ]);
        $this->createTable(Table::IMAGETRANSFORMINDEX, [
            'id' => $this->primaryKey(),
            'assetId' => $this->integer()->notNull(),
            'transformer' => $this->string()->null(),
            'filename' => $this->string(),
            'format' => $this->string(),
            'transformString' => $this->string()->notNull(),
            'fileExists' => $this->boolean()->notNull()->defaultValue(false),
            'inProgress' => $this->boolean()->notNull()->defaultValue(false),
            'error' => $this->boolean()->defaultValue(false)->notNull(),
            'dateIndexed' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::IMAGETRANSFORMS, [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'mode' => $this->enum('mode', ['stretch', 'fit', 'crop', 'letterbox'])->notNull()->defaultValue('crop'),
            'position' => $this->enum('position', ['top-left', 'top-center', 'top-right', 'center-left', 'center-center', 'center-right', 'bottom-left', 'bottom-center', 'bottom-right'])->notNull()->defaultValue('center-center'),
            'width' => $this->integer()->unsigned(),
            'height' => $this->integer()->unsigned(),
            'format' => $this->string(),
            'quality' => $this->integer(),
            'interlace' => $this->enum('interlace', ['none', 'line', 'plane', 'partition'])->notNull()->defaultValue('none'),
            'fill' => $this->string(11)->null(),
            'upscale' => $this->boolean()->notNull()->defaultValue(true),
            'parameterChangeTime' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::AUTHENTICATOR, [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'auth2faSecret' => $this->string()->defaultValue(null),
            'oldTimestamp' => $this->integer()->unsigned()->defaultValue(null),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
        ]);
        $this->createTable(Table::BULKOPEVENTS, [
            'key' => $this->char(10)->notNull(),
            'senderClass' => $this->string()->notNull(),
            'eventName' => $this->string()->notNull(),
            'timestamp' => $this->dateTime()->notNull(),
            'PRIMARY KEY([[key]], [[senderClass]], [[eventName]])',
        ]);
        $this->createTable(Table::CATEGORIES, [
            'id' => $this->integer()->notNull(),
            'groupId' => $this->integer()->notNull(),
            'parentId' => $this->integer(),
            'deletedWithGroup' => $this->boolean()->null(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'PRIMARY KEY([[id]])',
        ]);
        $this->createTable(Table::CATEGORYGROUPS, [
            'id' => $this->primaryKey(),
            'structureId' => $this->integer()->notNull(),
            'fieldLayoutId' => $this->integer(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'defaultPlacement' => $this->enum('defaultPlacement', [CategoryGroup::DEFAULT_PLACEMENT_BEGINNING, CategoryGroup::DEFAULT_PLACEMENT_END])->defaultValue('end')->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'dateDeleted' => $this->dateTime()->null(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::CATEGORYGROUPS_SITES, [
            'id' => $this->primaryKey(),
            'groupId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'hasUrls' => $this->boolean()->notNull()->defaultValue(true),
            'uriFormat' => $this->text(),
            'template' => $this->string(500),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::CHANGEDATTRIBUTES, [
            'elementId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'attribute' => $this->string()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'propagated' => $this->boolean()->notNull(),
            'userId' => $this->integer(),
            'PRIMARY KEY([[elementId]], [[siteId]], [[attribute]])',
        ]);
        $this->createTable(Table::CHANGEDFIELDS, [
            'elementId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'fieldId' => $this->integer()->notNull(),
            'layoutElementUid' => $this->uid(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'propagated' => $this->boolean()->notNull(),
            'userId' => $this->integer(),
            'PRIMARY KEY([[elementId]], [[siteId]], [[fieldId]], [[layoutElementUid]])',
        ]);
        $this->createTable(Table::CONTENTBLOCKS, [
            'id' => $this->integer()->notNull(),
            'primaryOwnerId' => $this->integer(),
            'fieldId' => $this->integer(),
            'PRIMARY KEY([[id]])',
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
            'message' => $this->text(),
            'traces' => $this->json(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::DRAFTS, [
            'id' => $this->primaryKey(),
            'canonicalId' => $this->integer(),
            'creatorId' => $this->integer(),
            'provisional' => $this->boolean()->notNull()->defaultValue(false),
            'name' => $this->string()->notNull(),
            'notes' => $this->text(),
            'trackChanges' => $this->boolean()->notNull()->defaultValue(false),
            'dateLastMerged' => $this->dateTime(),
            'saved' => $this->boolean()->notNull()->defaultValue(true),
        ]);
        $this->createTable(Table::ELEMENTACTIVITY, [
            'elementId' => $this->integer()->notNull(),
            'userId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'draftId' => $this->integer()->null(),
            'type' => $this->string()->notNull(),
            'timestamp' => $this->dateTime(),
            'PRIMARY KEY([[elementId]], [[userId]], [[type]])',
        ]);
        $this->createTable(Table::ELEMENTS, [
            'id' => $this->primaryKey(),
            'canonicalId' => $this->integer(),
            'draftId' => $this->integer(),
            'revisionId' => $this->integer(),
            'fieldLayoutId' => $this->integer(),
            'type' => $this->string()->notNull(),
            'enabled' => $this->boolean()->notNull()->defaultValue(true),
            'archived' => $this->boolean()->notNull()->defaultValue(false),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'dateLastMerged' => $this->dateTime()->null(),
            'dateDeleted' => $this->dateTime()->null(),
            'deletedWithOwner' => $this->boolean()->null(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::ELEMENTS_BULKOPS, [
            'elementId' => $this->integer(),
            'key' => $this->char(10)->notNull(),
            'timestamp' => $this->dateTime()->notNull(),
            'PRIMARY KEY([[elementId]], [[key]])',
        ]);
        $this->createTable(Table::ELEMENTS_OWNERS, [
            'elementId' => $this->integer()->notNull(),
            'ownerId' => $this->integer()->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned()->notNull(),
            'PRIMARY KEY([[elementId]], [[ownerId]])',
        ]);
        $this->createTable(Table::ELEMENTS_SITES, [
            'id' => $this->primaryKey(),
            'elementId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'title' => $this->string(),
            'slug' => $this->string(),
            'uri' => $this->string(),
            'content' => $this->json(),
            'enabled' => $this->boolean()->notNull()->defaultValue(true),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::RESOURCEPATHS, [
            'hash' => $this->string()->notNull(),
            'path' => $this->string()->notNull(),
            'PRIMARY KEY([[hash]])',
        ]);
        $this->createTable(Table::REVISIONS, [
            'id' => $this->primaryKey(),
            'canonicalId' => $this->integer()->notNull(),
            'creatorId' => $this->integer(),
            'num' => $this->integer()->notNull(),
            'notes' => $this->text(),
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
            'sectionId' => $this->integer(),
            'parentId' => $this->integer(),
            'primaryOwnerId' => $this->integer(),
            'fieldId' => $this->integer(),
            'typeId' => $this->integer()->notNull(),
            'postDate' => $this->dateTime(),
            'expiryDate' => $this->dateTime(),
            'status' => $this->enum('status', [
                Entry::STATUS_LIVE,
                Entry::STATUS_PENDING,
                Entry::STATUS_EXPIRED,
            ])->notNull()->defaultValue(Entry::STATUS_LIVE),
            'deletedWithEntryType' => $this->boolean()->null(),
            'deletedWithSection' => $this->boolean()->null(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'PRIMARY KEY([[id]])',
        ]);
        $this->createTable(Table::ENTRIES_AUTHORS, [
            'entryId' => $this->integer()->notNull(),
            'authorId' => $this->integer()->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned()->notNull(),
            'PRIMARY KEY([[entryId]], [[authorId]])',
        ]);
        $this->createTable(Table::ENTRYTYPES, [
            'id' => $this->primaryKey(),
            'fieldLayoutId' => $this->integer(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'description' => $this->text(),
            'icon' => $this->string(),
            'color' => $this->string(),
            'hasTitleField' => $this->boolean()->defaultValue(true)->notNull(),
            'titleTranslationMethod' => $this->string()->notNull()->defaultValue(Field::TRANSLATION_METHOD_SITE),
            'titleTranslationKeyFormat' => $this->text(),
            'titleFormat' => $this->string(),
            'showSlugField' => $this->boolean()->defaultValue(true),
            'slugTranslationMethod' => $this->string()->notNull()->defaultValue(Field::TRANSLATION_METHOD_SITE),
            'slugTranslationKeyFormat' => $this->text(),
            'showStatusField' => $this->boolean()->defaultValue(true),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'dateDeleted' => $this->dateTime()->null(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::FIELDLAYOUTS, [
            'id' => $this->primaryKey(),
            'type' => $this->string()->notNull(),
            'config' => $this->json(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'dateDeleted' => $this->dateTime()->null(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::FIELDS, [
            'id' => $this->primaryKey(),
            'name' => $this->text()->notNull(),
            'handle' => $this->string(64)->notNull(),
            'context' => $this->string()->notNull()->defaultValue('global'),
            'columnSuffix' => $this->char(8),
            'instructions' => $this->text(),
            'searchable' => $this->boolean()->notNull()->defaultValue(true),
            'translationMethod' => $this->string()->notNull()->defaultValue(Field::TRANSLATION_METHOD_NONE),
            'translationKeyFormat' => $this->text(),
            'type' => $this->string()->notNull(),
            'settings' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'dateDeleted' => $this->dateTime()->null(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::GLOBALSETS, [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'fieldLayoutId' => $this->integer(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::GQLTOKENS, [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'accessToken' => $this->string()->notNull(),
            'enabled' => $this->boolean()->notNull()->defaultValue(true),
            'expiryDate' => $this->dateTime(),
            'lastUsed' => $this->dateTime(),
            'schemaId' => $this->integer(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::GQLSCHEMAS, [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'scope' => $this->json(),
            'isPublic' => $this->boolean()->notNull()->defaultValue(false),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::INFO, [
            'id' => $this->primaryKey(),
            'version' => $this->string(50)->notNull(),
            'schemaVersion' => $this->string(15)->notNull(),
            'maintenance' => $this->boolean()->defaultValue(false)->notNull(),
            'configVersion' => $this->char(12)->notNull()->defaultValue('000000000000'),
            'fieldVersion' => $this->char(12)->notNull()->defaultValue('000000000000'),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::MIGRATIONS, [
            'id' => $this->primaryKey(),
            'track' => $this->string()->notNull(),
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
            'installDate' => $this->dateTime()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::PROJECTCONFIG, [
            'path' => $this->string()->notNull(),
            'value' => $this->text()->notNull(),
            'PRIMARY KEY([[path]])',
        ]);
        $this->createTable(Table::QUEUE, [
            'id' => $this->primaryKey(),
            'channel' => $this->string()->notNull()->defaultValue('queue'),
            'job' => $this->binary()->notNull(),
            'description' => $this->text(),
            'timePushed' => $this->integer()->notNull(),
            'ttr' => $this->integer()->notNull(),
            'delay' => $this->integer()->defaultValue(0)->notNull(),
            'priority' => $this->integer()->unsigned()->notNull()->defaultValue(1024),
            'dateReserved' => $this->dateTime(),
            'timeUpdated' => $this->integer(),
            'progress' => $this->smallInteger()->notNull()->defaultValue(0),
            'progressLabel' => $this->string(),
            'attempt' => $this->integer(),
            'fail' => $this->boolean()->defaultValue(false),
            'dateFailed' => $this->dateTime(),
            'error' => $this->text(),
        ]);
        $this->createTable(Table::RECOVERYCODES, [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'recoveryCodes' => $this->text()->defaultValue(null),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
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
        $this->createTable(Table::SEARCHINDEXQUEUE, [
            'id' => $this->primaryKey(),
            'elementId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'reserved' => $this->boolean()->notNull()->defaultValue(false),
        ]);
        $this->createTable(Table::SEARCHINDEXQUEUE_FIELDS, [
            'jobId' => $this->integer()->notNull(),
            'fieldHandle' => $this->string()->notNull(),
            'PRIMARY KEY([[jobId]], [[fieldHandle]])',
        ]);
        $this->createTable(Table::SECTIONS, [
            'id' => $this->primaryKey(),
            'structureId' => $this->integer(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'type' => $this->enum('type', [Section::TYPE_SINGLE, Section::TYPE_CHANNEL, Section::TYPE_STRUCTURE])->notNull()->defaultValue('channel'),
            'enableVersioning' => $this->boolean()->defaultValue(false)->notNull(),
            'maxAuthors' => $this->smallInteger()->unsigned(),
            'propagationMethod' => $this->string()->defaultValue(PropagationMethod::All->value)->notNull(),
            'defaultPlacement' => $this->enum('defaultPlacement', [Section::DEFAULT_PLACEMENT_BEGINNING, Section::DEFAULT_PLACEMENT_END])->defaultValue('end')->notNull(),
            'previewTargets' => $this->json(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'dateDeleted' => $this->dateTime()->null(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::SECTIONS_ENTRYTYPES, [
            'sectionId' => $this->integer()->notNull(),
            'typeId' => $this->integer()->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned()->notNull(),
            'name' => $this->string(),
            'handle' => $this->string(),
            'description' => $this->text(),
            'PRIMARY KEY([[sectionId]], [[typeId]])',
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
            'enabled' => $this->string()->notNull()->defaultValue('true'),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'language' => $this->string()->notNull(),
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
        $this->createTable(Table::SSO_IDENTITIES, [
            'provider' => $this->string()->notNull(),
            'identityId' => $this->string()->notNull(),
            'userId' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'PRIMARY KEY([[provider]], [[identityId]], [[userId]])',
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
            'PRIMARY KEY([[id]])',
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
            'description' => $this->text(),
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
            'preferences' => $this->json(),
        ]);
        $this->createTable(Table::USERS, [
            'id' => $this->integer()->notNull(),
            'photoId' => $this->integer(),
            'affiliatedSiteId' => $this->integer(),
            'active' => $this->boolean()->defaultValue(false)->notNull(),
            'pending' => $this->boolean()->defaultValue(false)->notNull(),
            'locked' => $this->boolean()->defaultValue(false)->notNull(),
            'suspended' => $this->boolean()->defaultValue(false)->notNull(),
            'admin' => $this->boolean()->defaultValue(false)->notNull(),
            'username' => $this->string(),
            'fullName' => $this->string(),
            'firstName' => $this->string(),
            'lastName' => $this->string(),
            'email' => $this->string(),
            'password' => $this->string(),
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
            'fs' => $this->string()->notNull(),
            'subpath' => $this->string(),
            'transformFs' => $this->string(),
            'transformSubpath' => $this->string(),
            'titleTranslationMethod' => $this->string()->notNull()->defaultValue(Field::TRANSLATION_METHOD_SITE),
            'titleTranslationKeyFormat' => $this->text(),
            'altTranslationMethod' => $this->string()->notNull()->defaultValue(Field::TRANSLATION_METHOD_SITE),
            'altTranslationKeyFormat' => $this->text(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'dateDeleted' => $this->dateTime()->null(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::WEBAUTHN, [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'credentialId' => $this->string()->defaultValue(null),
            'credential' => $this->text()->defaultValue(null),
            'credentialName' => $this->string()->defaultValue(null),
            'dateLastUsed' => $this->dateTime()->defaultValue(null),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createTable(Table::WIDGETS, [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'type' => $this->string()->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'colspan' => $this->tinyInteger(),
            'settings' => $this->json(),
            'enabled' => $this->boolean()->defaultValue(true)->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
    }

    /**
     * Creates the indexes.
     */
    public function createIndexes(): void
    {
        $this->createIndex(null, Table::ANNOUNCEMENTS, ['userId', 'unread', 'dateRead', 'dateCreated'], false);
        $this->createIndex(null, Table::ANNOUNCEMENTS, ['dateRead'], false);
        $this->createIndex(null, Table::ASSETINDEXDATA, ['sessionId', 'volumeId']);
        $this->createIndex(null, Table::ASSETINDEXDATA, ['volumeId'], false);
        $this->createIndex(null, Table::ASSETS, ['filename', 'folderId'], false);
        $this->createIndex(null, Table::ASSETS, ['folderId'], false);
        $this->createIndex(null, Table::ASSETS, ['volumeId'], false);
        $this->createIndex(null, Table::BULKOPEVENTS, ['timestamp'], false);
        $this->createIndex(null, Table::CATEGORIES, ['groupId'], false);
        $this->createIndex(null, Table::CATEGORYGROUPS, ['name'], false);
        $this->createIndex(null, Table::CATEGORYGROUPS, ['handle'], false);
        $this->createIndex(null, Table::CATEGORYGROUPS, ['structureId'], false);
        $this->createIndex(null, Table::CATEGORYGROUPS, ['fieldLayoutId'], false);
        $this->createIndex(null, Table::CATEGORYGROUPS, ['dateDeleted'], false);
        $this->createIndex(null, Table::CATEGORYGROUPS_SITES, ['groupId', 'siteId'], true);
        $this->createIndex(null, Table::CATEGORYGROUPS_SITES, ['siteId'], false);
        $this->createIndex(null, Table::CHANGEDATTRIBUTES, ['elementId', 'siteId', 'dateUpdated']);
        $this->createIndex(null, Table::CHANGEDFIELDS, ['elementId', 'siteId', 'dateUpdated']);
        $this->createIndex(null, Table::CONTENTBLOCKS, ['primaryOwnerId'], false);
        $this->createIndex(null, Table::CONTENTBLOCKS, ['fieldId'], false);
        $this->createIndex(null, Table::DEPRECATIONERRORS, ['key', 'fingerprint'], true);
        $this->createIndex(null, Table::DRAFTS, ['creatorId', 'provisional'], false);
        $this->createIndex(null, Table::DRAFTS, ['saved'], false);
        $this->createIndex(null, Table::ELEMENTACTIVITY, ['elementId', 'timestamp', 'userId'], false);
        $this->createIndex(null, Table::ELEMENTS, ['dateDeleted'], false);
        $this->createIndex(null, Table::ELEMENTS, ['fieldLayoutId'], false);
        $this->createIndex(null, Table::ELEMENTS, ['type'], false);
        $this->createIndex(null, Table::ELEMENTS, ['enabled'], false);
        $this->createIndex(null, Table::ELEMENTS, ['canonicalId'], false);
        $this->createIndex(null, Table::ELEMENTS, ['archived', 'dateCreated'], false);
        $this->createIndex(null, Table::ELEMENTS, ['archived', 'dateDeleted', 'draftId', 'revisionId', 'canonicalId'], false);
        $this->createIndex(null, Table::ELEMENTS, ['archived', 'dateDeleted', 'draftId', 'revisionId', 'canonicalId', 'enabled'], false);
        $this->createIndex(null, Table::ELEMENTS_BULKOPS, ['timestamp'], false);
        $this->createIndex(null, Table::ELEMENTS_SITES, ['elementId', 'siteId'], true);
        $this->createIndex(null, Table::ELEMENTS_SITES, ['siteId'], false);
        $this->createIndex(null, Table::ELEMENTS_SITES, ['title', 'siteId'], false);
        $this->createIndex(null, Table::ELEMENTS_SITES, ['slug', 'siteId'], false);
        $this->createIndex(null, Table::ELEMENTS_SITES, ['enabled'], false);
        $this->createIndex(null, Table::SYSTEMMESSAGES, ['key', 'language'], true);
        $this->createIndex(null, Table::SYSTEMMESSAGES, ['language'], false);
        $this->createIndex(null, Table::ENTRIES, ['postDate'], false);
        $this->createIndex(null, Table::ENTRIES, ['expiryDate'], false);
        $this->createIndex(null, Table::ENTRIES, ['status'], false);
        $this->createIndex(null, Table::ENTRIES, ['sectionId'], false);
        $this->createIndex(null, Table::ENTRIES, ['typeId'], false);
        $this->createIndex(null, Table::ENTRIES_AUTHORS, ['authorId'], false);
        $this->createIndex(null, Table::ENTRIES_AUTHORS, ['entryId', 'sortOrder'], false);
        $this->createIndex(null, Table::ENTRIES, ['primaryOwnerId'], false);
        $this->createIndex(null, Table::ENTRIES, ['fieldId'], false);
        $this->createIndex(null, Table::ENTRYTYPES, ['fieldLayoutId'], false);
        $this->createIndex(null, Table::ENTRYTYPES, ['dateDeleted'], false);
        $this->createIndex(null, Table::FIELDLAYOUTS, ['dateDeleted'], false);
        $this->createIndex(null, Table::FIELDLAYOUTS, ['type'], false);
        $this->createIndex(null, Table::FIELDS, ['handle', 'context']);
        $this->createIndex(null, Table::FIELDS, ['context'], false);
        $this->createIndex(null, Table::FIELDS, ['dateDeleted'], false);
        $this->createIndex(null, Table::GLOBALSETS, ['name'], false);
        $this->createIndex(null, Table::GLOBALSETS, ['handle'], false);
        $this->createIndex(null, Table::GLOBALSETS, ['fieldLayoutId'], false);
        $this->createIndex(null, Table::GLOBALSETS, ['sortOrder'], false);
        $this->createIndex(null, Table::GQLTOKENS, ['accessToken'], true);
        $this->createIndex(null, Table::GQLTOKENS, ['name'], true);
        $this->createIndex(null, Table::IMAGETRANSFORMINDEX, ['assetId', 'transformString'], false);
        $this->createIndex(null, Table::IMAGETRANSFORMS, ['name']);
        $this->createIndex(null, Table::IMAGETRANSFORMS, ['handle']);
        $this->createIndex(null, Table::MIGRATIONS, ['track', 'name'], true);
        $this->createIndex(null, Table::PLUGINS, ['handle'], true);
        $this->createIndex(null, Table::QUEUE, ['channel', 'fail', 'timeUpdated', 'timePushed']);
        $this->createIndex(null, Table::QUEUE, ['channel', 'fail', 'timeUpdated', 'delay']);
        $this->createIndex(null, Table::RELATIONS, ['fieldId', 'sourceId', 'sourceSiteId', 'targetId'], true);
        $this->createIndex(null, Table::RELATIONS, ['sourceId'], false);
        $this->createIndex(null, Table::RELATIONS, ['targetId'], false);
        $this->createIndex(null, Table::RELATIONS, ['sourceSiteId'], false);
        $this->createIndex(null, Table::REVISIONS, ['canonicalId', 'num'], true);
        $this->createIndex(null, Table::SEARCHINDEXQUEUE, ['elementId', 'siteId', 'reserved'], false);
        $this->createIndex(null, Table::SEARCHINDEXQUEUE_FIELDS, ['jobId', 'fieldHandle'], true);
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
        $this->createIndex(null, Table::TOKENS, ['token'], true);
        $this->createIndex(null, Table::TOKENS, ['expiryDate'], false);
        $this->createIndex(null, Table::USERGROUPS, ['handle']);
        $this->createIndex(null, Table::USERGROUPS, ['name']);
        $this->createIndex(null, Table::USERGROUPS_USERS, ['groupId', 'userId'], true);
        $this->createIndex(null, Table::USERGROUPS_USERS, ['userId'], false);
        $this->createIndex(null, Table::USERPERMISSIONS, ['name'], true);
        $this->createIndex(null, Table::USERPERMISSIONS_USERGROUPS, ['permissionId', 'groupId'], true);
        $this->createIndex(null, Table::USERPERMISSIONS_USERGROUPS, ['groupId'], false);
        $this->createIndex(null, Table::USERPERMISSIONS_USERS, ['permissionId', 'userId'], true);
        $this->createIndex(null, Table::USERPERMISSIONS_USERS, ['userId'], false);
        $this->createIndex(null, Table::USERS, ['active'], false);
        $this->createIndex(null, Table::USERS, ['locked'], false);
        $this->createIndex(null, Table::USERS, ['pending'], false);
        $this->createIndex(null, Table::USERS, ['suspended'], false);
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
                'PRIMARY KEY([[elementId]], [[attribute]], [[fieldId]], [[siteId]])',
            ]);

            $sql = 'CREATE FULLTEXT INDEX ' .
                $this->db->quoteTableName($this->db->getIndexName()) . ' ON ' .
                $this->db->quoteTableName(Table::SEARCHINDEX) . ' ' .
                '(' . $this->db->quoteColumnName('keywords') . ')';

            $this->db->createCommand($sql)->execute();
        } else {
            // Postgres is case-sensitive
            $this->createIndex(null, Table::ELEMENTS_SITES, ['lower([[uri]])', 'siteId']);
            $this->createIndex(null, Table::USERS, ['lower([[email]])']);
            $this->createIndex(null, Table::USERS, ['lower([[username]])']);

            $this->createTable(Table::SEARCHINDEX, [
                'elementId' => $this->integer()->notNull(),
                'attribute' => $this->string(25)->notNull(),
                'fieldId' => $this->integer()->notNull(),
                'siteId' => $this->integer()->notNull(),
                'keywords' => $this->text()->notNull(),
                'keywords_vector' => $this->db->getSchema()->createColumnSchemaBuilder('tsvector')->notNull(),
                'PRIMARY KEY([[elementId]], [[attribute]], [[fieldId]], [[siteId]])',
            ]);

            $sql = 'CREATE INDEX ' . $this->db->quoteTableName($this->db->getIndexName()) . ' ON ' . Table::SEARCHINDEX . ' USING GIN([[keywords_vector]] [[pg_catalog]].[[tsvector_ops]]) WITH (FASTUPDATE=YES)';
            $this->db->createCommand($sql)->execute();

            $sql = 'CREATE INDEX ' . $this->db->quoteTableName($this->db->getIndexName()) . ' ON ' . Table::SEARCHINDEX . ' USING btree(keywords)';
            $this->db->createCommand($sql)->execute();
        }
    }

    /**
     * Adds the foreign keys.
     */
    public function addForeignKeys(): void
    {
        $this->addForeignKey(null, Table::ADDRESSES, ['id'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ADDRESSES, ['primaryOwnerId'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ANNOUNCEMENTS, ['userId'], Table::USERS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ANNOUNCEMENTS, ['pluginId'], Table::PLUGINS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ASSETINDEXDATA, ['volumeId'], Table::VOLUMES, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ASSETINDEXDATA, ['sessionId'], Table::ASSETINDEXINGSESSIONS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ASSETS, ['folderId'], Table::VOLUMEFOLDERS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ASSETS, ['id'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ASSETS, ['uploaderId'], Table::USERS, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::ASSETS, ['volumeId'], Table::VOLUMES, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ASSETS_SITES, ['assetId'], Table::ASSETS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ASSETS_SITES, ['siteId'], Table::SITES, ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, Table::AUTHENTICATOR, ['userId'], Table::USERS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::CATEGORIES, ['groupId'], Table::CATEGORYGROUPS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::CATEGORIES, ['id'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::CATEGORIES, ['parentId'], Table::CATEGORIES, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::CATEGORYGROUPS, ['fieldLayoutId'], Table::FIELDLAYOUTS, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::CATEGORYGROUPS, ['structureId'], Table::STRUCTURES, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::CATEGORYGROUPS_SITES, ['groupId'], Table::CATEGORYGROUPS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::CATEGORYGROUPS_SITES, ['siteId'], Table::SITES, ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, Table::CHANGEDATTRIBUTES, ['elementId'], Table::ELEMENTS, ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, Table::CHANGEDATTRIBUTES, ['siteId'], Table::SITES, ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, Table::CHANGEDATTRIBUTES, ['userId'], Table::USERS, ['id'], 'SET NULL', 'CASCADE');
        $this->addForeignKey(null, Table::CHANGEDFIELDS, ['elementId'], Table::ELEMENTS, ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, Table::CHANGEDFIELDS, ['siteId'], Table::SITES, ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, Table::CHANGEDFIELDS, ['fieldId'], Table::FIELDS, ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, Table::CHANGEDFIELDS, ['userId'], Table::USERS, ['id'], 'SET NULL', 'CASCADE');
        $this->addForeignKey(null, Table::CONTENTBLOCKS, ['id'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::CONTENTBLOCKS, ['fieldId'], Table::FIELDS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::CONTENTBLOCKS, ['primaryOwnerId'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::CRAFTIDTOKENS, ['userId'], Table::USERS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::DRAFTS, ['creatorId'], Table::USERS, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::DRAFTS, ['canonicalId'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ELEMENTACTIVITY, ['elementId'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ELEMENTACTIVITY, ['userId'], Table::USERS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ELEMENTACTIVITY, ['siteId'], Table::SITES, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ELEMENTACTIVITY, ['draftId'], Table::DRAFTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ELEMENTS, ['canonicalId'], Table::ELEMENTS, ['id'], 'SET NULL');
        $this->addForeignKey(null, Table::ELEMENTS, ['draftId'], Table::DRAFTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ELEMENTS, ['revisionId'], Table::REVISIONS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ELEMENTS, ['fieldLayoutId'], Table::FIELDLAYOUTS, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::ELEMENTS_OWNERS, ['elementId'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ELEMENTS_OWNERS, ['ownerId'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ELEMENTS_SITES, ['elementId'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ELEMENTS_SITES, ['siteId'], Table::SITES, ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, Table::ENTRIES, ['id'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ENTRIES, ['sectionId'], Table::SECTIONS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ENTRIES, ['parentId'], Table::ENTRIES, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::ENTRIES, ['typeId'], Table::ENTRYTYPES, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ENTRIES_AUTHORS, ['entryId'], Table::ENTRIES, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ENTRIES_AUTHORS, ['authorId'], Table::USERS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ENTRIES, ['fieldId'], Table::FIELDS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ENTRIES, ['primaryOwnerId'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ENTRYTYPES, ['fieldLayoutId'], Table::FIELDLAYOUTS, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::GLOBALSETS, ['fieldLayoutId'], Table::FIELDLAYOUTS, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::GLOBALSETS, ['id'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::GQLTOKENS, 'schemaId', Table::GQLSCHEMAS, 'id', 'SET NULL', null);
        $this->addForeignKey(null, Table::RELATIONS, ['fieldId'], Table::FIELDS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::RELATIONS, ['sourceId'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::RELATIONS, ['sourceSiteId'], Table::SITES, ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, Table::REVISIONS, ['creatorId'], Table::USERS, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::REVISIONS, ['canonicalId'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::SEARCHINDEXQUEUE, 'elementId', Table::ELEMENTS, 'id', 'CASCADE', null);
        $this->addForeignKey(null, Table::SEARCHINDEXQUEUE_FIELDS, 'jobId', Table::SEARCHINDEXQUEUE, 'id', 'CASCADE', null);
        $this->addForeignKey(null, Table::SECTIONS, ['structureId'], Table::STRUCTURES, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::SECTIONS_ENTRYTYPES, ['sectionId'], Table::SECTIONS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::SECTIONS_ENTRYTYPES, ['typeId'], Table::ENTRYTYPES, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::SECTIONS_SITES, ['siteId'], Table::SITES, ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, Table::SECTIONS_SITES, ['sectionId'], Table::SECTIONS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::SESSIONS, ['userId'], Table::USERS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::SHUNNEDMESSAGES, ['userId'], Table::USERS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::SITES, ['groupId'], Table::SITEGROUPS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::SSO_IDENTITIES, ['userId'], Table::USERS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::STRUCTUREELEMENTS, ['structureId'], Table::STRUCTURES, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::TAGGROUPS, ['fieldLayoutId'], Table::FIELDLAYOUTS, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::TAGS, ['groupId'], Table::TAGGROUPS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::TAGS, ['id'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::USERGROUPS_USERS, ['groupId'], Table::USERGROUPS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::USERGROUPS_USERS, ['userId'], Table::USERS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::USERPERMISSIONS_USERGROUPS, ['groupId'], Table::USERGROUPS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::USERPERMISSIONS_USERGROUPS, ['permissionId'], Table::USERPERMISSIONS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::USERPERMISSIONS_USERS, ['permissionId'], Table::USERPERMISSIONS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::USERPERMISSIONS_USERS, ['userId'], Table::USERS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::USERPREFERENCES, ['userId'], Table::USERS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::USERS, ['id'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::USERS, ['photoId'], Table::ASSETS, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::USERS, ['affiliatedSiteId'], Table::SITES, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::VOLUMEFOLDERS, ['parentId'], Table::VOLUMEFOLDERS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::VOLUMEFOLDERS, ['volumeId'], Table::VOLUMES, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::VOLUMES, ['fieldLayoutId'], Table::FIELDLAYOUTS, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::WEBAUTHN, ['userId'], Table::USERS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::WIDGETS, ['userId'], Table::USERS, ['id'], 'CASCADE', null);
    }

    /**
     * Populates the DB with the default data.
     */
    public function insertDefaultData(): void
    {
        // Populate the info table
        echo '    > populating the info table ... ';
        Craft::$app->saveInfo(new Info([
            'version' => Craft::$app->getVersion(),
            'schemaVersion' => Craft::$app->schemaVersion,
            'maintenance' => false,
            'configVersion' => StringHelper::randomString(12),
            'fieldVersion' => StringHelper::randomString(12),
        ]));
        echo "done\n";

        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $projectConfig = Craft::$app->getProjectConfig();

        if ($this->applyProjectConfigYaml) {
            // Make sure at least sites are processed
            ProjectConfigHelper::ensureAllSitesProcessed(true);
            $this->_installPlugins();
            // Save the existing system settings
            echo '    > applying the project config ... ';
            $projectConfig->applyExternalChanges();
            echo "done\n";
        } else {
            // Save the default system settings
            echo '    > saving default data ... ';
            $configData = $this->_generateInitialConfig();
            $projectConfig->applyConfigChanges($configData);
            echo "done\n";
        }

        // Craft, you are installed now.
        Craft::$app->setIsInstalled();

        if ($this->applyProjectConfigYaml) {
            // Update the primary site with the installer settings
            $sitesService = Craft::$app->getSites();
            $site = $sitesService->getPrimarySite();
            $site->setBaseUrl($this->site->getBaseUrl(false));
            $site->hasUrls = $this->site->hasUrls;
            $site->language = $this->site->language;
            $site->setName($this->site->getName(false));
            $sitesService->saveSite($site);
        }

        // Set the app language
        Craft::$app->language = $this->site->language;

        // Save the first user
        echo '    > saving the first user ... ';
        $user = new User([
            'active' => true,
            'admin' => true,
            'username' => $this->username,
            'newPassword' => $this->password,
            'email' => $this->email,
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

    /**
     * Validates the existing project config data, if present.
     */
    private function _validateProjectConfig(string &$error = null): bool
    {
        if (!$this->applyProjectConfigYaml) {
            return true;
        }

        $projectConfig = Craft::$app->getProjectConfig();
        if (!$projectConfig->getDoesExternalConfigExist()) {
            $this->applyProjectConfigYaml = false;
            return true;
        }

        $expectedSchemaVersion = (string)$projectConfig->get(ProjectConfig::PATH_SCHEMA_VERSION, true);
        $craftSchemaVersion = Craft::$app->schemaVersion;

        // Compare existing Craft schema version with the one that is being applied.
        if (!version_compare($craftSchemaVersion, $expectedSchemaVersion, '=')) {
            $error = "Craft CMS is Composer-installed with schema version $craftSchemaVersion, but project.yaml expects $expectedSchemaVersion.";
            return false;
        }

        $pluginsService = Craft::$app->getPlugins();
        $pluginConfigs = $projectConfig->get(ProjectConfig::PATH_PLUGINS, true) ?? [];

        // Make sure that all to-be-installed plugins actually exist,
        // and that they have the same schema as project.yaml
        foreach ($pluginConfigs as $handle => $pluginConfig) {
            try {
                $pluginInfo = $pluginsService->getPluginInfo($handle);
            } catch (InvalidPluginException) {
                $error = "The “{$handle}” plugin is not Composer-installed, but project.yaml expects it to be.";
                return false;
            }

            if (isset($pluginInfo['schemaVersion'])) {
                $schemaVersion = $pluginInfo['schemaVersion'];
            } else {
                $pluginRef = new ReflectionClass($pluginInfo['class']);
                $schemaVersion = $pluginRef->getProperty('schemaVersion')->getDefaultValue();
            }

            $expectedSchemaVersion = $pluginConfig['schemaVersion'] ?? null;

            if ($schemaVersion && $expectedSchemaVersion && $schemaVersion != $expectedSchemaVersion) {
                $error = "{$pluginInfo['name']} is installed with schema version $schemaVersion, but project.yaml expects $expectedSchemaVersion.";
                return false;
            }
        }

        return true;
    }

    /**
     * Attempts to install any plugins listed in project.yaml.
     */
    private function _installPlugins(): void
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $pluginsService = Craft::$app->getPlugins();
        $pluginConfigs = $projectConfig->get(ProjectConfig::PATH_PLUGINS, true) ?? [];

        // Prevent the plugin from sending any headers, etc.
        $realResponse = Craft::$app->getResponse();
        $tempResponse = new Response(['isSent' => true]);
        Craft::$app->set('response', $tempResponse);

        try {
            foreach ($pluginConfigs as $handle => $pluginConfig) {
                echo "    > installing $handle ... ";
                $pluginsService->installPlugin($handle);
                echo "done\n";
            }
        } finally {
            // Put the real response back
            Craft::$app->set('response', $realResponse);
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
            'dateModified' => DateTimeHelper::currentTimeStamp(),
            'email' => [
                'fromEmail' => $this->email,
                'fromName' => $this->site->getName(),
                'transportType' => Sendmail::class,
            ],
            'siteGroups' => [
                $siteGroupUid => [
                    'name' => $this->site->getName(),
                ],
            ],
            'sites' => [
                StringHelper::UUID() => [
                    'baseUrl' => $this->site->getBaseUrl(false),
                    'handle' => $this->site->handle,
                    'hasUrls' => $this->site->hasUrls,
                    'language' => $this->site->language,
                    'name' => $this->site->getName(false),
                    'primary' => true,
                    'siteGroup' => $siteGroupUid,
                    'sortOrder' => 1,
                ],
            ],
            'system' => [
                'edition' => CmsEdition::Solo->handle(),
                'name' => $this->site->getName(),
                'live' => true,
                'schemaVersion' => Craft::$app->schemaVersion,
                'timeZone' => 'America/Los_Angeles',
            ],
            'users' => [
                'requireEmailVerification' => true,
                'allowPublicRegistration' => false,
                'defaultGroup' => null,
                'photoVolumeUid' => null,
                'photoSubpath' => null,
                'require2fa' => false,
            ],
        ];
    }
}
