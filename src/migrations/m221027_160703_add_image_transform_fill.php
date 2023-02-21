<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;
use craft\services\ProjectConfig;
use yii\db\Exception;

/**
 * m221027_160703_add_image_transform_fill migration.
 */
class m221027_160703_add_image_transform_fill extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // Place migration code here...
        $this->addColumn(Table::IMAGETRANSFORMS, 'fill', $this->string(11)->null()->after('interlace'));
        $this->addColumn(Table::IMAGETRANSFORMS, 'upscale', $this->boolean()->notNull()->defaultValue(true)->after('fill'));

        $modeOptions = ['stretch', 'fit', 'crop', 'letterbox'];
        if ($this->db->getIsPgsql()) {
            // Manually construct the SQL for Postgres
            $check = '[[mode]] in (';
            foreach ($modeOptions as $i => $value) {
                if ($i !== 0) {
                    $check .= ',';
                }
                $check .= $this->db->quoteValue($value);
            }
            $check .= ')';
            $tryConstraints = [
                '{{%imagetransforms_mode_check}}',
                '{{%assettransforms_mode_check}}',
            ];
            foreach ($tryConstraints as $constraint) {
                try {
                    $sql = sprintf('alter table %s drop constraint %s, add check (%s)', Table::IMAGETRANSFORMS, $constraint, $check);
                    $this->execute($sql);
                    break;
                } catch (Exception) {
                    // try the next one...
                }
            }
        } else {
            $this->alterColumn(Table::IMAGETRANSFORMS, 'mode', $this->enum('mode', $modeOptions)->notNull()->defaultValue('crop'));
        }

        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);

        if (version_compare($schemaVersion, '4.4.0.3', '<')) {
            // Hard-code the existing transforms with the current upscaleImages config value
            $generalConfig = Craft::$app->getConfig()->getGeneral();
            $transforms = $projectConfig->get(ProjectConfig::PATH_IMAGE_TRANSFORMS) ?? [];
            foreach ($transforms as $uid => $config) {
                $config['upscale'] = $generalConfig->upscaleImages;
                $path = sprintf('%s.%s', ProjectConfig::PATH_IMAGE_TRANSFORMS, $uid);
                $projectConfig->set($path, $config);
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m221027_160703_add_image_transform_fill cannot be reverted.\n";
        return false;
    }
}
