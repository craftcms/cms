<?php

declare(strict_types=1);

/** @noinspection RepetitiveMethodCallsInspection */

namespace CraftCms\Cms\Database\Migrations;

use Craft;
use craft\web\Response;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Migration;
use CraftCms\Cms\Database\Migrations\Event\PostCreateTables;
use CraftCms\Cms\Database\Migrator;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\Plugin\Exceptions\InvalidPluginException;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\ProjectConfig\ProjectConfigHelper;
use CraftCms\Cms\Section\Enums\DefaultPlacement;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Shared\Exceptions\OperationAbortedException;
use CraftCms\Cms\Shared\Models\Info;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Throwable;
use Tpetry\QueryExpressions\Function\String\Lower;

class Install extends Migration
{
    public function __construct(
        public ?string $username = null,
        public ?string $password = null,
        public ?string $email = null,
        public ?Site $site = null,
        public bool $applyProjectConfigYaml = true
    ) {
        parent::__construct();
    }

    public function up(): void
    {
        if (! $this->_validateProjectConfig($error)) {
            $message = "Project config validation failed: $error Run `composer install` or remove your `config/project/` folder and try again.";
            $this->output->writeln($message);
            $this->output->writeln('');
            $this->output->writeln('Aborting install.');
            throw new OperationAbortedException($message);
        }

        $this->components->task('Creating tables', fn () => $this->createTables());
        $this->components->task('Creating indexes', fn () => $this->createIndexes());
        $this->components->task('Adding foreign keys', fn () => $this->addForeignKeys());

        event(new PostCreateTables);

        DB::afterCommit(function () {
            try {
                $this->insertDefaultData();
            } catch (Throwable $e) {
                $this->components->error("Error inserting default data: {$e->getMessage()}");
                $this->getOutput()->info($e->getTraceAsString());
            }
        });

        $this->newLine();
    }

