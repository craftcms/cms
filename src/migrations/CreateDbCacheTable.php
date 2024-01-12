<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * CreateDbCacheTable Migration
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.14
 */
class CreateDbCacheTable extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if ($this->db->getIsMysql()) {
            $dataType = 'longblob';
        } else {
            $dataType = $this->binary();
        }

        $this->createTable(Table::CACHE, [
            'id' => $this->string(128)->notNull(),
            'expire' => $this->integer(11),
            'data' => $dataType,
            'PRIMARY KEY([[id]])',
        ]);

        $this->createIndex(null, Table::CACHE, ['expire']);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropTableIfExists(Table::CACHE);
        return true;
    }
}
