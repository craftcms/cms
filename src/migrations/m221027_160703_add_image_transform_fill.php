<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;

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
        $allowUpscale = Craft::$app->getConfig()->getGeneral()->upscaleImages;
        $this->addColumn(Table::IMAGETRANSFORMS, 'upscale', $this->boolean()->notNull()->defaultValue($allowUpscale)->after('fill'));

        $values = ['stretch', 'fit', 'crop', 'letterbox'];
        if ($this->db->getIsPgsql()) {
            // Manually construct the SQL for Postgres
            $check = '[[mode]] in (';
            foreach ($values as $i => $value) {
                if ($i != 0) {
                    $check .= ',';
                }
                $check .= $this->db->quoteValue($value);
            }
            $check .= ')';
            $this->execute("alter table {{%imagetransforms}} drop constraint {{%imagetransforms_mode_check}}, add check ({$check})");
        } else {
            $this->alterColumn(
                Table::IMAGETRANSFORMS,
                'mode',
                $this->enum('mode', ['stretch', 'fit', 'crop', 'letterbox'])->notNull()->defaultValue('crop'),
            );
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
