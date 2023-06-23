<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Entry;
use craft\fields\Matrix;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\services\ProjectConfig;
use yii\helpers\Inflector;

/**
 * m230617_070415_entrify_matrix_blocks migration.
 */
class m230617_070415_entrify_matrix_blocks extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // update the schema
        if ($this->db->getIsPgsql()) {
            $this->execute(sprintf('alter table %s alter column [[sectionId]] drop not null', Table::ENTRIES));
        } else {
            $this->alterColumn(Table::ENTRIES, 'sectionId', $this->integer());
        }

        $this->addColumn(Table::ENTRIES, 'primaryOwnerId', $this->integer()->after('parentId'));
        $this->addColumn(Table::ENTRIES, 'fieldId', $this->integer()->after('primaryOwnerId'));
        $this->addColumn(Table::ENTRIES, 'deletedWithOwner', $this->boolean()->null()->after('deletedWithEntryType'));

        $this->createTable(Table::ENTRIES_OWNERS, [
            'entryId' => $this->integer()->notNull(),
            'ownerId' => $this->integer()->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned()->notNull(),
            'PRIMARY KEY([[entryId]], [[ownerId]])',
        ]);

        $this->createIndex(null, Table::ENTRIES, ['primaryOwnerId'], false);
        $this->createIndex(null, Table::ENTRIES, ['fieldId'], false);

        $this->addForeignKey(null, Table::ENTRIES, ['fieldId'], Table::FIELDS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ENTRIES, ['primaryOwnerId'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ENTRIES_OWNERS, ['entryId'], Table::ENTRIES, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ENTRIES_OWNERS, ['ownerId'], Table::ELEMENTS, ['id'], 'CASCADE', null);

        $projectConfig = Craft::$app->getProjectConfig();
        $fieldsService = Craft::$app->getFields();

        // index entry type names and handles
        $entryTypeNames = [];
        $entryTypeHandles = [];
        foreach ($projectConfig->get(ProjectConfig::PATH_ENTRY_TYPES) ?? [] as $entryTypeConfig) {
            $entryTypeNames[$entryTypeConfig['name']] = true;
            $entryTypeHandles[$entryTypeConfig['handle']] = true;
        }

        // index global field names and handles
        $fieldNames = [];
        $fieldHandles = [];
        foreach ($projectConfig->get(ProjectConfig::PATH_FIELDS) ?? [] as $fieldConfig) {
            $fieldNames[$fieldConfig['name']] = true;
            $fieldNames[$fieldConfig['handle']] = true;
        }

        // get all the block type configs, grouped by field
        $blockTypeConfigsByField = [];
        foreach ($projectConfig->get('matrixBlockTypes') ?? [] as $uid => $config) {
            $blockTypeConfigsByField[$config['field']][$uid] = $config;
        }

        // find all the Matrix field configs
        $fieldConfigs = $projectConfig->find(
            fn(array $config) => ($config['type'] ?? null) === 'craft\fields\Matrix',
        );

        $newEntryTypes = [];

        foreach ($fieldConfigs as $fieldPath => $fieldConfig) {
            $fieldUid = ArrayHelper::lastValue(explode('.', $fieldPath));

            foreach ($blockTypeConfigsByField[$fieldUid] ?? [] as $blockTypeUid => $blockTypeConfig) {
                $entryType = $newEntryTypes[] = new EntryType([
                    'uid' => $blockTypeUid,
                    'name' => $this->uniqueName($blockTypeConfig['name'], $entryTypeNames),
                    'handle' => $this->uniqueHandle($blockTypeConfig['handle'], $entryTypeHandles),
                    'hasTitleField' => false,
                    'titleFormat' => '{id}',
                ]);

                $fieldLayoutUid = ArrayHelper::firstKey($blockTypeConfig['fieldLayouts'] ?? []);
                $fieldLayout = $fieldLayoutUid ? $fieldsService->getLayoutByUid($fieldLayoutUid) : new FieldLayout();
                $fieldLayout->type = Entry::class;
                $entryType->setFieldLayout($fieldLayout);

                foreach ($fieldLayout?->getCustomFieldElements() ?? [] as $layoutElement) {
                    $subField = $layoutElement->getField();

                    // Set a unique name & label, and preserve the originals if needed
                    $layoutElement->label = $subField->name;
                    $subField->name = $this->uniqueName(sprintf(
                        '%s - %s - %s',
                        $fieldConfig['name'],
                        $blockTypeConfig['name'],
                        $subField->name !== '__blank__' ? $subField->name : Inflector::camel2words($subField->handle),
                    ), $fieldNames);

                    $originalHandle = $subField->handle;
                    $subField->handle = $this->uniqueHandle($subField->handle, $fieldHandles);
                    if ($subField->handle !== $originalHandle) {
                        $layoutElement->handle = $originalHandle;
                    }

                    $muteEvents = $projectConfig->muteEvents;
                    $projectConfig->muteEvents = true;
                    $projectConfig->set(
                        sprintf('%s.%s', ProjectConfig::PATH_FIELDS, $subField->uid),
                        $fieldsService->createFieldConfig($subField),
                    );
                    $projectConfig->muteEvents = $muteEvents;

                    $this->update(Table::FIELDS, [
                        'name' => $subField->name,
                        'handle' => $subField->handle,
                        'context' => 'global',
                    ], [
                        'uid' => $subField->uid,
                    ], updateTimestamp: false);
                }
            }

            // update the field config
            $fieldConfig['settings'] += [
                'maxEntries' => ArrayHelper::remove($fieldConfig['settings'], 'maxBlocks'),
                'minEntries' => ArrayHelper::remove($fieldConfig['settings'], 'minBlocks'),
                'entryTypes' => array_map(fn(EntryType $entryType) => $entryType->uid, $newEntryTypes),
            ];
            unset($fieldConfig['settings']['contentTable']);

            $muteEvents = $projectConfig->muteEvents;
            $projectConfig->muteEvents = true;
            $projectConfig->set($fieldPath, $fieldConfig);
            $projectConfig->muteEvents = $muteEvents;

            $this->update(Table::FIELDS, [
                'settings' => Json::encode($fieldConfig['settings']),
            ], [
                'uid' => $fieldUid,
            ], updateTimestamp: false);
        }

        // save the new entry types
        $entriesServices = Craft::$app->getEntries();
        $typeIdMap = [];

        $oldIds = (new Query())
            ->select(['uid', 'id'])
            ->from('{{%matrixblocktypes}}')
            ->pairs();

        foreach ($newEntryTypes as $entryType) {
            $entriesServices->saveEntryType($entryType, false);
            if (isset($oldIds[$entryType->uid])) {
                $typeIdMap[$oldIds[$entryType->uid]] = $entryType->id;
            }
        }

        if (!empty($typeIdMap)) {
            // entrify the Matrix blocks
            $typeIdSql = 'CASE';
            foreach ($typeIdMap as $oldId => $newId) {
                $typeIdSql .= " WHEN [[typeId]] = $oldId THEN $newId";
            }
            $typeIdSql .= " END";
            $this->execute(sprintf(
                <<<SQL
INSERT INTO %s ([[id]], [[primaryOwnerId]], [[fieldId]], [[typeId]], [[postDate]], [[deletedWithOwner]], [[dateCreated]], [[dateUpdated]])
SELECT [[id]], [[primaryOwnerId]], [[fieldId]], %s, [[dateCreated]], [[deletedWithOwner]], [[dateCreated]], [[dateUpdated]]
FROM %s matrixblocks
WHERE [[matrixblocks.typeId]] IN (%s)
SQL,
                Table::ENTRIES,
                $typeIdSql,
                '{{%matrixblocks}}',
                implode(',', array_keys($typeIdMap)),
            ));

            $this->execute(sprintf(
                <<<SQL
INSERT INTO %s
SELECT * FROM %s
SQL,
                Table::ENTRIES_OWNERS,
                '{{%matrixblocks_owners}}',
            ));

            $this->update(
                Table::ELEMENTS,
                ['type' => Entry::class],
                ['type' => 'craft\elements\MatrixBlock'],
                updateTimestamp: false,
            );

            if ($this->db->getIsMysql()) {
                $this->execute(sprintf(
                    <<<SQL
UPDATE %s AS [[elements_sites]]
INNER JOIN %s AS [[entries]] ON [[entries.id]] = [[elements_sites.elementId]]
SET [[elements_sites.title]] = [[elements_sites.elementId]]
WHERE [[elements_sites.title]] IS NULL 
SQL,
                    Table::ELEMENTS_SITES,
                    Table::ENTRIES,
                ));
            } else {
                $this->execute(sprintf(
                    <<<SQL
UPDATE %s AS [[elements_sites]] 
SET [[title]] = [[elementId]]
FROM %s AS [[entries]]
WHERE [[entries.id]] = [[elements_sites.elementId]] AND
[[elements_sites.title]] IS NULL
SQL,
                    Table::ELEMENTS_SITES,
                    Table::ENTRIES,
                ));
            }
        }

        // drop the old Matrix tables
        $this->dropAllForeignKeysToTable('{{%matrixblocks_owners}}');
        $this->dropAllForeignKeysToTable('{{%matrixblocks}}');
        $this->dropAllForeignKeysToTable('{{%matrixblocktypes}}');
        $this->dropTable('{{%matrixblocks_owners}}');
        $this->dropTable('{{%matrixblocks}}');
        $this->dropTable('{{%matrixblocktypes}}');

        $contentTablePrefix = Craft::$app->getDb()->getSchema()->getRawTableName('{{%matrixcontent_}}');
        foreach ($this->db->getSchema()->getTableNames() as $table) {
            if (str_starts_with($table, $contentTablePrefix)) {
                $this->dropTable($table);
            }
        }

        $fieldsService->refreshFields();

        return true;
    }

    private function uniqueName(string $name, array &$names): string
    {
        $i = 1;
        do {
            $test = $name . ($i !== 1 ? " $i" : '');
            if (!isset($names[$test])) {
                $names[$test] = true;
                return $test;
            }
            $i++;
        } while (true);
    }

    private function uniqueHandle(string $handle, array &$handles): string
    {
        $i = 1;
        do {
            $test = $handle . ($i !== 1 ? $i : '');
            if (!isset($handles[$test])) {
                $handles[$test] = true;
                return $test;
            }
            $i++;
        } while (true);
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m230617_070415_entrify_matrix_blocks cannot be reverted.\n";
        return false;
    }
}
