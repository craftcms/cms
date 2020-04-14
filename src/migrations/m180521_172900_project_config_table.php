<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Json;
use craft\helpers\ProjectConfig;
use craft\helpers\StringHelper;

/**
 * m180521172900_project_config_table migration.
 */
class m180521_172900_project_config_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // In case this was run in a previous update attempt
        $this->dropTableIfExists(Table::PROJECTCONFIG);

        $this->createTable(Table::PROJECTCONFIG, [
            'path' => $this->string()->notNull(),
            'value' => $this->text()->notNull(),
            'PRIMARY KEY([[path]])',
        ]);

        // If this column exists, this means this migration is running on a site where you already have project config
        if ($this->db->columnExists(Table::INFO, 'config')) {
            // Load up the existing project config
            $config = (new Query())
                ->select(['config'])
                ->from([Table::INFO])
                ->scalar();

            if (!$config) {
                $config = [];
            } else {
                // Try to decode it in case it contains any 4+ byte characters
                $config = StringHelper::decdec($config);
                if ($config[0] === '{') {
                    $config = Json::decode($config);
                } else {
                    $config = unserialize($config, ['allowed_classes' => false]);
                }
            }

            $flatConfigData = [];

            ProjectConfig::flattenConfigArray($config, '', $flatConfigData);

            $batch = [];
            $counter = 0;
            $isMysql = $this->db->getIsMysql();

            // Batch by 100
            foreach ($flatConfigData as $key => $value) {
                $value = Json::encode($value);

                if (
                    !mb_check_encoding($value, 'UTF-8') ||
                    ($isMysql && StringHelper::containsMb4($value))
                ) {
                    $value = 'base64:' . base64_encode($value);
                }

                $batch[] = [$key, $value];

                if (++$counter == 100) {
                    $this->batchInsert(Table::PROJECTCONFIG, ['path', 'value'], $batch, false);
                    $batch = [];
                    $counter = 0;
                }
            }

            // Leftovers
            if (!empty($batch)) {
                $this->batchInsert(Table::PROJECTCONFIG, ['path', 'value'], $batch, false);
            }

            // Drop the old column
            $this->dropColumn(Table::INFO, 'config');
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180521172900_project_config_table cannot be reverted.\n";
        return false;
    }
}
