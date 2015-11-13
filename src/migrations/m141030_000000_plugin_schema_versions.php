<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m141030_000000_plugin_schema_versions extends BaseMigration
{
    /**
     * Any migration code in here is wrapped inside of a transaction.
     *
     * @return bool
     */
    public function safeUp()
    {
    	// Turn the version column into a Varchar
    	$this->alterColumn('plugins', 'version', array('column' => ColumnType::Varchar, 'length' => 15, 'null' => false));

        // Add the schemaVersion column
        $this->addColumnAfter('plugins', 'schemaVersion', array('column' => ColumnType::Varchar, 'length' => 15), 'version');

        return true;
    }
}