    /**
     * Creates the tables.
     */
    public function createTables(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
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

        Schema::create('assetindexdata', function (Blueprint $table) {
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

        Schema::create('assetindexingsessions', function (Blueprint $table) {
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

        Schema::create('assets', function (Blueprint $table) {
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

        Schema::create('assets_sites', function (Blueprint $table) {
            $table->integer('assetId');
            $table->integer('siteId');
            $table->text('alt')->nullable();
            $table->primary(['assetId', 'siteId']);
        });

        Schema::create('imagetransformindex', function (Blueprint $table) {
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

        Schema::create('imagetransforms', function (Blueprint $table) {
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

        Schema::create('authenticator', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('userId');
            $table->string('auth2faSecret')->default(null)->nullable();
            $table->unsignedInteger('oldTimestamp')->default(null)->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
        });

        Schema::create('bulkopevents', function (Blueprint $table) {
            $table->char('key', 10);
            $table->string('senderClass');
            $table->string('eventName');
            $table->dateTime('timestamp');
            $table->primary(['key', 'senderClass', 'eventName']);
        });

        Schema::create('changedattributes', function (Blueprint $table) {
            $table->integer('elementId');
            $table->integer('siteId');
            $table->string('attribute');
            $table->dateTime('dateUpdated');
            $table->boolean('propagated');
            $table->integer('userId')->nullable();
            $table->primary(['elementId', 'siteId', 'attribute']);
        });

        Schema::create('changedfields', function (Blueprint $table) {
            $table->integer('elementId');
            $table->integer('siteId');
            $table->integer('fieldId');
            $table->char('layoutElementUid', 36)->default('0');
            $table->dateTime('dateUpdated');
            $table->boolean('propagated');
            $table->integer('userId')->nullable();
            $table->primary(['elementId', 'siteId', 'fieldId', 'layoutElementUid']);
        });

        Schema::create('contentblocks', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('primaryOwnerId')->nullable();
            $table->integer('fieldId')->nullable();
        });

        /** @todo change back to Table::DEPRECATIONERRORS once larastan is updated */
        Schema::create('deprecationerrors', function (Blueprint $table) {
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

        Schema::create('drafts', function (Blueprint $table) {
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

        Schema::create('elementactivity', function (Blueprint $table) {
            $table->integer('elementId');
            $table->integer('userId');
            $table->integer('siteId');
            $table->integer('draftId')->nullable();
            $table->string('type');
            $table->dateTime('timestamp')->nullable();
            $table->primary(['elementId', 'userId', 'type']);
        });

        Schema::create('elements', function (Blueprint $table) {
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

        Schema::create('elements_bulkops', function (Blueprint $table) {
            $table->integer('elementId');
            $table->char('key', 10);
            $table->dateTime('timestamp');
            $table->primary(['elementId', 'key']);
        });

        Schema::create('elements_owners', function (Blueprint $table) {
            $table->integer('elementId');
            $table->integer('ownerId');
            $table->unsignedSmallInteger('sortOrder');
            $table->primary(['elementId', 'ownerId']);
        });

        Schema::create('elements_sites', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('elementId');
            $table->integer('siteId');
            $table->string('title')->nullable();
            $table->string('slug')->nullable();
            $table->string('uri')->nullable();
            $table->string('uriLower')
                ->nullable()
                ->storedAs(new Lower('uri'))
                ->invisible();
            $table->jsonb('content')->nullable();
            $table->boolean('enabled')->default(true);
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::create('resourcepaths', function (Blueprint $table) {
            $table->string('hash');
            $table->string('path');
            $table->primary('hash');
        });

        Schema::create('revisions', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('canonicalId');
            $table->integer('creatorId')->nullable();
            $table->integer('num');
            $table->text('notes')->nullable();
        });

        Schema::create('sequences', function (Blueprint $table) {
            $table->string('name');
            $table->unsignedInteger('next')->default(1);
            $table->primary('name');
        });

        /** @todo Change when Larastan is updated */
        Schema::create('systemmessages', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('language');
            $table->string('key');
            $table->text('subject');
            $table->text('body');
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::create('entries', function (Blueprint $table) {
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

        Schema::create('entries_authors', function (Blueprint $table) {
            $table->integer('entryId');
            $table->integer('authorId');
            $table->unsignedSmallInteger('sortOrder');
            $table->primary(['entryId', 'authorId']);
        });

        Schema::create('entrytypes', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('fieldLayoutId')->nullable();
            $table->string('name');
            $table->string('handle');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->string('uiLabelFormat')->default('{title}');
            $table->boolean('hasTitleField')->default(true);
            $table->string('titleTranslationMethod')->default(Field::TRANSLATION_METHOD_SITE);
            $table->text('titleTranslationKeyFormat')->nullable();
            $table->string('titleFormat')->nullable();
            $table->boolean('allowLineBreaksInTitles')->default(false);
            $table->boolean('showSlugField')->default(true)->nullable();
            $table->string('slugTranslationMethod')->default(Field::TRANSLATION_METHOD_SITE);
            $table->text('slugTranslationKeyFormat')->nullable();
            $table->boolean('showStatusField')->default(true)->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->dateTime('dateDeleted')->nullable()->default(null);
            $table->char('uid', 36)->default('0');
        });

        Schema::create('fieldlayouts', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('type');
            $table->jsonb('config')->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->dateTime('dateDeleted')->nullable()->default(null);
            $table->char('uid', 36)->default('0');
        });

        Schema::create('fields', function (Blueprint $table) {
            $table->integer('id', true);
            $table->text('name');
            $table->string('handle', 64);
            $table->string('context')->default('global');
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

        Schema::create('gqltokens', function (Blueprint $table) {
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

        Schema::create('gqlschemas', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name');
            $table->jsonb('scope')->nullable();
            $table->boolean('isPublic')->default(false);
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        /** @todo Change when Larastan is updated */
        Schema::create('info', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('version', 50);
            $table->string('schemaVersion', 15);
            $table->char('configVersion', 12)->default('000000000000');
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::dropIfExists(Table::MIGRATIONS);
        app(Migrator::class)
            ->getRepository()
            ->createRepository();

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

        Schema::create('projectconfig', function (Blueprint $table) {
            $table->string('path')->primary();
            $table->text('value');
        });

        Schema::create('jobprogress', function (Blueprint $table) {
            $table->string('uid')->primary();
            $table->string('description')->nullable();
            $table->unsignedTinyInteger('status')->default(1); // JobStatus::Pending
            $table->unsignedTinyInteger('progress')->default(0);
            $table->unsignedInteger('delay')->nullable();
            $table->string('progressLabel')->nullable();
            $table->text('error')->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
        });

        Schema::create('recoverycodes', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('userId');
            $table->text('recoveryCodes')->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
        });

        Schema::create('relations', function (Blueprint $table) {
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

        Schema::create('routetokens', function (Blueprint $table) {
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

        Schema::create('searchindexqueue', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('elementId');
            $table->integer('siteId');
            $table->boolean('reserved')->default(false);
        });

        Schema::create('searchindexqueue_fields', function (Blueprint $table) {
            $table->integer('jobId');
            $table->string('fieldHandle');

            $table->primary(['jobId', 'fieldHandle']);
        });

        Schema::create('sections', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('structureId')->nullable();
            $table->string('name');
            $table->string('handle');
            $table->enum('type', [
                SectionType::Single->value,
                SectionType::Channel->value,
                SectionType::Structure->value,
            ])->default(SectionType::Channel->value);
            $table->boolean('enableVersioning')->default(false);
            $table->unsignedSmallInteger('maxAuthors')->nullable();
            $table->string('propagationMethod')->default(PropagationMethod::All->value);
            $table->enum('defaultPlacement', [
                DefaultPlacement::Beginning->value,
                DefaultPlacement::End->value,
            ])->default(DefaultPlacement::End->value);
            $table->jsonb('previewTargets')->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->dateTime('dateDeleted')->nullable()->default(null);
            $table->char('uid', 36)->default('0');
        });

        Schema::create('sections_entrytypes', function (Blueprint $table) {
            $table->integer('sectionId');
            $table->integer('typeId');
            $table->unsignedSmallInteger('sortOrder');
            $table->string('name')->nullable();
            $table->string('handle')->nullable();
            $table->text('description')->nullable();

            $table->primary(['sectionId', 'typeId']);
        });

        Schema::create('sections_sites', function (Blueprint $table) {
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

        Schema::create('shunnedmessages', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('userId');
            $table->string('message');
            $table->dateTime('expiryDate')->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::create('sites', function (Blueprint $table) {
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

        Schema::create('sitegroups', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name');
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->dateTime('dateDeleted')->nullable()->default(null);
            $table->char('uid', 36)->default('0');
        });

        Schema::create('sso_identities', function (Blueprint $table) {
            $table->string('provider');
            $table->string('identityId');
            $table->integer('userId');
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');

            $table->primary(['provider', 'identityId', 'userId']);
        });

        Schema::create('structureelements', function (Blueprint $table) {
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

        Schema::create('structures', function (Blueprint $table) {
            $table->integer('id', true);
            $table->unsignedSmallInteger('maxLevels')->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->dateTime('dateDeleted')->nullable()->default(null);
            $table->char('uid', 36)->default('0');
        });

        Schema::create('usergroups', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name');
            $table->string('handle');
            $table->text('description')->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::create('usergroups_users', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('groupId');
            $table->integer('userId');
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::create('userpermissions', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name');
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::create('userpermissions_usergroups', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('permissionId');
            $table->integer('groupId');
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::create('userpermissions_users', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('permissionId');
            $table->integer('userId');
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::create('userpreferences', function (Blueprint $table) {
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
            $table->string('unverifiedEmail')->nullable();
            $table->boolean('passwordResetRequired')->default(false);
            $table->dateTime('lastPasswordChangeDate')->nullable();
            $table->string('rememberToken', 100)->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');

            $table->primary('id');
        });

        Schema::create('volumefolders', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('parentId')->nullable();
            $table->integer('volumeId')->nullable();
            $table->string('name');
            $table->string('path')->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::create('volumes', function (Blueprint $table) {
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

        Schema::create(Table::PASSWORD_RESET_TOKENS, function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('webauthn', function (Blueprint $table) {
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
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });
    }

    public function createIndexes(): void
    {
        Schema::createIndex(Table::ANNOUNCEMENTS, ['userId', 'unread', 'dateRead', 'dateCreated']);
        Schema::createIndex(Table::ANNOUNCEMENTS, ['dateRead']);
        Schema::createIndex(Table::ASSETINDEXDATA, ['sessionId', 'volumeId']);
        Schema::createIndex(Table::ASSETINDEXDATA, ['volumeId']);
        Schema::createIndex(Table::ASSETS, ['filename', 'folderId']);
        Schema::createIndex(Table::ASSETS, ['folderId']);
        Schema::createIndex(Table::ASSETS, ['volumeId']);
        Schema::createIndex(Table::BULKOPEVENTS, ['timestamp']);
        Schema::createIndex(Table::CHANGEDATTRIBUTES, ['elementId', 'siteId', 'dateUpdated']);
        Schema::createIndex(Table::CHANGEDFIELDS, ['elementId', 'siteId', 'dateUpdated']);
        Schema::createIndex(Table::CONTENTBLOCKS, ['primaryOwnerId']);
        Schema::createIndex(Table::CONTENTBLOCKS, ['fieldId']);
        Schema::createIndex(Table::DEPRECATIONERRORS, ['key', 'fingerprint'], unique: true);
        Schema::createIndex(Table::DRAFTS, ['creatorId', 'provisional']);
        Schema::createIndex(Table::DRAFTS, ['saved']);
        Schema::createIndex(Table::ELEMENTACTIVITY, ['elementId', 'timestamp', 'userId']);
        Schema::createIndex(Table::ELEMENTS, ['dateDeleted']);
        Schema::createIndex(Table::ELEMENTS, ['fieldLayoutId']);
        Schema::createIndex(Table::ELEMENTS, ['type']);
        Schema::createIndex(Table::ELEMENTS, ['enabled']);
        Schema::createIndex(Table::ELEMENTS, ['canonicalId']);
        Schema::createIndex(Table::ELEMENTS, ['archived', 'dateCreated']);
        Schema::createIndex(Table::ELEMENTS, ['archived', 'dateDeleted', 'draftId', 'revisionId', 'canonicalId']);
        Schema::createIndex(Table::ELEMENTS, ['archived', 'dateDeleted', 'draftId', 'revisionId', 'canonicalId', 'enabled']);
        Schema::createIndex(Table::ELEMENTS_BULKOPS, ['timestamp']);
        Schema::createIndex(Table::ELEMENTS_OWNERS, ['sortOrder']);
        Schema::createIndex(Table::ELEMENTS_SITES, ['elementId', 'siteId'], unique: true);
        Schema::createIndex(Table::ELEMENTS_SITES, ['siteId']);
        Schema::createIndex(Table::ELEMENTS_SITES, ['title', 'siteId']);
        Schema::createIndex(Table::ELEMENTS_SITES, ['slug', 'siteId']);
        Schema::createIndex(Table::ELEMENTS_SITES, ['enabled']);
        Schema::createIndex(Table::ELEMENTS_SITES, ['uriLower', 'siteId']);
        Schema::createIndex(Table::SYSTEMMESSAGES, ['key', 'language'], unique: true);
        Schema::createIndex(Table::SYSTEMMESSAGES, ['language']);
        Schema::createIndex(Table::ENTRIES, ['postDate']);
        Schema::createIndex(Table::ENTRIES, ['expiryDate']);
        Schema::createIndex(Table::ENTRIES, ['status']);
        Schema::createIndex(Table::ENTRIES, ['sectionId']);
        Schema::createIndex(Table::ENTRIES, ['typeId']);
        Schema::createIndex(Table::ENTRIES_AUTHORS, ['authorId']);
        Schema::createIndex(Table::ENTRIES_AUTHORS, ['entryId', 'sortOrder']);
        Schema::createIndex(Table::ENTRIES, ['primaryOwnerId']);
        Schema::createIndex(Table::ENTRIES, ['fieldId']);
        Schema::createIndex(Table::ENTRYTYPES, ['fieldLayoutId']);
        Schema::createIndex(Table::ENTRYTYPES, ['dateDeleted']);
        Schema::createIndex(Table::FIELDLAYOUTS, ['dateDeleted']);
        Schema::createIndex(Table::FIELDLAYOUTS, ['type']);
        Schema::createIndex(Table::FIELDS, ['handle', 'context']);
        Schema::createIndex(Table::FIELDS, ['context']);
        Schema::createIndex(Table::FIELDS, ['dateDeleted']);
        Schema::createIndex(Table::GQLTOKENS, ['accessToken'], unique: true);
        Schema::createIndex(Table::GQLTOKENS, ['name'], unique: true);
        Schema::createIndex(Table::IMAGETRANSFORMINDEX, ['assetId', 'transformString']);
        Schema::createIndex(Table::IMAGETRANSFORMS, ['name']);
        Schema::createIndex(Table::IMAGETRANSFORMS, ['handle']);
        Schema::createIndex(Table::PLUGINS, ['handle'], unique: true);
        Schema::createIndex(Table::RELATIONS, ['fieldId', 'sourceId', 'sourceSiteId', 'targetId'], unique: true);
        Schema::createIndex(Table::RELATIONS, ['sourceId']);
        Schema::createIndex(Table::RELATIONS, ['targetId']);
        Schema::createIndex(Table::RELATIONS, ['sourceSiteId']);
        Schema::createIndex(Table::REVISIONS, ['canonicalId', 'num'], unique: true);
        Schema::createIndex(Table::SEARCHINDEXQUEUE, ['elementId', 'siteId', 'reserved']);
        Schema::createIndex(Table::SEARCHINDEXQUEUE_FIELDS, ['jobId', 'fieldHandle'], unique: true);
        Schema::createIndex(Table::SECTIONS, ['handle']);
        Schema::createIndex(Table::SECTIONS, ['name']);
        Schema::createIndex(Table::SECTIONS, ['structureId']);
        Schema::createIndex(Table::SECTIONS, ['dateDeleted']);
        Schema::createIndex(Table::SECTIONS_SITES, ['sectionId', 'siteId'], unique: true);
        Schema::createIndex(Table::SECTIONS_SITES, ['siteId']);
        Schema::createIndex(Table::SHUNNEDMESSAGES, ['userId', 'message'], unique: true);
        Schema::createIndex(Table::SITES, ['dateDeleted']);
        Schema::createIndex(Table::SITES, ['handle']);
        Schema::createIndex(Table::SITES, ['sortOrder']);
        Schema::createIndex(Table::SITEGROUPS, ['name']);
        Schema::createIndex(Table::STRUCTUREELEMENTS, ['structureId', 'elementId'], unique: true);
        Schema::createIndex(Table::STRUCTUREELEMENTS, ['root']);
        Schema::createIndex(Table::STRUCTUREELEMENTS, ['lft']);
        Schema::createIndex(Table::STRUCTUREELEMENTS, ['rgt']);
        Schema::createIndex(Table::STRUCTUREELEMENTS, ['level']);
        Schema::createIndex(Table::STRUCTUREELEMENTS, ['elementId']);
        Schema::createIndex(Table::STRUCTURES, ['dateDeleted']);
        Schema::createIndex(Table::ROUTETOKENS, ['token'], unique: true);
        Schema::createIndex(Table::ROUTETOKENS, ['expiryDate']);
        Schema::createIndex(Table::USERGROUPS, ['handle']);
        Schema::createIndex(Table::USERGROUPS, ['name']);
        Schema::createIndex(Table::USERGROUPS_USERS, ['groupId', 'userId'], unique: true);
        Schema::createIndex(Table::USERGROUPS_USERS, ['userId']);
        Schema::createIndex(Table::USERPERMISSIONS, ['name'], unique: true);
        Schema::createIndex(Table::USERPERMISSIONS_USERGROUPS, ['permissionId', 'groupId'], unique: true);
        Schema::createIndex(Table::USERPERMISSIONS_USERGROUPS, ['groupId']);
        Schema::createIndex(Table::USERPERMISSIONS_USERS, ['permissionId', 'userId'], unique: true);
        Schema::createIndex(Table::USERPERMISSIONS_USERS, ['userId']);
        Schema::createIndex(Table::USERS, ['active']);
        Schema::createIndex(Table::USERS, ['locked']);
        Schema::createIndex(Table::USERS, ['pending']);
        Schema::createIndex(Table::USERS, ['suspended']);
        Schema::createIndex(Table::VOLUMEFOLDERS, ['name', 'parentId', 'volumeId'], unique: true);
        Schema::createIndex(Table::VOLUMEFOLDERS, ['parentId']);
        Schema::createIndex(Table::VOLUMEFOLDERS, ['volumeId']);
        Schema::createIndex(Table::VOLUMES, ['name']);
        Schema::createIndex(Table::VOLUMES, ['handle']);
        Schema::createIndex(Table::VOLUMES, ['fieldLayoutId']);
        Schema::createIndex(Table::VOLUMES, ['dateDeleted']);
        Schema::createIndex(Table::WIDGETS, ['userId']);

        Schema::create(Table::SEARCHINDEX, function (Blueprint $table) {
            $table->integer('elementId');
            $table->string('attribute', 25);
            $table->integer('fieldId');
            $table->integer('siteId');
            $table->text('keywords');

            $table->primary(['elementId', 'attribute', 'fieldId', 'siteId']);
        });

        if (DB::isMysql()) {
            Schema::createIndex(Table::USERS, ['email']);
            Schema::createIndex(Table::USERS, ['username']);

            Schema::table(Table::SEARCHINDEX, function (Blueprint $table) {
                $table->fullText('keywords');
            });
        } elseif (DB::isPgsql()) {
            // Postgres is case-sensitive
            DB::statement('CREATE INDEX users_email_index ON '.DB::getTablePrefix().Table::USERS.' (lower(email))');
            DB::statement('CREATE INDEX users_username_index ON '.DB::getTablePrefix().Table::USERS.' (lower(username))');

            Schema::table(Table::SEARCHINDEX, function (Blueprint $table) {
                $table->rawColumn('keywords_vector', 'tsvector');
            });

            DB::statement('CREATE INDEX keywords_gin ON '.DB::getTablePrefix().Table::SEARCHINDEX.' USING GIN(keywords_vector) WITH (FASTUPDATE=YES)');
            DB::statement('CREATE INDEX keywords_index ON '.DB::getTablePrefix().Table::SEARCHINDEX.' USING btree(keywords)');
        } else {
            // SQLite: basic indexes only, no full-text or tsvector
            Schema::createIndex(Table::USERS, ['email']);
            Schema::createIndex(Table::USERS, ['username']);
        }
    }

    public function addForeignKeys(): void
    {
        Schema::table(Table::ADDRESSES, fn (Blueprint $table) => $table->foreign('id')->references('id')->on(Table::ELEMENTS)->cascadeOnDelete());
        Schema::table(Table::ADDRESSES, fn (Blueprint $table) => $table->foreign('primaryOwnerId')->references('id')->on(Table::ELEMENTS)->cascadeOnDelete());
        Schema::table(Table::ANNOUNCEMENTS, fn (Blueprint $table) => $table->foreign('userId')->references('id')->on(Table::USERS)->cascadeOnDelete());
        Schema::table(Table::ANNOUNCEMENTS, fn (Blueprint $table) => $table->foreign('pluginId')->references('id')->on(Table::PLUGINS)->cascadeOnDelete());
        Schema::table(Table::ASSETINDEXDATA, fn (Blueprint $table) => $table->foreign('volumeId')->references('id')->on(Table::VOLUMES)->cascadeOnDelete());
        Schema::table(Table::ASSETINDEXDATA, fn (Blueprint $table) => $table->foreign('sessionId')->references('id')->on(Table::ASSETINDEXINGSESSIONS)->cascadeOnDelete());
        Schema::table(Table::ASSETS, fn (Blueprint $table) => $table->foreign('folderId')->references('id')->on(Table::VOLUMEFOLDERS)->cascadeOnDelete());
        Schema::table(Table::ASSETS, fn (Blueprint $table) => $table->foreign('id')->references('id')->on(Table::ELEMENTS)->cascadeOnDelete());
        Schema::table(Table::ASSETS, fn (Blueprint $table) => $table->foreign('uploaderId')->references('id')->on(Table::USERS)->nullOnDelete());
        Schema::table(Table::ASSETS, fn (Blueprint $table) => $table->foreign('volumeId')->references('id')->on(Table::VOLUMES)->cascadeOnDelete());
        Schema::table(Table::ASSETS_SITES, fn (Blueprint $table) => $table->foreign('assetId')->references('id')->on(Table::ASSETS)->cascadeOnDelete());
        Schema::table(Table::ASSETS_SITES, fn (Blueprint $table) => $table->foreign('siteId')->references('id')->on(Table::SITES)->cascadeOnDelete()->cascadeOnUpdate());
        Schema::table(Table::AUTHENTICATOR, fn (Blueprint $table) => $table->foreign('userId')->references('id')->on(Table::USERS)->cascadeOnDelete());
        Schema::table(Table::CHANGEDATTRIBUTES, fn (Blueprint $table) => $table->foreign('elementId')->references('id')->on(Table::ELEMENTS)->cascadeOnDelete()->cascadeOnUpdate());
        Schema::table(Table::CHANGEDATTRIBUTES, fn (Blueprint $table) => $table->foreign('siteId')->references('id')->on(Table::SITES)->cascadeOnDelete()->cascadeOnUpdate());
        Schema::table(Table::CHANGEDATTRIBUTES, fn (Blueprint $table) => $table->foreign('userId')->references('id')->on(Table::USERS)->nullOnDelete()->cascadeOnUpdate());
        Schema::table(Table::CHANGEDFIELDS, fn (Blueprint $table) => $table->foreign('elementId')->references('id')->on(Table::ELEMENTS)->cascadeOnDelete()->cascadeOnUpdate());
        Schema::table(Table::CONTENTBLOCKS, fn (Blueprint $table) => $table->foreign('id')->references('id')->on(Table::ELEMENTS)->cascadeOnDelete());
        Schema::table(Table::CONTENTBLOCKS, fn (Blueprint $table) => $table->foreign('fieldId')->references('id')->on(Table::FIELDS)->cascadeOnDelete());
        Schema::table(Table::CONTENTBLOCKS, fn (Blueprint $table) => $table->foreign('primaryOwnerId')->references('id')->on(Table::ELEMENTS)->cascadeOnDelete());
        Schema::table(Table::CHANGEDFIELDS, fn (Blueprint $table) => $table->foreign('siteId')->references('id')->on(Table::SITES)->cascadeOnDelete()->cascadeOnUpdate());
        Schema::table(Table::CHANGEDFIELDS, fn (Blueprint $table) => $table->foreign('fieldId')->references('id')->on(Table::FIELDS)->cascadeOnDelete()->cascadeOnUpdate());
        Schema::table(Table::CHANGEDFIELDS, fn (Blueprint $table) => $table->foreign('userId')->references('id')->on(Table::USERS)->nullOnDelete()->cascadeOnUpdate());
        Schema::table(Table::DRAFTS, fn (Blueprint $table) => $table->foreign('creatorId')->references('id')->on(Table::USERS)->nullOnDelete());
        Schema::table(Table::DRAFTS, fn (Blueprint $table) => $table->foreign('canonicalId')->references('id')->on(Table::ELEMENTS)->cascadeOnDelete());
        Schema::table(Table::ELEMENTACTIVITY, fn (Blueprint $table) => $table->foreign('elementId')->references('id')->on(Table::ELEMENTS)->cascadeOnDelete());
        Schema::table(Table::ELEMENTACTIVITY, fn (Blueprint $table) => $table->foreign('userId')->references('id')->on(Table::USERS)->cascadeOnDelete());
        Schema::table(Table::ELEMENTACTIVITY, fn (Blueprint $table) => $table->foreign('siteId')->references('id')->on(Table::SITES)->cascadeOnDelete());
        Schema::table(Table::ELEMENTACTIVITY, fn (Blueprint $table) => $table->foreign('draftId')->references('id')->on(Table::DRAFTS)->cascadeOnDelete());
        Schema::table(Table::ELEMENTS, fn (Blueprint $table) => $table->foreign('canonicalId')->references('id')->on(Table::ELEMENTS)->nullOnDelete());
        Schema::table(Table::ELEMENTS, fn (Blueprint $table) => $table->foreign('draftId')->references('id')->on(Table::DRAFTS)->cascadeOnDelete());
        Schema::table(Table::ELEMENTS, fn (Blueprint $table) => $table->foreign('revisionId')->references('id')->on(Table::REVISIONS)->cascadeOnDelete());
        Schema::table(Table::ELEMENTS, fn (Blueprint $table) => $table->foreign('fieldLayoutId')->references('id')->on(Table::FIELDLAYOUTS)->nullOnDelete());
        Schema::table(Table::ELEMENTS_OWNERS, fn (Blueprint $table) => $table->foreign('elementId')->references('id')->on(Table::ELEMENTS)->cascadeOnDelete());
        Schema::table(Table::ELEMENTS_OWNERS, fn (Blueprint $table) => $table->foreign('ownerId')->references('id')->on(Table::ELEMENTS)->cascadeOnDelete());
        Schema::table(Table::ELEMENTS_SITES, fn (Blueprint $table) => $table->foreign('elementId')->references('id')->on(Table::ELEMENTS)->cascadeOnDelete());
        Schema::table(Table::ELEMENTS_SITES, fn (Blueprint $table) => $table->foreign('siteId')->references('id')->on(Table::SITES)->cascadeOnDelete()->cascadeOnUpdate());
        Schema::table(Table::ENTRIES, fn (Blueprint $table) => $table->foreign('id')->references('id')->on(Table::ELEMENTS)->cascadeOnDelete());
        Schema::table(Table::ENTRIES, fn (Blueprint $table) => $table->foreign('sectionId')->references('id')->on(Table::SECTIONS)->cascadeOnDelete());
        Schema::table(Table::ENTRIES, fn (Blueprint $table) => $table->foreign('parentId')->references('id')->on(Table::ENTRIES)->nullOnDelete());
        Schema::table(Table::ENTRIES, fn (Blueprint $table) => $table->foreign('typeId')->references('id')->on(Table::ENTRYTYPES)->cascadeOnDelete());
        Schema::table(Table::ENTRIES_AUTHORS, fn (Blueprint $table) => $table->foreign('entryId')->references('id')->on(Table::ENTRIES)->cascadeOnDelete());
        Schema::table(Table::ENTRIES_AUTHORS, fn (Blueprint $table) => $table->foreign('authorId')->references('id')->on(Table::USERS)->cascadeOnDelete());
        Schema::table(Table::ENTRIES, fn (Blueprint $table) => $table->foreign('fieldId')->references('id')->on(Table::FIELDS)->cascadeOnDelete());
        Schema::table(Table::ENTRIES, fn (Blueprint $table) => $table->foreign('primaryOwnerId')->references('id')->on(Table::ELEMENTS)->cascadeOnDelete());
        Schema::table(Table::ENTRYTYPES, fn (Blueprint $table) => $table->foreign('fieldLayoutId')->references('id')->on(Table::FIELDLAYOUTS)->nullOnDelete());
        Schema::table(Table::GQLTOKENS, fn (Blueprint $table) => $table->foreign('schemaId')->references('id')->on(Table::GQLSCHEMAS)->nullOnDelete());
        Schema::table(Table::RELATIONS, fn (Blueprint $table) => $table->foreign('fieldId')->references('id')->on(Table::FIELDS)->cascadeOnDelete());
        Schema::table(Table::RELATIONS, fn (Blueprint $table) => $table->foreign('sourceId')->references('id')->on(Table::ELEMENTS)->cascadeOnDelete());
        Schema::table(Table::RELATIONS, fn (Blueprint $table) => $table->foreign('sourceSiteId')->references('id')->on(Table::SITES)->cascadeOnDelete()->cascadeOnUpdate());
        Schema::table(Table::REVISIONS, fn (Blueprint $table) => $table->foreign('creatorId')->references('id')->on(Table::USERS)->nullOnDelete());
        Schema::table(Table::REVISIONS, fn (Blueprint $table) => $table->foreign('canonicalId')->references('id')->on(Table::ELEMENTS)->cascadeOnDelete());
        Schema::table(Table::SEARCHINDEXQUEUE_FIELDS, fn (Blueprint $table) => $table->foreign('jobId')->references('id')->on(Table::SEARCHINDEXQUEUE)->cascadeOnDelete());
        Schema::table(Table::SECTIONS, fn (Blueprint $table) => $table->foreign('structureId')->references('id')->on(Table::STRUCTURES)->nullOnDelete());
        Schema::table(Table::SECTIONS_ENTRYTYPES, fn (Blueprint $table) => $table->foreign('sectionId')->references('id')->on(Table::SECTIONS)->cascadeOnDelete());
        Schema::table(Table::SECTIONS_ENTRYTYPES, fn (Blueprint $table) => $table->foreign('typeId')->references('id')->on(Table::ENTRYTYPES)->cascadeOnDelete());
        Schema::table(Table::SECTIONS_SITES, fn (Blueprint $table) => $table->foreign('siteId')->references('id')->on(Table::SITES)->cascadeOnDelete()->cascadeOnUpdate());
        Schema::table(Table::SECTIONS_SITES, fn (Blueprint $table) => $table->foreign('sectionId')->references('id')->on(Table::SECTIONS)->cascadeOnDelete());
        Schema::table(Table::SHUNNEDMESSAGES, fn (Blueprint $table) => $table->foreign('userId')->references('id')->on(Table::USERS)->cascadeOnDelete());
        Schema::table(Table::SITES, fn (Blueprint $table) => $table->foreign('groupId')->references('id')->on(Table::SITEGROUPS)->cascadeOnDelete());
        Schema::table(Table::SSO_IDENTITIES, fn (Blueprint $table) => $table->foreign('userId')->references('id')->on(Table::USERS)->cascadeOnDelete());
        Schema::table(Table::STRUCTUREELEMENTS, fn (Blueprint $table) => $table->foreign('structureId')->references('id')->on(Table::STRUCTURES)->cascadeOnDelete());
        Schema::table(Table::USERGROUPS_USERS, fn (Blueprint $table) => $table->foreign('groupId')->references('id')->on(Table::USERGROUPS)->cascadeOnDelete());
        Schema::table(Table::USERGROUPS_USERS, fn (Blueprint $table) => $table->foreign('userId')->references('id')->on(Table::USERS)->cascadeOnDelete());
        Schema::table(Table::USERPERMISSIONS_USERGROUPS, fn (Blueprint $table) => $table->foreign('groupId')->references('id')->on(Table::USERGROUPS)->cascadeOnDelete());
        Schema::table(Table::USERPERMISSIONS_USERGROUPS, fn (Blueprint $table) => $table->foreign('permissionId')->references('id')->on(Table::USERPERMISSIONS)->cascadeOnDelete());
        Schema::table(Table::USERPERMISSIONS_USERS, fn (Blueprint $table) => $table->foreign('permissionId')->references('id')->on(Table::USERPERMISSIONS)->cascadeOnDelete());
        Schema::table(Table::USERPERMISSIONS_USERS, fn (Blueprint $table) => $table->foreign('userId')->references('id')->on(Table::USERS)->cascadeOnDelete());
        Schema::table(Table::USERPREFERENCES, fn (Blueprint $table) => $table->foreign('userId')->references('id')->on(Table::USERS)->cascadeOnDelete());
        Schema::table(Table::USERS, fn (Blueprint $table) => $table->foreign('id')->references('id')->on(Table::ELEMENTS)->cascadeOnDelete());
        Schema::table(Table::USERS, fn (Blueprint $table) => $table->foreign('photoId')->references('id')->on(Table::ASSETS)->nullOnDelete());
        Schema::table(Table::USERS, fn (Blueprint $table) => $table->foreign('affiliatedSiteId')->references('id')->on(Table::SITES)->nullOnDelete());
        Schema::table(Table::VOLUMEFOLDERS, fn (Blueprint $table) => $table->foreign('parentId')->references('id')->on(Table::VOLUMEFOLDERS)->cascadeOnDelete());
        Schema::table(Table::VOLUMEFOLDERS, fn (Blueprint $table) => $table->foreign('volumeId')->references('id')->on(Table::VOLUMES)->cascadeOnDelete());
        Schema::table(Table::VOLUMES, fn (Blueprint $table) => $table->foreign('fieldLayoutId')->references('id')->on(Table::FIELDLAYOUTS)->nullOnDelete());
        Schema::table(Table::WEBAUTHN, fn (Blueprint $table) => $table->foreign('userId')->references('id')->on(Table::USERS)->cascadeOnDelete());
        Schema::table(Table::WIDGETS, fn (Blueprint $table) => $table->foreign('userId')->references('id')->on(Table::USERS)->cascadeOnDelete());
    }

    public function insertDefaultData(): void
    {
        $this->components->task('Populating the info table', function () {
            Info::create([
                'id' => 1,
                'version' => Cms::VERSION,
                'schemaVersion' => Cms::SCHEMA_VERSION,
                'configVersion' => Str::random(12),
            ]);
        });

        $projectConfig = app(ProjectConfig::class);

        if ($this->applyProjectConfigYaml) {
            // Make sure at least sites are processed
            ProjectConfigHelper::ensureAllSitesProcessed(true);
            $this->_installPlugins();

            $this->components->task('Applying the project config', function () use ($projectConfig) {
                // Save the existing system settings
                $projectConfig->applyExternalChanges();
            });
        } else {
            $this->components->task('Saving default data', function () use ($projectConfig) {
                $configData = $this->_generateInitialConfig();
                $projectConfig->applyConfigChanges($configData);
            });
        }

        $projectConfig->flush();

        // Craft, you are installed now.
        Cms::setIsInstalled();

        Sites::refreshSites();

        if ($this->applyProjectConfigYaml) {
            // Update the primary site with the installer settings
            $site = Sites::getPrimarySite();
            $site->setBaseUrl($this->site->getBaseUrl(false));
            $site->hasUrls = $this->site->hasUrls;
            $site->setLanguage($this->site->getLanguage(false));
            $site->setName($this->site->getName(false));
            Sites::saveSite($site);
        }

        app()->setLocale($this->site->getLanguage());

        $this->components->task('Saving the first user', function () {
            $user = new User([
                'active' => true,
                'admin' => true,
                'username' => $this->username,
                'newPassword' => $this->password,
                'email' => $this->email,
            ]);
            Elements::saveElement($user);

            Users::saveUserPreferences($user, [
                'language' => $this->site->getLanguage(),
            ]);

            if (! app()->runningInConsole()) {
                Auth::guard('craft')->loginUsingId($user->id);
            }
        });
    }

    private function _validateProjectConfig(?string &$error = null): bool
    {
        if (! $this->applyProjectConfigYaml) {
            return true;
        }

        if (! app(ProjectConfig::class)->getDoesExternalConfigExist()) {
            $this->applyProjectConfigYaml = false;

            return true;
        }

        $expectedSchemaVersion = (string) app(ProjectConfig::class)->get(ProjectConfig::PATH_SCHEMA_VERSION, true);
        $craftSchemaVersion = Cms::SCHEMA_VERSION;

        if (! version_compare($craftSchemaVersion, $expectedSchemaVersion, '=')) {
            $error = "Craft CMS is Composer-installed with schema version $craftSchemaVersion, but project.yaml expects $expectedSchemaVersion.";

            return false;
        }

        $pluginsService = app(Plugins::class);
        $pluginConfigs = app(ProjectConfig::class)->get(ProjectConfig::PATH_PLUGINS, true) ?? [];

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
        $pluginsService = app(Plugins::class);
        $pluginConfigs = app(ProjectConfig::class)->get(ProjectConfig::PATH_PLUGINS, true) ?? [];

        // Prevent the plugin from sending any headers, etc.
        $realResponse = Craft::$app->getResponse();
        $tempResponse = new Response(['isSent' => true]);
        Craft::$app->set('response', $tempResponse);

        try {
            foreach ($pluginConfigs as $handle => $pluginConfig) {
                $this->components->task("Installing $handle", function () use ($handle, $pluginsService) {
                    $pluginsService->installPlugin($handle);
                });
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
                    'language' => $this->site->getLanguage(false),
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
                'schemaVersion' => Cms::SCHEMA_VERSION,
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

    public function down(): void
    {
        $this->output->writeln('Install migration cannot be reverted.');
    }
}
