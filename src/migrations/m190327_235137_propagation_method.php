<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;
use craft\helpers\ArrayHelper;
use craft\models\Section;

/**
 * m190327_235137_propagation_method migration.
 */
class m190327_235137_propagation_method extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn(Table::SECTIONS, 'propagationMethod', $this->string()->defaultValue(Section::PROPAGATION_METHOD_ALL)->after('enableVersioning'));

        // Don't make the same config changes twice
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);

        if (version_compare($schemaVersion, '3.2.0', '<')) {
            $projectConfig->muteEvents = true;
            foreach ($projectConfig->get('sections') ?? [] as $uid => $section) {
                $newVal = ArrayHelper::remove($section, 'propagateEntries') ? Section::PROPAGATION_METHOD_ALL : Section::PROPAGATION_METHOD_NONE;
                $section['propagationMethod'] = $newVal;
                $projectConfig->set('sections.' . $uid, $section);
                $this->update(Table::SECTIONS, ['propagationMethod' => $newVal], ['uid' => $uid], [], false);
            }
            $projectConfig->muteEvents = false;
        } else {
            foreach ($projectConfig->get('sections', true) ?? [] as $uid => $section) {
                $this->update(Table::SECTIONS, [
                    'propagationMethod' => $section['propagationMethod'] ?? Section::PROPAGATION_METHOD_ALL,
                ], ['uid' => $uid], [], false);
            }
        }

        if ($this->db->getIsPgsql()) {
            $this->execute('alter table ' . Table::SECTIONS . ' alter column [[propagationMethod]] set not null');
        } else {
            $this->alterColumn(Table::SECTIONS, 'propagationMethod', $this->string()->defaultValue(Section::PROPAGATION_METHOD_ALL)->notNull());
        }

        $this->dropColumn(Table::SECTIONS, 'propagateEntries');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190327_235137_propagation_method cannot be reverted.\n";
        return false;
    }
}
