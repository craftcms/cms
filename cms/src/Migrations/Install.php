<?php

/** @noinspection RepetitiveMethodCallsInspection */

namespace CraftCms\Cms\Migrations;

use Craft;
use craft\base\Field;
use craft\db\Table;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\elements\User;
use craft\errors\InvalidPluginException;
use craft\errors\OperationAbortedException;
use craft\helpers\DateTimeHelper;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\mail\transportadapters\Sendmail;
use craft\models\CategoryGroup;
use craft\models\Info;
use craft\models\Section;
use craft\models\Site;
use craft\services\ProjectConfig;
use craft\web\Response;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Support\Str;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Prompts\Output\ConsoleOutput;
use ReflectionClass;

/** @since 6.0.0 */
class Install extends Migration
{
    private ConsoleOutput $output;

    public function __construct(
        public ?string $username = null,
        public ?string $password = null,
        public ?string $email = null,
        public ?Site $site = null,
        public bool $applyProjectConfigYaml = true
    ) {
        $this->output = new ConsoleOutput;
    }

    public function up(): bool
    {
        if (! $this->_validateProjectConfig($error)) {
            $message = "Project config validation failed: $error Run `composer install` or remove your `config/project/` folder and try again.";
            $this->output->writeln($message);
            $this->output->writeln('');
            $this->output->writeln('Aborting install.');
            throw new OperationAbortedException($message);
        }

        $this->createTables();
        $this->createIndexes();
        $this->addForeignKeys();

        $this->insertDefaultData();

        return true;
    }

