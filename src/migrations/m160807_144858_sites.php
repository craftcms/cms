<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\fields\Assets;
use craft\fields\Categories;
use craft\fields\Entries;
use craft\fields\Matrix;
use craft\fields\Tags;
use craft\fields\Users;
use craft\helpers\Json;
use craft\helpers\MigrationHelper;
use craft\helpers\StringHelper;
use craft\validators\HandleValidator;
use yii\db\Expression;

/**
 * m160807_144858_sites migration.
 */
class m160807_144858_sites extends Migration
{
    /**
     * @var array The site FK columns ([table, column, not null?, locale column])
     */
    protected static $siteColumns = [
        ['{{%categorygroups_i18n}}', 'siteId', true, 'locale'],
        [Table::CONTENT, 'siteId', true, 'locale'],
        ['{{%elements_i18n}}', 'siteId', true, 'locale'],
        [Table::ENTRYDRAFTS, 'siteId', true, 'locale'],
        [Table::ENTRYVERSIONS, 'siteId', true, 'locale'],
        [Table::MATRIXBLOCKS, 'ownerSiteId', false, 'ownerLocale'],
        [Table::RELATIONS, 'sourceSiteId', false, 'sourceLocale'],
        ['{{%routes}}', 'siteId', false, 'locale'],
        [Table::SEARCHINDEX, 'siteId', true, 'locale'],
        ['{{%sections_i18n}}', 'siteId', true, 'locale'],
        [Table::TEMPLATECACHES, 'siteId', true, 'locale'],
    ];

    /**
     * @var string|null The CASE SQL used to set site column values
     */
    protected $caseSql;

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // In case this was run in a previous update attempt
        $this->execute($this->db->getQueryBuilder()->checkIntegrity(false, '', Table::SITES));
        $this->dropTableIfExists(Table::SITES);

        // Create the sites table
        // ---------------------------------------------------------------------

