<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;

/**
 * m161108_000000_new_version_format migration.
 */
class m161108_000000_new_version_format extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->db->columnExists(Table::INFO, 'build')) {
            // Migration has already run
            return true;
        }

        // Increase size of the version column
        $this->alterColumn(Table::INFO, 'version', $this->string(50)->notNull());

        // Get the existing version, build, and track
        $infoRow = (new Query())
            ->select(['version', 'build', 'track'])
            ->from([Table::INFO])
            ->one($this->db);

        // Update the version
        $version = $infoRow['version'];

        switch ($infoRow['track']) {
            case 'beta':
                $version .= '.0-beta.' . $infoRow['build'];
                break;
            case 'dev':
                $version .= '.0-dev.' . $infoRow['build'];
                break;
            default:
                $version .= '.' . $infoRow['build'];
        }

        $this->update(Table::INFO, ['version' => $version]);

        // Drop the unneeded columns
        $this->dropColumn(Table::INFO, 'build');
        $this->dropColumn(Table::INFO, 'releaseDate');
        $this->dropColumn(Table::INFO, 'track');

        // Update the info model
        $info = Craft::$app->getInfo();
        $info->version = $version;

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m161108_000000_new_version_format cannot be reverted.\n";

        return false;
    }
}
