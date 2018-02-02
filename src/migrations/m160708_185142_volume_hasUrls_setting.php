<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;
use yii\helpers\Json;

/**
 * m150403_185142_volumes migration.
 */
class m160708_185142_volume_hasUrls_setting extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->db->columnExists('{{%volumes}}', 'hasUrls')) {
            $this->addColumn('{{%volumes}}', 'hasUrls', $this->boolean()->after('type')->defaultValue(false));
        }

        // Update all Volumes and move the setting from settings column to it's own field
        $volumes = (new Query())
            ->select(['id', 'settings'])
            ->from(['{{%volumes}}'])
            ->all($this->db);

        foreach ($volumes as $volume) {
            $settings = Json::decode($volume['settings']);
            $data = [];

            if (!empty($settings['publicURLs'])) {
                $data['hasUrls'] = true;
            }

            unset($settings['publicURLs']);
            $data['settings'] = Json::encode($settings);

            $this->update('{{%volumes}}', $data, ['id' => $volume['id']]);
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m160708_185142_volume_hasUrls_setting cannot be reverted.\n";

        return false;
    }
}
