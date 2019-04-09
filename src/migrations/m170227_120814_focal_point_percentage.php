<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;

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
        $focalPointColumn = Craft::$app->getDb()->quoteColumnName('focalPoint');

        $assets = (new Query())
            ->select(['id', 'width', 'height', 'focalPoint'])
            ->from([Table::ASSETS])
            ->where($focalPointColumn . ' IS NOT NULL')
            ->all($this->db);

        // Alter columns
        $this->alterColumn(Table::ASSETS, 'focalPoint', $this->string(13));

        // Convert to percentage
        foreach ($assets as $asset) {
            $focal = explode(',', $asset['focalPoint']);
            if (count($focal) === 2) {
                $x = number_format($focal[0] / $asset['width'], 4);
                $y = number_format($focal[1] / $asset['height'], 4);
                $this->update(Table::ASSETS, ['focalPoint' => $x . ';' . $y], ['id' => $asset['id']]);
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
