<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\migrations;

use craft\app\db\Migration;

/**
 * CreateMatrixContentTable Migration
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class CreateMatrixContentTable extends Migration
{
    // Properties
    // =========================================================================

    /**
     * @var string The table name
     */
    public $tableName;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable($this->tableName, [
            'id' => $this->primaryKey(),
            'elementId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex($this->db->getIndexName($this->tableName, 'elementId,siteId'), $this->tableName, 'elementId,siteId', true);
        $this->addForeignKey($this->db->getForeignKeyName($this->tableName, 'elementId'), $this->tableName, 'elementId', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName($this->tableName, 'siteId'), $this->tableName, 'siteId', '{{%sites}}', 'id', 'CASCADE', 'CASCADE');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        return false;
    }
}
