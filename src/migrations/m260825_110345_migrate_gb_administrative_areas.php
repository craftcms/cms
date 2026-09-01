<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;

/**
 * m260825_110345_migrate_gb_administrative_areas migration.
 */
class m260825_110345_migrate_gb_administrative_areas extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $codesByName = array_flip(Craft::$app->getAddresses()->getSubdivisionRepository()->getList(['GB']));

        $renames = [
            'Bristol, City of' => 'City of Bristol',
            'Edinburgh, City of' => 'City of Edinburgh',
            'London, City of' => 'City of London',
            'Durham, County' => 'County Durham',
            'Vale of Glamorgan, The' => 'The Vale of Glamorgan',
        ];
        $codesByOldName = $codesByName;
        foreach ($renames as $oldName => $newName) {
            $codesByOldName[$oldName] = $codesByName[$newName];
        }

        $query = (new Query())
            ->select(['id', 'administrativeArea'])
            ->from(Table::ADDRESSES)
            ->where(['countryCode' => 'GB'])
            ->andWhere(['not', ['administrativeArea' => null]]);

        foreach ($query->each() as $row) {
            $code = $codesByOldName[$row['administrativeArea']] ?? null;
            if ($code !== null && $code !== $row['administrativeArea']) {
                $this->update(Table::ADDRESSES, ['administrativeArea' => $code], ['id' => $row['id']], updateTimestamp: false);
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m260825_110345_migrate_gb_administrative_areas cannot be reverted.\n";
        return false;
    }
}
