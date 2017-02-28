<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\fields\PlainText;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use yii\db\Schema;

/**
 * m170227_120814_focal_point_percentage migration.
 */
class m170227_120814_focal_point_percentage extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Fetch all Assets with focal points
        $assets = (new Query())
            ->select(['id', 'width', 'height', 'focalPoint'])
            ->from(['{{%assets}}'])
            ->where('focalPoint IS NOT NULL')
            ->all();

        // Alter columns
        $this->alterColumn('{{%assets}}', 'focalPoint', $this->string(13)->null());

        // Convert to percentage
        foreach ($assets as $asset) {
            $focal = explode(",", $asset['focalPoint']);
            if (count($focal) === 2) {
                $x = number_format($focal[0] / $asset['width'], 4);
                $y = number_format($focal[1] / $asset['height'], 4);
                $this->update('{{%assets}}', ['focalPoint' => $x.';'.$y], ['id' => $asset['id']]);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m170227_120814_focal_point_percentage cannot be reverted.\n";
        return false;
    }
}
