<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\services\ProjectConfig;
use Illuminate\Support\Collection;

/**
 * m230524_220029_global_entry_types migration.
 */
class m230524_220029_global_entry_types extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->dropTableIfExists(Table::SECTIONS_ENTRYTYPES);
        $this->createTable(Table::SECTIONS_ENTRYTYPES, [
            'sectionId' => $this->integer()->notNull(),
            'typeId' => $this->integer()->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned()->notNull(),
            'name' => $this->string(),
            'handle' => $this->string(),
            'PRIMARY KEY([[sectionId]], [[typeId]])',
        ]);
        $this->addForeignKey(null, Table::SECTIONS_ENTRYTYPES, ['sectionId'], Table::SECTIONS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::SECTIONS_ENTRYTYPES, ['typeId'], Table::ENTRYTYPES, ['id'], 'CASCADE', null);

        $data = (new Query())
            ->select(['id', 'sectionId', 'sortOrder'])
            ->from(Table::ENTRYTYPES)
            ->all();

        // add sections_entrytypes rows
        $this->batchInsert(
            Table::SECTIONS_ENTRYTYPES,
            ['sectionId', 'typeId', 'sortOrder'],
            Collection::make($data)->map(fn(array $row, int $i) => [
                $row['sectionId'],
                $row['id'],
                $i + 1,
            ])->all(),
        );

        $this->dropForeignKeyIfExists(Table::ENTRYTYPES, ['sectionId']);
        $this->dropIndexIfExists(Table::ENTRYTYPES, ['name', 'sectionId'], false);
        $this->dropIndexIfExists(Table::ENTRYTYPES, ['handle', 'sectionId'], false);
        $this->dropIndexIfExists(Table::ENTRYTYPES, ['sectionId'], false);
        $this->dropColumn(Table::ENTRYTYPES, 'sectionId');
        $this->dropColumn(Table::ENTRYTYPES, 'sortOrder');

        // restructure the project config data
        $projectConfig = Craft::$app->getProjectConfig();
        $muteEvents = $projectConfig->muteEvents;
        $projectConfig->muteEvents = true;
        $entryTypeConfigs = $projectConfig->get(ProjectConfig::PATH_ENTRY_TYPES) ?? [];
        $sectionConfigs = $projectConfig->get(ProjectConfig::PATH_SECTIONS) ?? [];
        $sectionUidsByEntryTypeUid = [];

        foreach ($entryTypeConfigs as $entryTypeUid => &$entryTypeConfig) {
            $entryTypePath = sprintf('%s.%s', ProjectConfig::PATH_ENTRY_TYPES, $entryTypeUid);
            $sectionUid = ArrayHelper::remove($entryTypeConfig, 'section');
            if (!$sectionUid || !isset($sectionConfigs[$sectionUid])) {
                $projectConfig->remove($entryTypePath);
                continue;
            }
            $sectionConfigs[$sectionUid]['entryTypes'][] = ['uid' => $entryTypeUid];
            $projectConfig->set($entryTypePath, $entryTypeConfig);
            $sectionUidsByEntryTypeUid[$entryTypeUid] = $sectionUid;
        }
        unset($entryTypeConfig);

        foreach ($sectionConfigs as $sectionUid => $sectionConfig) {
            if (!empty($sectionConfig['entryTypes'])) {
                $sectionPath = sprintf('%s.%s', ProjectConfig::PATH_SECTIONS, $sectionUid);
                $projectConfig->set($sectionPath, $sectionConfig);
            }
        }

        $projectConfig->muteEvents = $muteEvents;

        // check for duplicate entry type handles
        $entryTypeHandles = [];
        foreach ($entryTypeConfigs as $entryTypeUid => $entryTypeConfig) {
            if (isset($entryTypeHandles[$entryTypeConfig['handle']])) {
                $originalHandle = $entryTypeConfig['handle'];

                // find the section that was using it
                $sectionConfig = ArrayHelper::firstWhere(
                    $sectionConfigs,
                    fn(array $config) => ArrayHelper::contains(
                        $config['entryTypes'] ?? [],
                        fn(array $entryType) => $entryType['uid'] === $entryTypeUid,
                    ),
                );

                $baseHandle = sprintf('%s_%s', $sectionConfig['handle'], $entryTypeConfig['handle']);
                $i = 1;
                do {
                    $entryTypeConfig['handle'] = $baseHandle;
                    if ($i !== 1) {
                        $entryTypeConfig['handle'] .= $i;
                    }
                    $i++;
                } while (isset($entryTypeHandles[$entryTypeConfig['handle']]));

                $entryTypePath = sprintf('%s.%s', ProjectConfig::PATH_ENTRY_TYPES, $entryTypeUid);
                $projectConfig->set($entryTypePath, $entryTypeConfig);

                // Preserve the original handle on the section
                $entryTypeId = Db::idByUid(Table::ENTRYTYPES, $entryTypeUid);
                if ($entryTypeId) {
                    $this->update(Table::SECTIONS_ENTRYTYPES, [
                        'handle' => $originalHandle,
                    ], ['typeId' => $entryTypeId]);
                }

                $sectionEntryTypesPath = sprintf(
                    '%s.%s.entryTypes',
                    ProjectConfig::PATH_SECTIONS,
                    $sectionUidsByEntryTypeUid[$entryTypeUid],
                );
                $projectConfig->set($sectionEntryTypesPath, array_map(
                    function(array $config) use ($entryTypeUid, $originalHandle) {
                        if ($config['uid'] === $entryTypeUid) {
                            $config['handle'] = $originalHandle;
                        }
                        return $config;
                    },
                    $projectConfig->get($sectionEntryTypesPath),
                ));
            }

            $entryTypeHandles[$entryTypeConfig['handle']] = true;
        }

        // update GraphQL schemas
        $actions = ['read', 'create', 'save', 'delete'];
        foreach ($projectConfig->get(ProjectConfig::PATH_GRAPHQL_SCHEMAS) ?? [] as $schemaUid => $schemaConfig) {
            if (empty($schemaConfig['scope'])) {
                continue;
            }

            $scope = array_flip(array_map('strtolower', $schemaConfig['scope']));

            foreach ($sectionConfigs as $sectionUid => $sectionConfig) {
                if (empty($sectionConfig['entryTypes'])) {
                    continue;
                }

                // unset the section's `read` component initially. We'll add it back if all entry types have it too
                unset($scope["sections.$sectionUid:read"]);

                $can = array_combine($actions, array_map(fn() => true, $actions));
                foreach ($sectionConfig['entryTypes'] as $entryType) {
                    $entryTypeUid = $entryType['uid'];

                    // unset the entry type's `edit` component because it's pointless
                    unset($scope["entrytypes.$entryTypeUid:edit"]);

                    foreach ($actions as $action) {
                        if (isset($scope["entrytypes.$entryTypeUid:$action"])) {
                            unset($scope["entrytypes.$entryTypeUid:$action"]);
                        } else {
                            $can[$action] = false;
                        }
                    }
                }
                foreach ($actions as $action) {
                    if ($can[$action]) {
                        $scope["sections.$sectionUid:$action"] = true;
                    }
                }
            }
            $schemaConfig['scope'] = array_keys($scope);
            $schemaPath = sprintf('%s.%s', ProjectConfig::PATH_GRAPHQL_SCHEMAS, $schemaUid);
            $projectConfig->set($schemaPath, $schemaConfig);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m230524_220029_global_entry_types cannot be reverted.\n";
        return false;
    }
}
