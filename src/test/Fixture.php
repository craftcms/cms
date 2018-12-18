<?php
/**
 * Created by PhpStorm.
 * User: Giel Tettelaar
 * Date: 18/12/2018
 * Time: 11:40
 */

namespace craft\test;


use yii\test\ActiveFixture;

class Fixture extends ActiveFixture
{
    public function unload() {

        $table = $this->getTableSchema();
        foreach ($this->getData() as $toBeDeletedRow) {
            $this->db->createCommand()->delete($table->fullName, $toBeDeletedRow)->execute();
        }
    }
}