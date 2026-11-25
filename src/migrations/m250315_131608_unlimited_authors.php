<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;
use craft\services\ProjectConfig;

/**
 * m250315_131608_unlimited_authors migration.
 */
class m250315_131608_unlimited_authors extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if ($this->db->getIsPgsql()) {
            $this->execute(sprintf('alter table %s alter column [[maxAuthors]] drop default, alter column [[maxAuthors]] drop not null', Table::SECTIONS));
        } else {
            $this->alterColumn(Table::SECTIONS, 'maxAuthors', $this->smallInteger()->unsigned());
        }

        $projectConfig = Craft::$app->getProjectConfig();
        $muteEvents = $projectConfig->muteEvents;
        $projectConfig->muteEvents = true;

        $sectionConfigs = $projectConfig->get(ProjectConfig::PATH_SECTIONS) ?? [];
        foreach ($sectionConfigs as $uid => $config) {
            if (!isset($config['maxAuthors'])) {
                $config['maxAuthors'] = 1;
                $projectConfig->set(sprintf('%s.%s', ProjectConfig::PATH_SECTIONS, $uid), $config);
            }
        }

        $projectConfig->muteEvents = $muteEvents;

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        if ($this->db->getIsPgsql()) {
            $this->update(Table::SECTIONS, ['maxAuthors' => 1], ['maxAuthors' => null]);
            $this->execute(sprintf('alter table %s alter column [[maxAuthors]] set default 1, alter column [[maxAuthors]] set not null', Table::SECTIONS));
        } else {
            $this->alterColumn(Table::SECTIONS, 'maxAuthors', $this->smallInteger()->unsigned()->defaultValue(1)->notNull());
        }

        return true;
    }
}
