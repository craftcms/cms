<?php

namespace craft\migrations;

use craft\db\Migration;

/**
 * m180202_185551_fix_focal_points migration.
 */
class m180202_185551_fix_focal_points extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // 3.0.0-RC8 introduced a bug where images would get a 50%-50% focal
        // point. Technically that's also the position you get by default when
        // you click the Focal Point button and don't change it, so it's
        // possible we're nulling out some intended focal points here, but meh.
        $this->update('{{%assets}}', ['focalPoint' => null], ['focalPoint' => '0.5000;0.5000'], [], false);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180202_185551_fix_focal_points cannot be reverted.\n";
        return false;
    }
}
