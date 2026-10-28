<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

class m261007_100000_oauth extends Migration
{
    public function safeUp(): bool
    {
        $this->createTables();
        return true;
    }

    public function createTables(): void
    {
        $this->createTable(Table::OAUTH_CLIENTS, [
            'resourceId' => $this->string(64)->notNull(),
            'id' => $this->string(36)->notNull(),
            'name' => $this->string()->notNull(),
            'redirectUris' => $this->text()->notNull(),
            'revoked' => $this->boolean()->notNull()->defaultValue(false),
            'dateCreated' => $this->dateTime()->notNull(),
            'PRIMARY KEY ([[id]])',
        ]);
        $this->createTable(Table::OAUTH_ACCESS_TOKENS, [
            'resourceId' => $this->string(64)->notNull(),
            'id' => $this->string(100)->notNull(),
            'clientId' => $this->string(36)->notNull(),
            'userId' => $this->integer()->notNull(),
            'scopes' => $this->text()->notNull(),
            'revoked' => $this->boolean()->notNull()->defaultValue(false),
            'expiresAt' => $this->dateTime()->notNull(),
            'PRIMARY KEY ([[id]])',
        ]);
        $this->createTable(Table::OAUTH_AUTH_CODES, [
            'resourceId' => $this->string(64)->notNull(),
            'id' => $this->string(100)->notNull(),
            'clientId' => $this->string(36)->notNull(),
            'userId' => $this->integer()->notNull(),
            'revoked' => $this->boolean()->notNull()->defaultValue(false),
            'expiresAt' => $this->dateTime()->notNull(),
            'PRIMARY KEY ([[id]])',
        ]);
        $this->createTable(Table::OAUTH_REFRESH_TOKENS, [
            'id' => $this->string(100)->notNull(),
            'accessTokenId' => $this->string(100)->notNull(),
            'revoked' => $this->boolean()->notNull()->defaultValue(false),
            'expiresAt' => $this->dateTime()->notNull(),
            'PRIMARY KEY ([[id]])',
        ]);

        foreach ([Table::OAUTH_ACCESS_TOKENS, Table::OAUTH_AUTH_CODES] as $table) {
            $this->addForeignKey(null, $table, ['userId'], Table::USERS, ['id'], 'CASCADE');
            $this->createIndex(null, $table, ['expiresAt']);
        }
        $this->createResourceIndexes();
        $this->addForeignKey(null, Table::OAUTH_REFRESH_TOKENS, ['accessTokenId'], Table::OAUTH_ACCESS_TOKENS, ['id'], 'CASCADE');
        $this->createIndex(null, Table::OAUTH_REFRESH_TOKENS, ['expiresAt']);
    }

    private function createResourceIndexes(): void
    {
        $this->createIndex(null, Table::OAUTH_CLIENTS, ['resourceId', 'id'], true);

        foreach ([Table::OAUTH_ACCESS_TOKENS, Table::OAUTH_AUTH_CODES] as $table) {
            $this->createIndex(null, $table, ['resourceId', 'clientId']);
            $this->createIndex(null, $table, ['resourceId', 'userId']);
            $this->addForeignKey(null, $table, ['resourceId', 'clientId'], Table::OAUTH_CLIENTS, ['resourceId', 'id'], 'CASCADE');
        }
    }

    public function safeDown(): bool
    {
        foreach ([Table::OAUTH_REFRESH_TOKENS, Table::OAUTH_AUTH_CODES, Table::OAUTH_ACCESS_TOKENS, Table::OAUTH_CLIENTS] as $table) {
            $this->dropTableIfExists($table);
        }
        return true;
    }
}
