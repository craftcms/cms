<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

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
        $this->dropTableIfExists(Table::ADDRESSES);

        $this->createTable(Table::ADDRESSES, [
            'id' => $this->integer()->notNull(),
            'ownerId' => $this->integer()->notNull(),
            'countryCode' => $this->string()->notNull(),
            'administrativeArea' => $this->string(),
            'locality' => $this->string(),
            'dependentLocality' => $this->string(),
            'postalCode' => $this->string(),
            'sortingCode' => $this->string(),
            'addressLine1' => $this->string(),
            'addressLine2' => $this->string(),
            'organization' => $this->string(),
            'organizationTaxId' => $this->string(),
            'fullName' => $this->string(),
            'firstName' => $this->string(),
            'lastName' => $this->string(),
            'latitude' => $this->string(),
            'longitude' => $this->string(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'PRIMARY KEY(id)',
        ]);

        $this->addForeignKey(null, Table::ADDRESSES, ['id'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ADDRESSES, ['ownerId'], Table::ELEMENTS, ['id'], 'CASCADE', null);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropTableIfExists(Table::ADDRESSES);
        return true;
    }
}