    /**
     * Creates the tables.
     */
    public function createTables(): void
    {
        Schema::create(Table::withoutYiiPlaceholder(Table::ADDRESSES), function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('primaryOwnerId')->nullable();
            $table->integer('fieldId')->nullable();
            $table->string('countryCode');
            $table->string('administrativeArea')->nullable();
            $table->string('locality')->nullable();
            $table->string('dependentLocality')->nullable();
            $table->string('postalCode')->nullable();
            $table->string('sortingCode')->nullable();
            $table->string('addressLine1')->nullable();
            $table->string('addressLine2')->nullable();
            $table->string('addressLine3')->nullable();
            $table->string('organization')->nullable();
            $table->string('organizationTaxId')->nullable();
            $table->string('fullName')->nullable();
            $table->string('firstName')->nullable();
            $table->string('lastName')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('userId');
            $table->integer('pluginId')->nullable();
            $table->string('heading');
            $table->text('body');
            $table->boolean('unread')->default(true);
            $table->dateTime('dateRead')->nullable();
            $table->dateTime('dateCreated');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::ASSETINDEXDATA), function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('sessionId');
            $table->integer('volumeId');
            $table->text('uri')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->dateTime('timestamp')->nullable();
            $table->boolean('isDir')->default(false)->nullable();
            $table->integer('recordId')->nullable();
            $table->boolean('isSkipped')->default(false)->nullable();
            $table->boolean('inProgress')->default(false)->nullable();
            $table->boolean('completed')->default(false)->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::ASSETINDEXINGSESSIONS), function (Blueprint $table) {
            $table->integer('id', true);
            $table->text('indexedVolumes')->nullable();
            $table->integer('totalEntries')->nullable();
            $table->integer('processedEntries')->default(0);
            $table->boolean('cacheRemoteImages')->nullable();
            $table->boolean('listEmptyFolders')->default(false)->nullable();
            $table->boolean('isCli')->default(false)->nullable();
            $table->boolean('actionRequired')->nullable()->default(false);
            $table->boolean('processIfRootEmpty')->default(false)->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::ASSETS), function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->integer('volumeId')->nullable();
            $table->integer('folderId');
            $table->integer('uploaderId')->nullable();
            $table->string('filename');
            $table->string('mimeType')->nullable();
            $table->string('kind', 50)->default(Asset::KIND_UNKNOWN);
            $table->text('alt')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('focalPoint', 13)->nullable()->default(null);
            $table->boolean('deletedWithVolume')->nullable();
            $table->boolean('keptFile')->nullable();
            $table->dateTime('dateModified')->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::ASSETS_SITES), function (Blueprint $table) {
            $table->integer('assetId');
            $table->integer('siteId');
            $table->text('alt')->nullable();
            $table->primary(['assetId', 'siteId']);
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::IMAGETRANSFORMINDEX), function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('assetId');
            $table->string('transformer')->default(null)->nullable();
            $table->string('filename')->nullable();
            $table->string('format')->nullable();
            $table->string('transformString');
            $table->boolean('fileExists')->default(false);
            $table->boolean('inProgress')->default(false);
            $table->boolean('error')->default(false);
            $table->dateTime('dateIndexed')->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::IMAGETRANSFORMS), function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name');
            $table->string('handle');
            $table->enum('mode', ['stretch', 'fit', 'crop', 'letterbox'])->default('crop');
            $table->enum('position', ['top-left', 'top-center', 'top-right', 'center-left', 'center-center', 'center-right', 'bottom-left', 'bottom-center', 'bottom-right'])->default('center-center');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('format')->nullable();
            $table->integer('quality')->nullable();
            $table->enum('interlace', ['none', 'line', 'plane', 'partition'])->default('none');
            $table->string('fill', 11)->nullable()->default(null);
            $table->boolean('upscale')->default(true);
            $table->dateTime('parameterChangeTime')->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::AUTHENTICATOR), function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('userId');
            $table->string('auth2faSecret')->default(null)->nullable();
            $table->unsignedInteger('oldTimestamp')->default(null)->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::BULKOPEVENTS), function (Blueprint $table) {
            $table->char('key', 10);
            $table->string('senderClass');
            $table->string('eventName');
            $table->dateTime('timestamp');
            $table->primary(['key', 'senderClass', 'eventName']);
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::CATEGORIES), function (Blueprint $table) {
            $table->integer('id');
            $table->integer('groupId');
            $table->integer('parentId')->nullable();
            $table->boolean('deletedWithGroup')->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->primary('id');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::CATEGORYGROUPS), function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('structureId');
            $table->integer('fieldLayoutId')->nullable();
            $table->string('name');
            $table->string('handle');
            $table->enum('defaultPlacement', [CategoryGroup::DEFAULT_PLACEMENT_BEGINNING, CategoryGroup::DEFAULT_PLACEMENT_END])->default('end');
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->dateTime('dateDeleted')->nullable()->default(null);
            $table->char('uid', 36)->default('0');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::CATEGORYGROUPS_SITES), function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('groupId');
            $table->integer('siteId');
            $table->boolean('hasUrls')->default(true);
            $table->text('uriFormat')->nullable();
            $table->string('template', 500)->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::CHANGEDATTRIBUTES), function (Blueprint $table) {
            $table->integer('elementId');
            $table->integer('siteId');
            $table->string('attribute');
            $table->dateTime('dateUpdated');
            $table->boolean('propagated');
            $table->integer('userId')->nullable();
            $table->primary(['elementId', 'siteId', 'attribute']);
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::CHANGEDFIELDS), function (Blueprint $table) {
            $table->integer('elementId');
            $table->integer('siteId');
            $table->integer('fieldId');
            $table->char('layoutElementUid', 36)->default('0');
            $table->dateTime('dateUpdated');
            $table->boolean('propagated');
            $table->integer('userId')->nullable();
            $table->primary(['elementId', 'siteId', 'fieldId', 'layoutElementUid']);
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::CONTENTBLOCKS), function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('primaryOwnerId')->nullable();
            $table->integer('fieldId')->nullable();
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::CRAFTIDTOKENS), function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('userId');
            $table->text('accessToken');
            $table->dateTime('expiryDate')->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::DEPRECATIONERRORS), function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('key');
            $table->string('fingerprint');
            $table->dateTime('lastOccurrence');
            $table->string('file');
            $table->unsignedSmallInteger('line')->nullable();
            $table->text('message')->nullable();
            $table->jsonb('traces')->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::DRAFTS), function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('canonicalId')->nullable();
            $table->integer('creatorId')->nullable();
            $table->boolean('provisional')->default(false);
            $table->string('name');
            $table->text('notes')->nullable();
            $table->boolean('trackChanges')->default(false);
            $table->dateTime('dateLastMerged')->nullable();
            $table->boolean('saved')->default(true);
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::ELEMENTACTIVITY), function (Blueprint $table) {
            $table->integer('elementId');
            $table->integer('userId');
            $table->integer('siteId');
            $table->integer('draftId')->nullable();
            $table->string('type');
            $table->dateTime('timestamp')->nullable();
            $table->primary(['elementId', 'userId', 'type']);
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::ELEMENTS), function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('canonicalId')->nullable();
            $table->integer('draftId')->nullable();
            $table->integer('revisionId')->nullable();
            $table->integer('fieldLayoutId')->nullable();
            $table->string('type');
            $table->boolean('enabled')->default(true);
            $table->boolean('archived')->default(false);
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->dateTime('dateLastMerged')->nullable();
            $table->dateTime('dateDeleted')->nullable()->default(null);
            $table->boolean('deletedWithOwner')->nullable();
            $table->char('uid', 36)->default('0');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::ELEMENTS_BULKOPS), function (Blueprint $table) {
            $table->integer('elementId');
            $table->char('key', 10);
            $table->dateTime('timestamp');
            $table->primary(['elementId', 'key']);
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::ELEMENTS_OWNERS), function (Blueprint $table) {
            $table->integer('elementId');
            $table->integer('ownerId');
            $table->unsignedSmallInteger('sortOrder');
            $table->primary(['elementId', 'ownerId']);
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::ELEMENTS_SITES), function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('elementId');
            $table->integer('siteId');
            $table->string('title')->nullable();
            $table->string('slug')->nullable();
            $table->string('uri')->nullable();
            $table->jsonb('content')->nullable();
            $table->boolean('enabled')->default(true);
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::RESOURCEPATHS), function (Blueprint $table) {
            $table->string('hash');
            $table->string('path');
            $table->primary('hash');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::REVISIONS), function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('canonicalId');
            $table->integer('creatorId')->nullable();
            $table->integer('num');
            $table->text('notes')->nullable();
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::SEQUENCES), function (Blueprint $table) {
            $table->string('name');
            $table->unsignedInteger('next')->default(1);
            $table->primary('name');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::SYSTEMMESSAGES), function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('language');
            $table->string('key');
            $table->text('subject');
            $table->text('body');
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::ENTRIES), function (Blueprint $table) {
            $table->integer('id');
            $table->integer('sectionId')->nullable();
            $table->integer('parentId')->nullable();
            $table->integer('primaryOwnerId')->nullable();
            $table->integer('fieldId')->nullable();
            $table->integer('typeId');
            $table->dateTime('postDate')->nullable();
            $table->dateTime('expiryDate')->nullable();
            $table->enum('status', [
                Entry::STATUS_LIVE,
                Entry::STATUS_PENDING,
                Entry::STATUS_EXPIRED,
            ])->default(Entry::STATUS_LIVE);
            $table->boolean('deletedWithEntryType')->nullable();
            $table->boolean('deletedWithSection')->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->primary('id');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::ENTRIES_AUTHORS), function (Blueprint $table) {
            $table->integer('entryId');
            $table->integer('authorId');
            $table->unsignedSmallInteger('sortOrder');
            $table->primary(['entryId', 'authorId']);
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::ENTRYTYPES), function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('fieldLayoutId')->nullable();
            $table->string('name');
            $table->string('handle');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->boolean('hasTitleField')->default(true);
            $table->string('titleTranslationMethod')->default(Field::TRANSLATION_METHOD_SITE);
            $table->text('titleTranslationKeyFormat')->nullable();
            $table->string('titleFormat')->nullable();
            $table->boolean('showSlugField')->default(true)->nullable();
            $table->string('slugTranslationMethod')->default(Field::TRANSLATION_METHOD_SITE);
            $table->text('slugTranslationKeyFormat')->nullable();
            $table->boolean('showStatusField')->default(true)->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->dateTime('dateDeleted')->nullable()->default(null);
            $table->char('uid', 36)->default('0');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::FIELDLAYOUTS), function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('type');
            $table->jsonb('config')->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->dateTime('dateDeleted')->nullable()->default(null);
            $table->char('uid', 36)->default('0');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::FIELDS), function (Blueprint $table) {
            $table->integer('id', true);
            $table->text('name');
            $table->string('handle', 64);
            $table->string('context')->default('global');
            $table->char('columnSuffix', 8)->nullable();
            $table->text('instructions')->nullable();
            $table->boolean('searchable')->default(true);
            $table->string('translationMethod')->default(Field::TRANSLATION_METHOD_NONE);
            $table->text('translationKeyFormat')->nullable();
            $table->string('type');
            $table->text('settings')->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->dateTime('dateDeleted')->nullable()->default(null);
            $table->char('uid', 36)->default('0');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::GLOBALSETS), function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name');
            $table->string('handle');
            $table->integer('fieldLayoutId')->nullable();
            $table->unsignedSmallInteger('sortOrder')->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::GQLTOKENS), function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name');
            $table->string('accessToken');
            $table->boolean('enabled')->default(true);
            $table->dateTime('expiryDate')->nullable();
            $table->dateTime('lastUsed')->nullable();
            $table->integer('schemaId')->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::GQLSCHEMAS), function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name');
            $table->jsonb('scope')->nullable();
            $table->boolean('isPublic')->default(false);
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::INFO), function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('version', 50);
            $table->string('schemaVersion', 15);
            $table->boolean('maintenance')->default(false);
            $table->char('configVersion', 12)->default('000000000000');
            $table->char('fieldVersion', 12)->default('000000000000');
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::MIGRATIONS), function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('track');
            $table->string('name');
            $table->dateTime('applyTime');
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::create('plugins', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('handle');
            $table->string('version');
            $table->string('schemaVersion');
            $table->dateTime('installDate');
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::PROJECTCONFIG), function (Blueprint $table) {
            $table->string('path')->primary();
            $table->text('value');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::QUEUE), function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('channel')->default('queue');
            $table->binary('job');
            $table->text('description')->nullable();
            $table->integer('timePushed');
            $table->integer('ttr');
            $table->integer('delay')->default(0);
            $table->unsignedInteger('priority')->default(1024);
            $table->dateTime('dateReserved')->nullable();
            $table->integer('timeUpdated')->nullable();
            $table->smallInteger('progress')->default(0);
            $table->string('progressLabel')->nullable();
            $table->integer('attempt')->nullable();
            $table->boolean('fail')->default(false)->nullable();
            $table->dateTime('dateFailed')->nullable();
            $table->text('error')->nullable();
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::RECOVERYCODES), function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('userId');
            $table->text('recoveryCodes')->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::RELATIONS), function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('fieldId');
            $table->integer('sourceId');
            $table->integer('sourceSiteId')->nullable();
            $table->integer('targetId');
            $table->unsignedSmallInteger('sortOrder')->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::SEARCHINDEXQUEUE), function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('elementId');
            $table->integer('siteId');
            $table->boolean('reserved')->default(false);
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::SEARCHINDEXQUEUE_FIELDS), function (Blueprint $table) {
            $table->integer('jobId');
            $table->string('fieldHandle');

            $table->primary(['jobId', 'fieldHandle']);
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::SECTIONS), function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('structureId')->nullable();
            $table->string('name');
            $table->string('handle');
            $table->enum('type', [
                Section::TYPE_SINGLE,
                Section::TYPE_CHANNEL,
                Section::TYPE_STRUCTURE,
            ])->default(Section::TYPE_CHANNEL);
            $table->boolean('enableVersioning')->default(false);
            $table->unsignedSmallInteger('maxAuthors')->nullable();
            $table->string('propagationMethod')->default(PropagationMethod::All->value);
            $table->enum('defaultPlacement', [
                Section::DEFAULT_PLACEMENT_BEGINNING,
                Section::DEFAULT_PLACEMENT_END,
            ])->default(Section::DEFAULT_PLACEMENT_END);
            $table->jsonb('previewTargets')->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->dateTime('dateDeleted')->nullable()->default(null);
            $table->char('uid', 36)->default('0');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::SECTIONS_ENTRYTYPES), function (Blueprint $table) {
            $table->integer('sectionId');
            $table->integer('typeId');
            $table->unsignedSmallInteger('sortOrder');
            $table->string('name')->nullable();
            $table->string('handle')->nullable();
            $table->text('description')->nullable();

            $table->primary(['sectionId', 'typeId']);
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::SECTIONS_SITES), function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('sectionId');
            $table->integer('siteId');
            $table->boolean('hasUrls')->default(true);
            $table->text('uriFormat')->nullable();
            $table->string('template', 500)->nullable();
            $table->boolean('enabledByDefault')->default(true);
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::SESSIONS), function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('userId');
            $table->char('token', 100);
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::SHUNNEDMESSAGES), function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('userId');
            $table->string('message');
            $table->dateTime('expiryDate')->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::SITES), function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('groupId');
            $table->boolean('primary');
            $table->string('enabled')->default('true');
            $table->string('name');
            $table->string('handle');
            $table->string('language');
            $table->boolean('hasUrls')->default(false);
            $table->string('baseUrl')->nullable();
            $table->unsignedSmallInteger('sortOrder')->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->dateTime('dateDeleted')->nullable()->default(null);
            $table->char('uid', 36)->default('0');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::SITEGROUPS), function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name');
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->dateTime('dateDeleted')->nullable()->default(null);
            $table->char('uid', 36)->default('0');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::SSO_IDENTITIES), function (Blueprint $table) {
            $table->string('provider');
            $table->string('identityId');
            $table->integer('userId');
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');

            $table->primary(['provider', 'identityId', 'userId']);
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::STRUCTUREELEMENTS), function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('structureId');
            $table->integer('elementId')->nullable();
            $table->unsignedInteger('root')->nullable();
            $table->integer('lft');
            $table->integer('rgt');
            $table->unsignedSmallInteger('level');
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::STRUCTURES), function (Blueprint $table) {
            $table->integer('id', true);
            $table->unsignedSmallInteger('maxLevels')->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->dateTime('dateDeleted')->nullable()->default(null);
            $table->char('uid', 36)->default('0');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::TAGGROUPS), function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name');
            $table->string('handle');
            $table->integer('fieldLayoutId')->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->dateTime('dateDeleted')->nullable()->default(null);
            $table->char('uid', 36)->default('0');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::TAGS), function (Blueprint $table) {
            $table->integer('id');
            $table->integer('groupId');
            $table->boolean('deletedWithGroup')->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::TOKENS), function (Blueprint $table) {
            $table->integer('id', true);
            $table->char('token', 32);
            $table->text('route')->nullable();
            $table->unsignedTinyInteger('usageLimit')->nullable();
            $table->unsignedTinyInteger('usageCount')->nullable();
            $table->dateTime('expiryDate');
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::USERGROUPS), function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name');
            $table->string('handle');
            $table->text('description')->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::USERGROUPS_USERS), function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('groupId');
            $table->integer('userId');
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::USERPERMISSIONS), function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name');
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::USERPERMISSIONS_USERGROUPS), function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('permissionId');
            $table->integer('groupId');
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::USERPERMISSIONS_USERS), function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('permissionId');
            $table->integer('userId');
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::USERPREFERENCES), function (Blueprint $table) {
            $table->integer('userId')->primary();
            $table->jsonb('preferences')->nullable();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->integer('id');
            $table->integer('photoId')->nullable();
            $table->integer('affiliatedSiteId')->nullable();
            $table->boolean('active')->default(false);
            $table->boolean('pending')->default(false);
            $table->boolean('locked')->default(false);
            $table->boolean('suspended')->default(false);
            $table->boolean('admin')->default(false);
            $table->string('username')->nullable();
            $table->string('fullName')->nullable();
            $table->string('firstName')->nullable();
            $table->string('lastName')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->dateTime('lastLoginDate')->nullable();
            $table->string('lastLoginAttemptIp', 45)->nullable();
            $table->dateTime('invalidLoginWindowStart')->nullable();
            $table->unsignedTinyInteger('invalidLoginCount')->nullable();
            $table->dateTime('lastInvalidLoginDate')->nullable();
            $table->dateTime('lockoutDate')->nullable();
            $table->boolean('hasDashboard')->default(false);
            $table->string('verificationCode')->nullable();
            $table->dateTime('verificationCodeIssuedDate')->nullable();
            $table->string('unverifiedEmail')->nullable();
            $table->boolean('passwordResetRequired')->default(false);
            $table->dateTime('lastPasswordChangeDate')->nullable();
            $table->rememberToken();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');

            $table->primary('id');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::VOLUMEFOLDERS), function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('parentId')->nullable();
            $table->integer('volumeId')->nullable();
            $table->string('name');
            $table->string('path')->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::VOLUMES), function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('fieldLayoutId')->nullable();
            $table->string('name');
            $table->string('handle');
            $table->string('fs');
            $table->string('subpath')->nullable();
            $table->string('transformFs')->nullable();
            $table->string('transformSubpath')->nullable();
            $table->string('titleTranslationMethod')->default(Field::TRANSLATION_METHOD_SITE);
            $table->text('titleTranslationKeyFormat')->nullable();
            $table->string('altTranslationMethod')->default(Field::TRANSLATION_METHOD_SITE);
            $table->text('altTranslationKeyFormat')->nullable();
            $table->unsignedSmallInteger('sortOrder')->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->dateTime('dateDeleted')->nullable()->default(null);
            $table->char('uid', 36)->default('0');
        });

        Schema::create(Table::withoutYiiPlaceholder(Table::WEBAUTHN), function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('userId');
            $table->string('credentialId')->default(null)->nullable();
            $table->text('credential')->nullable();
            $table->string('credentialName')->default(null)->nullable();
            $table->dateTime('dateLastUsed')->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::create('widgets', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('userId');
            $table->string('type');
            $table->unsignedSmallInteger('sortOrder')->nullable();
            $table->tinyInteger('colspan')->nullable();
            $table->jsonb('settings')->nullable();
            $table->boolean('enabled')->default(true);
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });
    }

    public function createIndexes(): void
    {
        $this->createIndex(Table::ANNOUNCEMENTS, ['userId', 'unread', 'dateRead', 'dateCreated']);
        $this->createIndex(Table::ANNOUNCEMENTS, ['dateRead']);
        $this->createIndex(Table::ASSETINDEXDATA, ['sessionId', 'volumeId']);
        $this->createIndex(Table::ASSETINDEXDATA, ['volumeId']);
        $this->createIndex(Table::ASSETS, ['filename', 'folderId']);
        $this->createIndex(Table::ASSETS, ['folderId']);
        $this->createIndex(Table::ASSETS, ['volumeId']);
        $this->createIndex(Table::BULKOPEVENTS, ['timestamp']);
        $this->createIndex(Table::CATEGORIES, ['groupId']);
        $this->createIndex(Table::CATEGORYGROUPS, ['name']);
        $this->createIndex(Table::CATEGORYGROUPS, ['handle']);
        $this->createIndex(Table::CATEGORYGROUPS, ['structureId']);
        $this->createIndex(Table::CATEGORYGROUPS, ['fieldLayoutId']);
        $this->createIndex(Table::CATEGORYGROUPS, ['dateDeleted']);
        $this->createIndex(Table::CATEGORYGROUPS_SITES, ['groupId', 'siteId'], unique: true);
        $this->createIndex(Table::CATEGORYGROUPS_SITES, ['siteId']);
        $this->createIndex(Table::CHANGEDATTRIBUTES, ['elementId', 'siteId', 'dateUpdated']);
        $this->createIndex(Table::CHANGEDFIELDS, ['elementId', 'siteId', 'dateUpdated']);
        $this->createIndex(Table::CONTENTBLOCKS, ['primaryOwnerId']);
        $this->createIndex(Table::CONTENTBLOCKS, ['fieldId']);
        $this->createIndex(Table::DEPRECATIONERRORS, ['key', 'fingerprint'], unique: true);
        $this->createIndex(Table::DRAFTS, ['creatorId', 'provisional']);
        $this->createIndex(Table::DRAFTS, ['saved']);
        $this->createIndex(Table::ELEMENTACTIVITY, ['elementId', 'timestamp', 'userId']);
        $this->createIndex(Table::ELEMENTS, ['dateDeleted']);
        $this->createIndex(Table::ELEMENTS, ['fieldLayoutId']);
        $this->createIndex(Table::ELEMENTS, ['type']);
        $this->createIndex(Table::ELEMENTS, ['enabled']);
        $this->createIndex(Table::ELEMENTS, ['canonicalId']);
        $this->createIndex(Table::ELEMENTS, ['archived', 'dateCreated']);
        $this->createIndex(Table::ELEMENTS, ['archived', 'dateDeleted', 'draftId', 'revisionId', 'canonicalId']);
        $this->createIndex(Table::ELEMENTS, ['archived', 'dateDeleted', 'draftId', 'revisionId', 'canonicalId', 'enabled']);
        $this->createIndex(Table::ELEMENTS_BULKOPS, ['timestamp']);
        $this->createIndex(Table::ELEMENTS_SITES, ['elementId', 'siteId'], unique: true);
        $this->createIndex(Table::ELEMENTS_SITES, ['siteId']);
        $this->createIndex(Table::ELEMENTS_SITES, ['title', 'siteId']);
        $this->createIndex(Table::ELEMENTS_SITES, ['slug', 'siteId']);
        $this->createIndex(Table::ELEMENTS_SITES, ['enabled']);
        $this->createIndex(Table::SYSTEMMESSAGES, ['key', 'language'], unique: true);
        $this->createIndex(Table::SYSTEMMESSAGES, ['language']);
        $this->createIndex(Table::ENTRIES, ['postDate']);
        $this->createIndex(Table::ENTRIES, ['expiryDate']);
        $this->createIndex(Table::ENTRIES, ['status']);
        $this->createIndex(Table::ENTRIES, ['sectionId']);
        $this->createIndex(Table::ENTRIES, ['typeId']);
        $this->createIndex(Table::ENTRIES_AUTHORS, ['authorId']);
        $this->createIndex(Table::ENTRIES_AUTHORS, ['entryId', 'sortOrder']);
        $this->createIndex(Table::ENTRIES, ['primaryOwnerId']);
        $this->createIndex(Table::ENTRIES, ['fieldId']);
        $this->createIndex(Table::ENTRYTYPES, ['fieldLayoutId']);
        $this->createIndex(Table::ENTRYTYPES, ['dateDeleted']);
        $this->createIndex(Table::FIELDLAYOUTS, ['dateDeleted']);
        $this->createIndex(Table::FIELDLAYOUTS, ['type']);
        $this->createIndex(Table::FIELDS, ['handle', 'context']);
        $this->createIndex(Table::FIELDS, ['context']);
        $this->createIndex(Table::FIELDS, ['dateDeleted']);
        $this->createIndex(Table::GLOBALSETS, ['name']);
        $this->createIndex(Table::GLOBALSETS, ['handle']);
        $this->createIndex(Table::GLOBALSETS, ['fieldLayoutId']);
        $this->createIndex(Table::GLOBALSETS, ['sortOrder']);
        $this->createIndex(Table::GQLTOKENS, ['accessToken'], unique: true);
        $this->createIndex(Table::GQLTOKENS, ['name'], unique: true);
        $this->createIndex(Table::IMAGETRANSFORMINDEX, ['assetId', 'transformString']);
        $this->createIndex(Table::IMAGETRANSFORMS, ['name']);
        $this->createIndex(Table::IMAGETRANSFORMS, ['handle']);
        $this->createIndex(Table::MIGRATIONS, ['track', 'name'], unique: true);
        $this->createIndex(Table::PLUGINS, ['handle'], unique: true);
        $this->createIndex(Table::QUEUE, ['channel', 'fail', 'timeUpdated', 'timePushed']);
        $this->createIndex(Table::QUEUE, ['channel', 'fail', 'timeUpdated', 'delay']);
        $this->createIndex(Table::RELATIONS, ['fieldId', 'sourceId', 'sourceSiteId', 'targetId'], unique: true);
        $this->createIndex(Table::RELATIONS, ['sourceId']);
        $this->createIndex(Table::RELATIONS, ['targetId']);
        $this->createIndex(Table::RELATIONS, ['sourceSiteId']);
        $this->createIndex(Table::REVISIONS, ['canonicalId', 'num'], unique: true);
        $this->createIndex(Table::SEARCHINDEXQUEUE, ['elementId', 'siteId', 'reserved']);
        $this->createIndex(Table::SEARCHINDEXQUEUE_FIELDS, ['jobId', 'fieldHandle'], unique: true);
        $this->createIndex(Table::SECTIONS, ['handle']);
        $this->createIndex(Table::SECTIONS, ['name']);
        $this->createIndex(Table::SECTIONS, ['structureId']);
        $this->createIndex(Table::SECTIONS, ['dateDeleted']);
        $this->createIndex(Table::SECTIONS_SITES, ['sectionId', 'siteId'], unique: true);
        $this->createIndex(Table::SECTIONS_SITES, ['siteId']);
        $this->createIndex(Table::SESSIONS, ['uid']);
        $this->createIndex(Table::SESSIONS, ['token']);
        $this->createIndex(Table::SESSIONS, ['dateUpdated']);
        $this->createIndex(Table::SESSIONS, ['userId']);
        $this->createIndex(Table::SHUNNEDMESSAGES, ['userId', 'message'], unique: true);
        $this->createIndex(Table::SITES, ['dateDeleted']);
        $this->createIndex(Table::SITES, ['handle']);
        $this->createIndex(Table::SITES, ['sortOrder']);
        $this->createIndex(Table::SITEGROUPS, ['name']);
        $this->createIndex(Table::STRUCTUREELEMENTS, ['structureId', 'elementId'], unique: true);
        $this->createIndex(Table::STRUCTUREELEMENTS, ['root']);
        $this->createIndex(Table::STRUCTUREELEMENTS, ['lft']);
        $this->createIndex(Table::STRUCTUREELEMENTS, ['rgt']);
        $this->createIndex(Table::STRUCTUREELEMENTS, ['level']);
        $this->createIndex(Table::STRUCTUREELEMENTS, ['elementId']);
        $this->createIndex(Table::STRUCTURES, ['dateDeleted']);
        $this->createIndex(Table::TAGGROUPS, ['name']);
        $this->createIndex(Table::TAGGROUPS, ['handle']);
        $this->createIndex(Table::TAGGROUPS, ['dateDeleted']);
        $this->createIndex(Table::TAGS, ['groupId']);
        $this->createIndex(Table::TOKENS, ['token'], unique: true);
        $this->createIndex(Table::TOKENS, ['expiryDate']);
        $this->createIndex(Table::USERGROUPS, ['handle']);
        $this->createIndex(Table::USERGROUPS, ['name']);
        $this->createIndex(Table::USERGROUPS_USERS, ['groupId', 'userId'], unique: true);
        $this->createIndex(Table::USERGROUPS_USERS, ['userId']);
        $this->createIndex(Table::USERPERMISSIONS, ['name'], unique: true);
        $this->createIndex(Table::USERPERMISSIONS_USERGROUPS, ['permissionId', 'groupId'], unique: true);
        $this->createIndex(Table::USERPERMISSIONS_USERGROUPS, ['groupId']);
        $this->createIndex(Table::USERPERMISSIONS_USERS, ['permissionId', 'userId'], unique: true);
        $this->createIndex(Table::USERPERMISSIONS_USERS, ['userId']);
        $this->createIndex(Table::USERS, ['active']);
        $this->createIndex(Table::USERS, ['locked']);
        $this->createIndex(Table::USERS, ['pending']);
        $this->createIndex(Table::USERS, ['suspended']);
        $this->createIndex(Table::USERS, ['verificationCode']);
        $this->createIndex(Table::VOLUMEFOLDERS, ['name', 'parentId', 'volumeId'], unique: true);
        $this->createIndex(Table::VOLUMEFOLDERS, ['parentId']);
        $this->createIndex(Table::VOLUMEFOLDERS, ['volumeId']);
        $this->createIndex(Table::VOLUMES, ['name']);
        $this->createIndex(Table::VOLUMES, ['handle']);
        $this->createIndex(Table::VOLUMES, ['fieldLayoutId']);
        $this->createIndex(Table::VOLUMES, ['dateDeleted']);
        $this->createIndex(Table::WIDGETS, ['userId']);

        Schema::create(Table::withoutYiiPlaceholder(Table::SEARCHINDEX), function (Blueprint $table) {
            $table->integer('elementId');
            $table->string('attribute', 25);
            $table->integer('fieldId');
            $table->integer('siteId');
            $table->text('keywords');

            $table->primary(['elementId', 'attribute', 'fieldId', 'siteId']);
        });

        if (Craft::$app->getDb()->getIsMysql()) {
            $this->createIndex(Table::ELEMENTS_SITES, ['uri', 'siteId']);
            $this->createIndex(Table::USERS, ['email']);
            $this->createIndex(Table::USERS, ['username']);

            Schema::table(Table::withoutYiiPlaceholder(Table::SEARCHINDEX), function (Blueprint $table) {
                $table->fullText('keywords');
            });
        } else {
            // Postgres is case-sensitive
            DB::statement('CREATE INDEX sites_uri_siteid_index ON '.Craft::$app->getDb()->tablePrefix.Table::withoutYiiPlaceholder(Table::ELEMENTS_SITES).' (lower(uri), "siteId")');
            DB::statement('CREATE INDEX users_email_index ON '.Craft::$app->getDb()->tablePrefix.Table::withoutYiiPlaceholder(Table::USERS).' (lower(email))');
            DB::statement('CREATE INDEX users_username_index ON '.Craft::$app->getDb()->tablePrefix.Table::withoutYiiPlaceholder(Table::USERS).' (lower(username))');

            Schema::table(Table::withoutYiiPlaceholder(Table::SEARCHINDEX), function (Blueprint $table) {
                $table->rawColumn('keywords_vector', 'tsvector');
            });

            DB::statement('CREATE INDEX keywords_gin ON '.Craft::$app->getDb()->tablePrefix.Table::withoutYiiPlaceholder(Table::SEARCHINDEX).' USING GIN(keywords_vector) WITH (FASTUPDATE=YES)');
            DB::statement('CREATE INDEX keywords_index ON '.Craft::$app->getDb()->tablePrefix.Table::withoutYiiPlaceholder(Table::SEARCHINDEX).' USING btree(keywords)');
        }
    }

    public function addForeignKeys(): void
    {
        $this->addForeignKey(Table::ADDRESSES, ['id'], Table::ELEMENTS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::ADDRESSES, ['primaryOwnerId'], Table::ELEMENTS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::ANNOUNCEMENTS, ['userId'], Table::USERS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::ANNOUNCEMENTS, ['pluginId'], Table::PLUGINS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::ASSETINDEXDATA, ['volumeId'], Table::VOLUMES, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::ASSETINDEXDATA, ['sessionId'], Table::ASSETINDEXINGSESSIONS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::ASSETS, ['folderId'], Table::VOLUMEFOLDERS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::ASSETS, ['id'], Table::ELEMENTS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::ASSETS, ['uploaderId'], Table::USERS, ['id'], onDelete: 'SET NULL');
        $this->addForeignKey(Table::ASSETS, ['volumeId'], Table::VOLUMES, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::ASSETS_SITES, ['assetId'], Table::ASSETS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::ASSETS_SITES, ['siteId'], Table::SITES, ['id'], onDelete: 'CASCADE', onUpdate: 'CASCADE');
        $this->addForeignKey(Table::AUTHENTICATOR, ['userId'], Table::USERS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::CATEGORIES, ['groupId'], Table::CATEGORYGROUPS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::CATEGORIES, ['id'], Table::ELEMENTS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::CATEGORIES, ['parentId'], Table::CATEGORIES, ['id'], onDelete: 'SET NULL');
        $this->addForeignKey(Table::CATEGORYGROUPS, ['fieldLayoutId'], Table::FIELDLAYOUTS, ['id'], onDelete: 'SET NULL');
        $this->addForeignKey(Table::CATEGORYGROUPS, ['structureId'], Table::STRUCTURES, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::CATEGORYGROUPS_SITES, ['groupId'], Table::CATEGORYGROUPS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::CATEGORYGROUPS_SITES, ['siteId'], Table::SITES, ['id'], onDelete: 'CASCADE', onUpdate: 'CASCADE');
        $this->addForeignKey(Table::CHANGEDATTRIBUTES, ['elementId'], Table::ELEMENTS, ['id'], onDelete: 'CASCADE', onUpdate: 'CASCADE');
        $this->addForeignKey(Table::CHANGEDATTRIBUTES, ['siteId'], Table::SITES, ['id'], onDelete: 'CASCADE', onUpdate: 'CASCADE');
        $this->addForeignKey(Table::CHANGEDATTRIBUTES, ['userId'], Table::USERS, ['id'], onDelete: 'SET NULL', onUpdate: 'CASCADE');
        $this->addForeignKey(Table::CHANGEDFIELDS, ['elementId'], Table::ELEMENTS, ['id'], onDelete: 'CASCADE', onUpdate: 'CASCADE');
        $this->addForeignKey(Table::CONTENTBLOCKS, ['id'], Table::ELEMENTS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::CONTENTBLOCKS, ['fieldId'], Table::FIELDS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::CONTENTBLOCKS, ['primaryOwnerId'], Table::ELEMENTS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::CHANGEDFIELDS, ['siteId'], Table::SITES, ['id'], onDelete: 'CASCADE', onUpdate: 'CASCADE');
        $this->addForeignKey(Table::CHANGEDFIELDS, ['fieldId'], Table::FIELDS, ['id'], onDelete: 'CASCADE', onUpdate: 'CASCADE');
        $this->addForeignKey(Table::CHANGEDFIELDS, ['userId'], Table::USERS, ['id'], onDelete: 'SET NULL', onUpdate: 'CASCADE');
        $this->addForeignKey(Table::CRAFTIDTOKENS, ['userId'], Table::USERS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::DRAFTS, ['creatorId'], Table::USERS, ['id'], onDelete: 'SET NULL');
        $this->addForeignKey(Table::DRAFTS, ['canonicalId'], Table::ELEMENTS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::ELEMENTACTIVITY, ['elementId'], Table::ELEMENTS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::ELEMENTACTIVITY, ['userId'], Table::USERS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::ELEMENTACTIVITY, ['siteId'], Table::SITES, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::ELEMENTACTIVITY, ['draftId'], Table::DRAFTS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::ELEMENTS, ['canonicalId'], Table::ELEMENTS, ['id'], onDelete: 'SET NULL');
        $this->addForeignKey(Table::ELEMENTS, ['draftId'], Table::DRAFTS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::ELEMENTS, ['revisionId'], Table::REVISIONS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::ELEMENTS, ['fieldLayoutId'], Table::FIELDLAYOUTS, ['id'], onDelete: 'SET NULL');
        $this->addForeignKey(Table::ELEMENTS_OWNERS, ['elementId'], Table::ELEMENTS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::ELEMENTS_OWNERS, ['ownerId'], Table::ELEMENTS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::ELEMENTS_SITES, ['elementId'], Table::ELEMENTS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::ELEMENTS_SITES, ['siteId'], Table::SITES, ['id'], onDelete: 'CASCADE', onUpdate: 'CASCADE');
        $this->addForeignKey(Table::ENTRIES, ['id'], Table::ELEMENTS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::ENTRIES, ['sectionId'], Table::SECTIONS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::ENTRIES, ['parentId'], Table::ENTRIES, ['id'], onDelete: 'SET NULL');
        $this->addForeignKey(Table::ENTRIES, ['typeId'], Table::ENTRYTYPES, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::ENTRIES_AUTHORS, ['entryId'], Table::ENTRIES, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::ENTRIES_AUTHORS, ['authorId'], Table::USERS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::ENTRIES, ['fieldId'], Table::FIELDS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::ENTRIES, ['primaryOwnerId'], Table::ELEMENTS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::ENTRYTYPES, ['fieldLayoutId'], Table::FIELDLAYOUTS, ['id'], onDelete: 'SET NULL');
        $this->addForeignKey(Table::GLOBALSETS, ['fieldLayoutId'], Table::FIELDLAYOUTS, ['id'], onDelete: 'SET NULL');
        $this->addForeignKey(Table::GLOBALSETS, ['id'], Table::ELEMENTS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::GQLTOKENS, 'schemaId', Table::GQLSCHEMAS, 'id', onDelete: 'SET NULL');
        $this->addForeignKey(Table::RELATIONS, ['fieldId'], Table::FIELDS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::RELATIONS, ['sourceId'], Table::ELEMENTS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::RELATIONS, ['sourceSiteId'], Table::SITES, ['id'], onDelete: 'CASCADE', onUpdate: 'CASCADE');
        $this->addForeignKey(Table::REVISIONS, ['creatorId'], Table::USERS, ['id'], onDelete: 'SET NULL');
        $this->addForeignKey(Table::REVISIONS, ['canonicalId'], Table::ELEMENTS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::SEARCHINDEXQUEUE, 'elementId', Table::ELEMENTS, 'id', onDelete: 'CASCADE');
        $this->addForeignKey(Table::SEARCHINDEXQUEUE_FIELDS, 'jobId', Table::SEARCHINDEXQUEUE, 'id', onDelete: 'CASCADE');
        $this->addForeignKey(Table::SECTIONS, ['structureId'], Table::STRUCTURES, ['id'], onDelete: 'SET NULL');
        $this->addForeignKey(Table::SECTIONS_ENTRYTYPES, ['sectionId'], Table::SECTIONS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::SECTIONS_ENTRYTYPES, ['typeId'], Table::ENTRYTYPES, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::SECTIONS_SITES, ['siteId'], Table::SITES, ['id'], onDelete: 'CASCADE', onUpdate: 'CASCADE');
        $this->addForeignKey(Table::SECTIONS_SITES, ['sectionId'], Table::SECTIONS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::SESSIONS, ['userId'], Table::USERS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::SHUNNEDMESSAGES, ['userId'], Table::USERS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::SITES, ['groupId'], Table::SITEGROUPS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::SSO_IDENTITIES, ['userId'], Table::USERS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::STRUCTUREELEMENTS, ['structureId'], Table::STRUCTURES, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::TAGGROUPS, ['fieldLayoutId'], Table::FIELDLAYOUTS, ['id'], onDelete: 'SET NULL');
        $this->addForeignKey(Table::TAGS, ['groupId'], Table::TAGGROUPS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::TAGS, ['id'], Table::ELEMENTS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::USERGROUPS_USERS, ['groupId'], Table::USERGROUPS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::USERGROUPS_USERS, ['userId'], Table::USERS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::USERPERMISSIONS_USERGROUPS, ['groupId'], Table::USERGROUPS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::USERPERMISSIONS_USERGROUPS, ['permissionId'], Table::USERPERMISSIONS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::USERPERMISSIONS_USERS, ['permissionId'], Table::USERPERMISSIONS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::USERPERMISSIONS_USERS, ['userId'], Table::USERS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::USERPREFERENCES, ['userId'], Table::USERS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::USERS, ['id'], Table::ELEMENTS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::USERS, ['photoId'], Table::ASSETS, ['id'], onDelete: 'SET NULL');
        $this->addForeignKey(Table::USERS, ['affiliatedSiteId'], Table::SITES, ['id'], onDelete: 'SET NULL');
        $this->addForeignKey(Table::VOLUMEFOLDERS, ['parentId'], Table::VOLUMEFOLDERS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::VOLUMEFOLDERS, ['volumeId'], Table::VOLUMES, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::VOLUMES, ['fieldLayoutId'], Table::FIELDLAYOUTS, ['id'], onDelete: 'SET NULL');
        $this->addForeignKey(Table::WEBAUTHN, ['userId'], Table::USERS, ['id'], onDelete: 'CASCADE');
        $this->addForeignKey(Table::WIDGETS, ['userId'], Table::USERS, ['id'], onDelete: 'CASCADE');
    }

    public function insertDefaultData(): void
    {
        $this->output->writeln('    > populating the info table ... ');
        Craft::$app->saveInfo(new Info([
            'version' => Craft::$app->getVersion(),
            'schemaVersion' => Craft::$app->schemaVersion,
            'maintenance' => false,
            'configVersion' => Str::random(12),
            'fieldVersion' => Str::random(12),
        ]));
        $this->output->writeln('done');

        $generalConfig = app(GeneralConfig::class);
        $projectConfig = Craft::$app->getProjectConfig();

        if ($this->applyProjectConfigYaml) {
            // Make sure at least sites are processed
            ProjectConfigHelper::ensureAllSitesProcessed(true);
            $this->_installPlugins();
            // Save the existing system settings
            $this->output->writeln('    > applying the project config ... ');
            $projectConfig->applyExternalChanges();
            $this->output->writeln('done');
        } else {
            // Save the default system settings
            $this->output->writeln('    > saving default data ... ');
            $configData = $this->_generateInitialConfig();
            $projectConfig->applyConfigChanges($configData);
            $this->output->writeln('done');
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

        Craft::$app->language = $this->site->language;

        $this->output->writeln('    > saving the first user ... ');
        $user = new User([
            'active' => true,
            'admin' => true,
            'username' => $this->username,
            'newPassword' => $this->password,
            'email' => $this->email,
        ]);
        Craft::$app->getElements()->saveElement($user);
        $this->output->writeln('done');

        Craft::$app->getUsers()->saveUserPreferences($user, [
            'language' => $this->site->language,
        ]);

        if (! Craft::$app->getRequest()->getIsConsoleRequest()) {
            Craft::$app->getUser()->login($user, $generalConfig->userSessionDuration);
        }
    }

    private function _validateProjectConfig(?string &$error = null): bool
    {
        if (! $this->applyProjectConfigYaml) {
            return true;
        }

        $projectConfig = Craft::$app->getProjectConfig();
        if (! $projectConfig->getDoesExternalConfigExist()) {
            $this->applyProjectConfigYaml = false;

            return true;
        }

        $expectedSchemaVersion = (string) $projectConfig->get(ProjectConfig::PATH_SCHEMA_VERSION, true);
        $craftSchemaVersion = Craft::$app->schemaVersion;

        if (! version_compare($craftSchemaVersion, $expectedSchemaVersion, '=')) {
            $error = "Craft CMS is Composer-installed with schema version $craftSchemaVersion, but project.yaml expects $expectedSchemaVersion.";

            return false;
        }

        $pluginsService = Craft::$app->getPlugins();
        $pluginConfigs = $projectConfig->get(ProjectConfig::PATH_PLUGINS, true) ?? [];

        /**
         * Make sure that all to-be-installed plugins actually exist
         * and that they have the same schema as project.yaml
         */
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
                $this->output->writeln("    > installing $handle ... ");
                $pluginsService->installPlugin($handle);
                $this->output->writeln('done');
            }
        } finally {
            // Put the real response back
            Craft::$app->set('response', $realResponse);
        }
    }

    private function _generateInitialConfig(): array
    {
        $siteGroupUid = Str::uuid()->toString();

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
                Str::uuid()->toString() => [
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
                'edition' => Edition::Solo->handle(),
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

    public function down(): bool
    {
        $this->output->writeln('Install migration cannot be reverted.');

        return false;
    }
}
