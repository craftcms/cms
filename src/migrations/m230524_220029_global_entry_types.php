<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\ArrayHelper;
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

        foreach ($entryTypeConfigs as $entryTypeUid => &$entryTypeConfig) {
            $entryTypePath = sprintf('%s.%s', ProjectConfig::PATH_ENTRY_TYPES, $entryTypeUid);
            $sectionUid = ArrayHelper::remove($entryTypeConfig, 'section');
            if (!$sectionUid || !isset($sectionConfigs[$sectionUid])) {
                $projectConfig->remove($entryTypePath);
                continue;
            }
            $sectionConfigs[$sectionUid]['entryTypes'][] = $entryTypeUid;
            $projectConfig->set($entryTypePath, $entryTypeConfig);
        }
        unset($entryTypeConfig);

        foreach ($sectionConfigs as $sectionUid => $sectionConfig) {
            if (!empty($sectionConfig['entryTypes'])) {
                $sectionPath = sprintf('%s.%s', ProjectConfig::PATH_SECTIONS, $sectionUid);
                $projectConfig->set($sectionPath, $sectionConfig);
            }
        }

        $projectConfig->muteEvents = $muteEvents;

        // check for duplicate entry type names/handles
        $entryTypeNames = [];
        $entryTypeHandles = [];
        foreach ($entryTypeConfigs as $entryTypeUid => $entryTypeConfig) {
            if (
                isset($entryTypeNames[$entryTypeConfig['name']]) ||
                isset($entryTypeHandles[$entryTypeConfig['handle']])
            ) {
                // find the section that was using it
                $sectionConfig = ArrayHelper::firstWhere(
                    $sectionConfigs,
                    fn(array $config) => in_array($entryTypeUid, $config['entryTypes'] ?? [])
                );

                if (isset($entryTypeNames[$entryTypeConfig['name']])) {
                    $baseName = sprintf('%s - %s', $sectionConfig['name'], $entryTypeConfig['name']);
                    $i = 1;
                    do {
                        $entryTypeConfig['name'] = $baseName;
                        if ($i !== 1) {
                            $entryTypeConfig['name'] .= " $i";
                        }
                        $i++;
                    } while (isset($entryTypeNames[$entryTypeConfig['name']]));
                }

                if (isset($entryTypeHandles[$entryTypeConfig['handle']])) {
                    $baseHandle = sprintf('%s_%s', $sectionConfig['handle'], $entryTypeConfig['handle']);
                    $i = 1;
                    do {
                        $entryTypeConfig['handle'] = $baseHandle;
                        if ($i !== 1) {
                            $entryTypeConfig['handle'] .= $i;
                        }
                        $i++;
                    } while (isset($entryTypeHandles[$entryTypeConfig['handle']]));
                }

                $entryTypePath = sprintf('%s.%s', ProjectConfig::PATH_ENTRY_TYPES, $entryTypeUid);
                $projectConfig->set($entryTypePath, $entryTypeConfig);
            }

            $entryTypeNames[$entryTypeConfig['name']] = true;
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
                foreach ($sectionConfig['entryTypes'] as $entryTypeUid) {
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
