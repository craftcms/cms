<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * Creates the upload sessions table.
 *
 * The columns match Craft 6’s `uploadsessions` table, so Craft 6 can adopt the table when upgrading. Unknown filenames
 * are stored as empty strings and unknown sizes as `0`. `disk` holds the staging volume’s UID, or `temp` for the
 * temporary asset upload filesystem.
 */
class m261117_000000_upload_sessions extends Migration
{
    public function safeUp(): bool
    {
        $this->createTables();
        return true;
    }

    public function createTables(): void
    {
        $this->createTable(Table::UPLOADSESSIONS, [
            'id' => $this->char(36)->notNull(),
            'owner' => $this->string()->notNull(),
            'handler' => $this->string()->notNull(),
            'uploader' => $this->string()->notNull(),
            'disk' => $this->string()->notNull(),
            'filename' => $this->string()->notNull(),
            'size' => $this->bigInteger()->unsigned()->notNull(),
            'chunkSize' => $this->bigInteger()->unsigned()->notNull()->defaultValue(1),
            'parameters' => $this->json()->notNull(),
            'state' => $this->json()->notNull(),
            'result' => $this->json(),
            'expiresAt' => $this->dateTime()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'PRIMARY KEY ([[id]])',
        ]);
        $this->createIndex(null, Table::UPLOADSESSIONS, ['expiresAt']);
    }

    public function safeDown(): bool
    {
        echo "m261117_000000_upload_sessions cannot be reverted.\n";
        return false;
    }
}
