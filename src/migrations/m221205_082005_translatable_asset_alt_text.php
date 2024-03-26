<?php

namespace craft\migrations;

use craft\base\Field;
use craft\db\Migration;
use craft\db\Table;

/**
 * m221205_082005_translatable_asset_alt_text migration.
 */
class m221205_082005_translatable_asset_alt_text extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->addColumn(Table::VOLUMES, 'altTranslationMethod', $this->string()->notNull()->defaultValue(Field::TRANSLATION_METHOD_SITE)->after('titleTranslationKeyFormat'));
        $this->addColumn(Table::VOLUMES, 'altTranslationKeyFormat', $this->text()->after('altTranslationMethod'));

        $this->dropTableIfExists(Table::ASSETS_SITES);

        $this->createTable(Table::ASSETS_SITES, [
            'assetId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'alt' => $this->text(),
            'PRIMARY KEY([[assetId]], [[siteId]])',
        ]);

        $this->addForeignKey(null, Table::ASSETS_SITES, ['assetId'], Table::ASSETS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::ASSETS_SITES, ['siteId'], Table::SITES, ['id'], 'CASCADE', 'CASCADE');

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m221205_082005_translatable_asset_alt_text cannot be reverted.\n";
        return false;
    }
}
