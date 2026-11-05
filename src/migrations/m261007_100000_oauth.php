<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * Creates the OAuth tables.
 *
 * The tables follow Laravel Passport 13’s layout, so Craft 6 can adopt them (and the tokens in them) as-is. Like
 * Passport, there’s no resource column: a token belongs to the OAuth resource that defines its scopes, and the optional
 * `oauth_clients.scopes` column Passport supports limits which scopes (and therefore resources) a client can request.
 * Columns Craft 5 doesn’t use (`owner_*`, `secret`, `provider`, and `updated_at`) are there so Craft 6 can use them.
 */
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
            'id' => $this->char(36)->notNull(),
            'owner_type' => $this->string(),
            'owner_id' => $this->bigInteger(),
            'name' => $this->string()->notNull(),
            'secret' => $this->string(),
            'provider' => $this->string(),
            'redirect_uris' => $this->text()->notNull(),
            'grant_types' => $this->text()->notNull(),
            'scopes' => $this->text(),
            'revoked' => $this->boolean()->notNull(),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
            'PRIMARY KEY ([[id]])',
        ]);
        $this->createTable(Table::OAUTH_ACCESS_TOKENS, [
            'id' => $this->char(80)->notNull(),
            'user_id' => $this->integer(),
            'client_id' => $this->char(36)->notNull(),
            'name' => $this->string(),
            'scopes' => $this->text(),
            'revoked' => $this->boolean()->notNull(),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
            'expires_at' => $this->dateTime(),
            'PRIMARY KEY ([[id]])',
        ]);
        $this->createTable(Table::OAUTH_AUTH_CODES, [
            'id' => $this->char(80)->notNull(),
            'user_id' => $this->integer()->notNull(),
            'client_id' => $this->char(36)->notNull(),
            'scopes' => $this->text(),
            'revoked' => $this->boolean()->notNull(),
            'expires_at' => $this->dateTime(),
            'PRIMARY KEY ([[id]])',
        ]);
        $this->createTable(Table::OAUTH_REFRESH_TOKENS, [
            'id' => $this->char(80)->notNull(),
            'access_token_id' => $this->char(80)->notNull(),
            'revoked' => $this->boolean()->notNull(),
            'expires_at' => $this->dateTime(),
            'PRIMARY KEY ([[id]])',
        ]);

        $this->createIndex(null, Table::OAUTH_CLIENTS, ['owner_type', 'owner_id']);
        foreach ([Table::OAUTH_ACCESS_TOKENS, Table::OAUTH_AUTH_CODES] as $table) {
            $this->createIndex(null, $table, ['user_id']);
            $this->createIndex(null, $table, ['expires_at']);
            $this->createIndex(null, $table, ['client_id']);
            $this->addForeignKey(null, $table, ['user_id'], Table::USERS, ['id'], 'CASCADE');
            $this->addForeignKey(null, $table, ['client_id'], Table::OAUTH_CLIENTS, ['id'], 'CASCADE');
        }
        $this->createIndex(null, Table::OAUTH_REFRESH_TOKENS, ['access_token_id']);
        $this->createIndex(null, Table::OAUTH_REFRESH_TOKENS, ['expires_at']);
        $this->addForeignKey(null, Table::OAUTH_REFRESH_TOKENS, ['access_token_id'], Table::OAUTH_ACCESS_TOKENS, ['id'], 'CASCADE');
    }

    public function safeDown(): bool
    {
        foreach ([Table::OAUTH_REFRESH_TOKENS, Table::OAUTH_AUTH_CODES, Table::OAUTH_ACCESS_TOKENS, Table::OAUTH_CLIENTS] as $table) {
            $this->dropTableIfExists($table);
        }
        return true;
    }
}
