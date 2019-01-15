<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m150724_140822_adjust_quality_settings extends Migration
{
    /**
     * Any migration code in here is wrapped inside of a transaction.
     *
     * @return bool
     */
    public function safeUp(): bool
    {
        $transforms = (new Query())
            ->select(['id', 'quality'])
            ->from([Table::ASSETTRANSFORMS])
            ->all($this->db);

        foreach ($transforms as $transform) {
            $quality = $transform['quality'];

            if (!$quality) {
                continue;
            }

            $closest = 0;
            $closestDistance = 100;
            $qualityLevels = [10, 30, 60, 82, 100];

            foreach ($qualityLevels as $qualityLevel) {
                if (abs($quality - $qualityLevel) <= $closestDistance) {
                    $closest = $qualityLevel;
                    $closestDistance = abs($quality - $qualityLevel);
                }
            }

            $this->update(Table::ASSETTRANSFORMS, ['quality' => $closest],
                'id = :id', [':id' => $transform['id']]);
        }

        return true;
    }
}
