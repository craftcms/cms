<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */


namespace craft\test;


use yii\test\ActiveFixture;

/**
 * Class Fixture.
 *
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.0
 */
class Fixture extends ActiveFixture
{
    public function unload() {

        $table = $this->getTableSchema();
        foreach ($this->getData() as $toBeDeletedRow) {
            $this->db->createCommand()->delete($table->fullName, $toBeDeletedRow)->execute();
        }
    }
}