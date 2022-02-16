<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;
use craft\models\FieldLayoutTab;

/**
 * m220126_003432_addresses migration.
 */
class m220126_003432_addresses extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->createTable(Table::ADDRESSES, [
            'id' => $this->integer()->notNull(),
            'label' => $this->string()->notNull(),
            'givenName' => $this->string(),
            'additionalName' => $this->string(),
            'familyName' => $this->string(),
            'countryCode' => $this->string()->notNull(),
            'administrativeArea' => $this->string(),
            'locality' => $this->string(),
            'dependentLocality' => $this->string(),
            'postalCode' => $this->string(),
            'sortingCode' => $this->string(),
            'addressLine1' => $this->string(),
            'addressLine2' => $this->string(),
            'organization' => $this->string(),
            'metadata' => $this->text(),
            'latitude' => $this->string(),
            'longitude' => $this->string(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'PRIMARY KEY(id)',
        ]);

        $this->addForeignKey(null, Table::ADDRESSES, ['id'], Table::ELEMENTS, ['id'], 'CASCADE', 'CASCADE');

        $this->createTable(Table::ADDRESSES_USERS, [
            'id' => $this->primaryKey(),
            'addressId' => $this->integer()->notNull(),
            'userId' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull()
        ]);

        $this->createIndex(null, Table::ADDRESSES_USERS, ['userId', 'addressId'], true);
        $this->addForeignKey(null, Table::ADDRESSES_USERS, ['addressId'], Table::ADDRESSES, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ADDRESSES_USERS, ['userId'], Table::ELEMENTS, ['id'], 'CASCADE', 'CASCADE');

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        return true;
    }
}
