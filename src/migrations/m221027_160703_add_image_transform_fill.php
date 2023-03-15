<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;
use craft\services\ProjectConfig;

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
            // imagetransforms used to be assettransforms, so try dropping the constraint with both table names
            $this->execute(sprintf('alter table %s drop constraint if exists %s', Table::IMAGETRANSFORMS, '{{%imagetransforms_mode_check}}'));
            $this->execute(sprintf('alter table %s drop constraint if exists %s', Table::IMAGETRANSFORMS, '{{%assettransforms_mode_check}}'));

            $check = '[[mode]] in (';
            foreach ($modeOptions as $i => $value) {
                if ($i !== 0) {
                    $check .= ',';
                }
                $check .= $this->db->quoteValue($value);
            }
            $check .= ')';
            $this->execute(sprintf('alter table %s add check (%s)', Table::IMAGETRANSFORMS, $check));
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
