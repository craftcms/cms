<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * m190401_223843_drop_old_indexes migration.
 */
class m190401_223843_drop_old_indexes extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // From m150403_183908_migrations_table_changes
        MigrationHelper::dropIndexIfExists(Table::MIGRATIONS, ['name'], true, $this);

        // From m161109_000000_index_shuffle
        MigrationHelper::dropIndexIfExists(Table::TEMPLATECACHES, ['expiryDate', 'cacheKey', 'siteId', 'path'], false, $this);
        MigrationHelper::dropIndexIfExists(Table::TEMPLATECACHES, ['siteId', 'cacheKey', 'path', 'expiryDate'], false, $this);

        // From m171204_000001_templatecache_index_tune_deux
        MigrationHelper::dropIndexIfExists(Table::TEMPLATECACHES, ['expiryDate', 'cacheKey', 'siteId'], false, $this);

        // From m180731_162030_soft_delete_sites
        MigrationHelper::dropIndexIfExists(Table::SITES, ['handle'], true, $this);

        // From m180824_193422_case_sensitivity_fixes
        MigrationHelper::dropIndexIfExists(Table::ELEMENTS_SITES, ['uri', 'siteId'], true, $this);
        MigrationHelper::dropIndexIfExists(Table::USERS, ['email'], true, $this);
        MigrationHelper::dropIndexIfExists(Table::USERS, ['username'], true, $this);

        // From m180910_142030_soft_delete_sitegroups
        MigrationHelper::dropIndexIfExists(Table::SITEGROUPS, ['name'], true, $this);

        // From m181011_160000_soft_delete_asset_support
        MigrationHelper::dropIndexIfExists(Table::ASSETS, ['filename', 'folderId'], true, $this);

        // From m190110_214819_soft_delete_volumes
        MigrationHelper::dropIndexIfExists(Table::VOLUMES, ['name'], true, $this);
        MigrationHelper::dropIndexIfExists(Table::VOLUMES, ['handle'], true, $this);

        // From m190112_201010_more_soft_deletes
        MigrationHelper::dropIndexIfExists(Table::CATEGORYGROUPS, ['name'], true, $this);
        MigrationHelper::dropIndexIfExists(Table::CATEGORYGROUPS, ['handle'], true, $this);
        MigrationHelper::dropIndexIfExists(Table::ENTRYTYPES, ['name', 'sectionId'], true, $this);
        MigrationHelper::dropIndexIfExists(Table::ENTRYTYPES, ['handle', 'sectionId'], true, $this);
        MigrationHelper::dropIndexIfExists(Table::SECTIONS, ['name'], true, $this);
        MigrationHelper::dropIndexIfExists(Table::SECTIONS, ['handle'], true, $this);
        MigrationHelper::dropIndexIfExists(Table::TAGGROUPS, ['name'], true, $this);
        MigrationHelper::dropIndexIfExists(Table::TAGGROUPS, ['handle'], true, $this);

        // From m190205_140000_fix_asset_soft_delete_index
        MigrationHelper::dropIndexIfExists(Table::ASSETS, ['filename', 'folderId'], false, $this);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190401_223843_drop_old_indexes cannot be reverted.\n";
        return false;
    }
}