        $this->createTable(Table::SITES, [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'language' => $this->string(12)->notNull(),
            'hasUrls' => $this->boolean()->unsigned(),
            'baseUrl' => $this->string(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, Table::SITES, ['handle'], true);
        $this->createIndex(null, Table::SITES, ['sortOrder'], false);

        // Populate based on existing locales
        // ---------------------------------------------------------------------

        $siteInfo = (new Query())
            ->select(['siteName', 'siteUrl'])
            ->from([Table::INFO])
            ->one($this->db);

        $locales = (new Query())
            ->select(['uid', 'locale'])
            ->from(['{{%locales}}'])
            ->orderBy(['sortOrder' => SORT_ASC])
            ->pairs($this->db);

        $siteIdsByLocale = [];
        $this->caseSql = 'case';
        $languageCaseSql = 'case';
        $localePermissions = [];
        $permissionsCaseSql = 'case';
        $sortOrder = 0;

        foreach ($locales as $uid => $locale) {
            $siteHandle = $this->locale2handle($locale);
            $language = $this->locale2language($locale);

            $this->insert(Table::SITES, [
                'name' => "{$siteInfo['siteName']} ({$language})",
                'handle' => $siteHandle,
                'language' => $language,
                'hasUrls' => 1,
                'baseUrl' => $siteInfo['siteUrl'],
                'sortOrder' => ++$sortOrder,
                'uid' => $uid,
            ]);

            $siteId = $this->db->getLastInsertID();
            $siteIdsByLocale[$locale] = $siteId;

            $this->caseSql .= ' when % = ' . $this->db->quoteValue($locale) . ' then ' . $this->db->quoteValue($siteId);
            $languageCaseSql .= ' when [[language]] = ' . $this->db->quoteValue($locale) . ' then ' . $this->db->quoteValue($language);

            $localePermission = 'editlocale:' . $locale;
            $sitePermission = 'editsite:' . $siteId;
            $localePermissions[] = $localePermission;
            $permissionsCaseSql .= ' when [[name]] = ' . $this->db->quoteValue($localePermission) . ' then ' . $this->db->quoteValue($sitePermission);
        }

        $this->caseSql .= ' end';
        $languageCaseSql .= ' end';
        $permissionsCaseSql .= ' end';

        // Update the user permissions
        // ---------------------------------------------------------------------

        $this->update(
            Table::USERPERMISSIONS,
            [
                'name' => new Expression($permissionsCaseSql),
            ],
            ['name' => $localePermissions],
            [],
            false);

        // Create the FK columns
        // ---------------------------------------------------------------------

        foreach (self::$siteColumns as list($table, $column, $isNotNull, $localeColumn)) {
            $this->addSiteColumn($table, $column, $isNotNull, $localeColumn);
        }

        // Create the new indexes
        // ---------------------------------------------------------------------

        $this->createIndex(null, '{{%categorygroups_i18n}}', ['groupId', 'siteId'], true);
        $this->createIndex(null, '{{%categorygroups_i18n}}', ['siteId'], false);
        $this->createIndex(null, Table::CONTENT, ['elementId', 'siteId'], true);
        $this->createIndex(null, Table::CONTENT, ['siteId'], false);
        $this->createIndex(null, '{{%elements_i18n}}', ['elementId', 'siteId'], true);
        $this->createIndex(null, '{{%elements_i18n}}', ['uri', 'siteId'], true);
        $this->createIndex(null, '{{%elements_i18n}}', ['siteId'], false);
        $this->createIndex(null, '{{%elements_i18n}}', ['slug', 'siteId'], false);
        $this->createIndex(null, Table::ENTRYDRAFTS, ['entryId', 'siteId'], false);
        $this->createIndex(null, Table::ENTRYDRAFTS, ['siteId'], false);
        $this->createIndex(null, Table::ENTRYVERSIONS, ['entryId', 'siteId'], false);
        $this->createIndex(null, Table::ENTRYVERSIONS, ['siteId'], false);
        $this->createIndex(null, Table::MATRIXBLOCKS, ['ownerSiteId'], false);
        $this->createIndex(null, Table::RELATIONS, ['fieldId', 'sourceId', 'sourceSiteId', 'targetId'], true);
        $this->createIndex(null, Table::RELATIONS, ['sourceSiteId'], false);
        $this->createIndex(null, '{{%routes}}', ['siteId'], false);
        $this->createIndex(null, '{{%sections_i18n}}', ['sectionId', 'siteId'], true);
        $this->createIndex(null, '{{%sections_i18n}}', ['siteId'], false);
        $this->createIndex(null, Table::TEMPLATECACHES, ['expiryDate', 'cacheKey', 'siteId', 'path'], false);
        $this->createIndex(null, Table::TEMPLATECACHES, ['siteId'], false);

        // Create the new FKs
        // ---------------------------------------------------------------------

        $this->addForeignKey(null, '{{%categorygroups_i18n}}', ['siteId'], Table::SITES, ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, Table::CONTENT, ['siteId'], Table::SITES, ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, '{{%elements_i18n}}', ['siteId'], Table::SITES, ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, Table::ENTRYDRAFTS, ['siteId'], Table::SITES, ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, Table::ENTRYVERSIONS, ['siteId'], Table::SITES, ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, Table::MATRIXBLOCKS, ['ownerSiteId'], Table::SITES, ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, Table::RELATIONS, ['sourceSiteId'], Table::SITES, ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, '{{%routes}}', ['siteId'], Table::SITES, ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, '{{%sections_i18n}}', ['siteId'], Table::SITES, ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, Table::TEMPLATECACHES, ['siteId'], Table::SITES, ['id'], 'CASCADE', 'CASCADE');

        // Update the searchindex PK
        // ---------------------------------------------------------------------

        $searchTable = $this->db->getSchema()->getRawTableName(Table::SEARCHINDEX);
        $this->execute('alter table ' . $this->db->quoteTableName($searchTable) . ' drop primary key, add primary key(elementId, attribute, fieldId, siteId)');

        // Drop the old FKs
        // ---------------------------------------------------------------------

        MigrationHelper::dropForeignKeyIfExists('{{%categorygroups_i18n}}', ['locale'], $this);
        MigrationHelper::dropForeignKeyIfExists(Table::CONTENT, ['locale'], $this);
        MigrationHelper::dropForeignKeyIfExists('{{%elements_i18n}}', ['locale'], $this);
        MigrationHelper::dropForeignKeyIfExists(Table::ENTRYDRAFTS, ['locale'], $this);
        MigrationHelper::dropForeignKeyIfExists(Table::ENTRYVERSIONS, ['locale'], $this);
        MigrationHelper::dropForeignKeyIfExists(Table::MATRIXBLOCKS, ['ownerLocale'], $this);
        MigrationHelper::dropForeignKeyIfExists(Table::RELATIONS, ['sourceLocale'], $this);
        MigrationHelper::dropForeignKeyIfExists('{{%routes}}', ['locale'], $this);
        MigrationHelper::dropForeignKeyIfExists('{{%sections_i18n}}', ['locale'], $this);
        MigrationHelper::dropForeignKeyIfExists(Table::TEMPLATECACHES, ['locale'], $this);

        // Drop the old indexes
        // ---------------------------------------------------------------------

        MigrationHelper::dropIndexIfExists('{{%categorygroups_i18n}}', ['groupId', 'locale'], true, $this);
        MigrationHelper::dropIndexIfExists('{{%categorygroups_i18n}}', ['locale'], false, $this);
        MigrationHelper::dropIndexIfExists(Table::CONTENT, ['elementId', 'locale'], true, $this);
        MigrationHelper::dropIndexIfExists(Table::CONTENT, ['locale'], false, $this);
        MigrationHelper::dropIndexIfExists('{{%elements_i18n}}', ['elementId', 'locale'], true, $this);
        MigrationHelper::dropIndexIfExists('{{%elements_i18n}}', ['uri', 'locale'], true, $this);
        MigrationHelper::dropIndexIfExists('{{%elements_i18n}}', ['locale'], false, $this);
        MigrationHelper::dropIndexIfExists('{{%elements_i18n}}', ['slug', 'locale'], false, $this);
        MigrationHelper::dropIndexIfExists(Table::ENTRYDRAFTS, ['entryId', 'locale'], false, $this);
        MigrationHelper::dropIndexIfExists(Table::ENTRYDRAFTS, ['locale'], false, $this);
        MigrationHelper::dropIndexIfExists(Table::ENTRYVERSIONS, ['entryId', 'locale'], false, $this);
        MigrationHelper::dropIndexIfExists(Table::ENTRYVERSIONS, ['locale'], false, $this);
        MigrationHelper::dropIndexIfExists(Table::MATRIXBLOCKS, ['ownerLocale'], false, $this);
        MigrationHelper::dropIndexIfExists(Table::RELATIONS, ['fieldId', 'sourceId', 'sourceLocale', 'targetId'], true, $this);
        MigrationHelper::dropIndexIfExists(Table::RELATIONS, ['sourceLocale'], false, $this);
        MigrationHelper::dropIndexIfExists('{{%routes}}', ['locale'], false, $this);
        MigrationHelper::dropIndexIfExists('{{%sections_i18n}}', ['sectionId', 'locale'], true, $this);
        MigrationHelper::dropIndexIfExists('{{%sections_i18n}}', ['locale'], false, $this);
        MigrationHelper::dropIndexIfExists(Table::TEMPLATECACHES, ['expiryDate', 'cacheKey', 'locale', 'path'], false, $this);
        MigrationHelper::dropIndexIfExists(Table::TEMPLATECACHES, ['locale'], false, $this);

        // Drop the locale columns
        // ---------------------------------------------------------------------

        foreach (self::$siteColumns as list($table, , , $localeColumn)) {
            $this->dropColumn($table, $localeColumn);
        }

        // Email Messages
        // ---------------------------------------------------------------------

        MigrationHelper::dropForeignKeyIfExists('{{%emailmessages}}', ['locale'], $this);
        MigrationHelper::renameColumn('{{%emailmessages}}', 'locale', 'language', $this);
        $this->alterColumn('{{%emailmessages}}', 'language', $this->string()->notNull());

        $this->update('{{%emailmessages}}', [
            'language' => new Expression($languageCaseSql),
        ], '', [], false);

        // Matrix content tables
        // ---------------------------------------------------------------------

        $matrixTablePrefix = $this->db->getSchema()->getRawTableName('{{%matrixcontent_}}');

        foreach ($this->db->getSchema()->getTableNames() as $tableName) {
            if (StringHelper::startsWith($tableName, $matrixTablePrefix)) {
                // Add the new siteId column + index
                $this->addSiteColumn($tableName, 'siteId', true, 'locale');
                $this->createIndex(null, $tableName, ['elementId', 'siteId'], true);
                $this->addForeignKey(null, $tableName, ['siteId'], Table::SITES, ['id'], 'CASCADE', 'CASCADE');

                // Delete the old FK, indexes, and column
                MigrationHelper::dropForeignKeyIfExists($tableName, ['locale'], $this);
                MigrationHelper::dropIndexIfExists($tableName, ['elementId', 'locale'], true, $this);
                MigrationHelper::dropIndexIfExists($tableName, ['locale'], false, $this);
                $this->dropColumn($tableName, 'locale');
            }
        }

        // Add site FKs to third party tables
        // ---------------------------------------------------------------------

        Craft::$app->getDb()->getSchema()->refresh();
        $fks = MigrationHelper::findForeignKeysTo('{{%locales}}', 'locale');

        foreach ($fks as $refTable => $fkInfo) {
            foreach ($fkInfo as $num => $fkData) {
                $columns = [];

                foreach ($fkData as $key => $fk) {
                    if ($key !== 0 && $key !== 'updateType' && $key !== 'deleteType') {
                        $columns[] = $key;
                    }
                }

                // Drop the old FK
                MigrationHelper::dropForeignKey($refTable, $columns, $this);

                $originalRefTable = StringHelper::removeLeft($refTable, Craft::$app->getConfig()->getDb()->tablePrefix);
                $originalRefTable = Craft::$app->getDb()->getTableSchema('{{%' . $originalRefTable . '}}');

                // Add a new *__siteId column + FK for each column in this FK that points to locales.locale
                foreach ($columns as $refColumn) {
                    $newColumn = $refColumn . '__siteId';
                    $isNotNull = !$originalRefTable->getColumn($refColumn)->allowNull;
                    $this->addSiteColumn($refTable, $newColumn, $isNotNull, $refColumn);
                    $this->addForeignKey(null, $refTable, [$newColumn], Table::SITES, ['id'], 'CASCADE', 'CASCADE');
                }
            }
        }

        // Drop the locales table
        // ---------------------------------------------------------------------

        $this->dropTable('{{%locales}}');

        // Update the site columns from the info table
        // ---------------------------------------------------------------------

        $this->renameColumn(Table::INFO, 'siteName', 'name');
        $this->dropColumn(Table::INFO, 'siteUrl');

        // Modify sections and categorygroups tables
        // ---------------------------------------------------------------------

        $i18nTables = [
            ['primary' => Table::CATEGORYGROUPS, 'i18n' => '{{%categorygroups_i18n}}', 'fk' => 'groupId'],
            ['primary' => Table::SECTIONS, 'i18n' => '{{%sections_i18n}}', 'fk' => 'sectionId'],
        ];

        foreach ($i18nTables as $tables) {
            // urlFormat => uriFormat
            $this->renameColumn($tables['i18n'], 'urlFormat', 'uriFormat');

            // Combine the uriFormat and nestedUrlFormat columns
            $results = (new Query())
                ->select(['id', 'uriFormat', 'nestedUrlFormat'])
                ->from([$tables['i18n']])
                ->where([
                    'not',
                    [
                        'or',
                        ['nestedUrlFormat' => null],
                        ['nestedUrlFormat' => ''],
                        '[[nestedUrlFormat]] = [[uriFormat]]'
                    ]
                ])
                ->all($this->db);

            foreach ($results as $result) {
                $uriFormat = '{% if object.level == 1 %}' . $result['uriFormat'] . '{% else %}' . $result['nestedUrlFormat'] . '{% endif %}';
                $this->update($tables['i18n'], ['uriFormat' => $uriFormat], ['id' => $result['id']], [], false);
            }

            // Drop the nestedUrlFormat column
            $this->dropColumn($tables['i18n'], 'nestedUrlFormat');

            // Create hasUrls and template columns in the i18n table
            $this->addColumn($tables['i18n'], 'hasUrls', $this->boolean()->after('siteId')->notNull()->defaultValue(true));
            $this->addColumn($tables['i18n'], 'template', $this->string(500)->after('uriFormat'));

            // Move the hasUrls and template values into the i18n table
            $results = (new Query())
                ->select(['id', 'hasUrls', 'template'])
                ->from([$tables['primary']])
                ->all($this->db);

            foreach ($results as $result) {
                $this->update($tables['i18n'], [
                    'hasUrls' => $result['hasUrls'],
                    'template' => $result['template'],
                ], [$tables['fk'] => $result['id']], [], false);
            }

            // Drop the old hasUrls and template columns
            $this->dropColumn($tables['primary'], 'hasUrls');
            $this->dropColumn($tables['primary'], 'template');
        }

        // Field translation methods
        // ---------------------------------------------------------------------

        $this->addColumn(Table::FIELDS, 'translationMethod', $this->enum('translationMethod', ['none', 'language', 'site', 'custom'])->after('instructions')->notNull()->defaultValue('none'));
        $this->addColumn(Table::FIELDS, 'translationKeyFormat', $this->text()->after('translationMethod'));

        $this->update(
            Table::FIELDS,
            [
                'translationMethod' => 'site',
            ],
            [
                'translatable' => true,
            ],
            [], false);

        $this->dropColumn(Table::FIELDS, 'translatable');

        // Update Matrix/relationship field settings
        // ---------------------------------------------------------------------

        $fields = (new Query())
            ->select(['id', 'type', 'translationMethod', 'settings'])
            ->from([Table::FIELDS])
            ->where([
                'type' => [
                    Matrix::class,
                    Assets::class,
                    Categories::class,
                    Entries::class,
                    Tags::class,
                    Users::class
                ]
            ])
            ->all($this->db);

        foreach ($fields as $field) {
            if ($field['settings'] === null) {
                echo 'Field ' . $field['id'] . ' (' . $field['type'] . ') settings were null' . "\n";
                $settings = [];
            } else {
                $settings = Json::decodeIfJson($field['settings']);
                if (!is_array($settings)) {
                    echo 'Field ' . $field['id'] . ' (' . $field['type'] . ') settings were invalid JSON: ' . $field['settings'] . "\n";
                    $settings = [];
                }
            }

            $localized = ($field['translationMethod'] === 'site');

            if ($field['type'] === 'craft\fields\Matrix') {
                $settings['propagationMethod'] = $localized ? 'none' : 'all';
            } else {
                // Exception: Cannot use a scalar value as an array
                $settings['localizeRelations'] = $localized;

                // targetLocale => targetSiteId
                if (!empty($settings['targetLocale'])) {
                    $settings['targetSiteId'] = $siteIdsByLocale[$settings['targetLocale']];
                }
                unset($settings['targetLocale']);
            }

            $this->update(
                Table::FIELDS,
                [
                    'translationMethod' => 'none',
                    'settings' => Json::encode($settings),
                ],
                ['id' => $field['id']],
                [],
                false);
        }

        // Update Recent Entries widgets
        // ---------------------------------------------------------------------

        $this->updateRecentEntriesWidgets($siteIdsByLocale);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m160807_144858_sites cannot be reverted.\n";

        return false;
    }

    /**
     * Updates the 'locale' setting in Recent Entries widgets
     *
     * @param array $siteIdsByLocale Mapping of site IDs to the locale IDs they used to be
     */
    public function updateRecentEntriesWidgets(array $siteIdsByLocale)
    {
        // Fetch all the Recent Entries widgets that have a locale setting
        $widgetResults = (new Query())
            ->select(['id', 'settings'])
            ->from([Table::WIDGETS])
            ->where(['like', 'settings', '"locale":'])
            ->all($this->db);

        foreach ($widgetResults as $result) {
            $settings = Json::decode($result['settings']);

            // Just to be sure...
            if (!isset($settings['locale'])) {
                continue;
            }

            if (isset($siteIdsByLocale[$settings['locale']])) {
                $settings['siteId'] = $siteIdsByLocale[$settings['locale']];
            }

            unset($settings['locale']);

            $this->update(Table::WIDGETS, ['settings' => Json::encode($settings)], ['id' => $result['id']]);
        }
    }

    /**
     * Creates a new siteId column and migrates the locale data over
     *
     * @param string $table
     * @param string $column
     * @param bool $isNotNull
     * @param string $localeColumn
     */
    protected function addSiteColumn(string $table, string $column, bool $isNotNull, string $localeColumn)
    {
        // Ignore NOT NULL for now
        $type = $this->integer()->after($localeColumn);
        $this->addColumn($table, $column, $type);

        // Set the values
        $this->update($table, [
            $column => new Expression(str_replace('%', "[[{$localeColumn}]]", $this->caseSql))
        ], '', [], false);

        // In case there were any referenced locales that no longer exist.
        if ($table === Table::SEARCHINDEX) {
            $this->delete($table, ['siteId' => null]);
        }

        if ($isNotNull) {
            $this->alterColumn($table, $column, $type->notNull());
        }
    }

    /**
     * Returns a site handle based on a given locale.
     *
     * @param string $locale
     * @return string
     */
    protected function locale2handle(string $locale): string
    {
        // Make sure it's a valid handle
        if (!preg_match('/^' . HandleValidator::$handlePattern . '$/', $locale) || in_array(strtolower($locale), HandleValidator::$baseReservedWords, true)) {
            $localeParts = array_filter(preg_split('/[^a-zA-Z0-9]/', $locale));

            // Prefix with a random string so there's no chance of a conflict with other locales
            return StringHelper::randomString(7) . ($localeParts ? '_' . implode('_', $localeParts) : '');
        }

        return $locale;
    }

    /**
     * Returns a language code based on a given locale.
     *
     * @param string $locale
     * @return string
     */
    protected function locale2language(string $locale): string
    {
        $foundMatch = false;

        // Get the individual words
        $localeParts = array_filter(preg_split('/[^a-zA-Z]+/', $locale));

        if (!empty($localeParts)) {
            $language = $localeParts[0] . (isset($localeParts[1]) ? '-' . strtoupper($localeParts[1]) : '');
            $allLanguages = Craft::$app->getI18n()->getAllLocaleIds();

            if (in_array($language, $allLanguages, true)) {
                $foundMatch = true;
            } else {
                // Find the closest one
                foreach ($allLanguages as $testLanguage) {
                    if (StringHelper::startsWith($testLanguage, $language)) {
                        $language = $testLanguage;
                        $foundMatch = true;
                        break;
                    }
                }
                if (!$foundMatch) {
                    foreach ($allLanguages as $testLanguage) {
                        if (StringHelper::startsWith($testLanguage, $localeParts[0])) {
                            $language = $testLanguage;
                            $foundMatch = true;
                            break;
                        }
                    }
                }
            }
        }

        if (!$foundMatch) {
            $language = 'en-US';
        }

        /** @noinspection PhpUndefinedVariableInspection */
        return $language;
    }
}
