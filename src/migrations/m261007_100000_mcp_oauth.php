<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;
use craft\mcp\oauth\Repositories;

class m261007_100000_mcp_oauth extends Migration
{
    public function safeUp(): bool
    {
        $this->createTables();
        return true;
    }

    public function createTables(): void
    {
        $this->createTable(Repositories::CLIENTS, [
            'id' => $this->string(36)->notNull(),
            'name' => $this->string()->notNull(),
            'redirectUris' => $this->text()->notNull(),
            'revoked' => $this->boolean()->notNull()->defaultValue(false),
            'dateCreated' => $this->dateTime()->notNull(),
            'PRIMARY KEY ([[id]])',
        ]);
        $this->createTable(Repositories::TOKENS, [
            'id' => $this->string(100)->notNull(),
            'clientId' => $this->string(36)->notNull(),
            'userId' => $this->integer()->notNull(),
            'scopes' => $this->text()->notNull(),
            'revoked' => $this->boolean()->notNull()->defaultValue(false),
            'expiresAt' => $this->dateTime()->notNull(),
            'PRIMARY KEY ([[id]])',
        ]);
        $this->createTable(Repositories::CODES, [
            'id' => $this->string(100)->notNull(),
            'clientId' => $this->string(36)->notNull(),
            'userId' => $this->integer()->notNull(),
            'revoked' => $this->boolean()->notNull()->defaultValue(false),
            'expiresAt' => $this->dateTime()->notNull(),
            'PRIMARY KEY ([[id]])',
        ]);
        $this->createTable(Repositories::REFRESH, [
            'id' => $this->string(100)->notNull(),
            'accessTokenId' => $this->string(100)->notNull(),
            'revoked' => $this->boolean()->notNull()->defaultValue(false),
            'expiresAt' => $this->dateTime()->notNull(),
            'PRIMARY KEY ([[id]])',
        ]);

        foreach ([Repositories::TOKENS, Repositories::CODES] as $table) {
            $this->addForeignKey(null, $table, ['clientId'], Repositories::CLIENTS, ['id'], 'CASCADE');
            $this->addForeignKey(null, $table, ['userId'], Table::USERS, ['id'], 'CASCADE');
            $this->createIndex(null, $table, ['expiresAt']);
        }
        $this->addForeignKey(null, Repositories::REFRESH, ['accessTokenId'], Repositories::TOKENS, ['id'], 'CASCADE');
        $this->createIndex(null, Repositories::REFRESH, ['expiresAt']);
    }

    public function safeDown(): bool
    {
        foreach ([Repositories::REFRESH, Repositories::CODES, Repositories::TOKENS, Repositories::CLIENTS] as $table) {
            $this->dropTableIfExists($table);
        }
        return true;
    }
}
