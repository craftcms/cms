<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140204_000007_delete_the_deleted_files extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$filesToDelete = array(
			'elementtypes/MatrixRecordElementType.php',
			'etc/assets/fileicons/56.png',
			'etc/console/commands/MigrateCommand.php',
			'etc/console/commands/QuerygenCommand.php',
			'migrations/m130917_000000_drop_users_enctype.php',
			'migrations/m130917_000001_big_names_and_handles.php',
			'migrations/m130917_000002_entry_types.php',
			'migrations/m130917_000003_section_types.php',
			'migrations/m131105_000000_content_column_field_prefixes.php',
			'migrations/m131105_000001_add_missing_content_and_i18n_rows.php',
			'migrations/m131105_000001_element_content.php',
			'migrations/m131105_000002_schema_version.php',
			'migrations/m131105_000003_field_contexts.php',
			'migrations/m131105_000004_matrix.php',
			'migrations/m131105_000004_matrix_blocks.php',
			'migrations/m131105_000005_correct_tag_field_layouts.php',
			'migrations/m131105_000006_remove_gethelp_widget_for_non_admins.php',
			'migrations/m131105_000007_new_relation_column_names.php',
			'migrations/m131105_000008_add_values_for_unknown_asset_kinds.php',
			'models/MatrixRecordModel.php',
			'models/MatrixRecordTypeModel.php',
			'models/TagSetModel.php',
			'records/EntryLocaleRecord.php',
			'records/MatrixRecordRecord.php',
			'records/MatrixRecordTypeRecord.php',
			'records/StructuredEntryRecord.php',
			'records/TagSetRecord.php',
			'resources/images/whats-new/entrytypes.png',
			'resources/images/whats-new/single.png',
			'resources/images/whats-new/structure.png',
			'resources/js/compressed/dashboard.js',
			'resources/js/compressed/dashboard.min.map',
			'resources/js/dashboard.js',
			'templates/assets/_nav_folder.html',
			'templates/users/_edit/_userphoto.html',
			'templates/users/_edit/account.html',
			'templates/users/_edit/admin.html',
			'templates/users/_edit/layout.html',
			'templates/users/_edit/profile.html',
			'translations/fr_fr.php',
		);

		$appPath = craft()->path->getAppPath();

		foreach ($filesToDelete as $fileToDelete)
		{
			if (IOHelper::fileExists($appPath.$fileToDelete))
			{
				$fullPath = $appPath.$fileToDelete;

				Craft::log('Deleting file: '.$fullPath.' because it is not supposed to exist.', LogLevel::Info, true);
				IOHelper::deleteFile($appPath.$fileToDelete);
			}
			else
			{
				Craft::log('File: '.$fullPath.' does not exist.  Good.', LogLevel::Info, true);
			}
		}

		return true;
	}
}
