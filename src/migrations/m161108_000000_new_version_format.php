<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m161108_000000_new_version_format extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp ()
	{
		if (!$this->dbConnection->columnExists('info', 'build'))
		{
			// Migration has already run
			return true;
		}

		// Increase size of the version column
		$this->alterColumn('info', 'version', array(ColumnType::Varchar, 'length' => 50, 'null' => false));

		// Get the existing version, build, and track
		$infoRow = $this->dbConnection->createCommand()
			->select('version, build, track')
			->from('info')
			->queryRow();

		// Update the version
		$version = $infoRow['version'];

		switch ($infoRow['track'])
		{
			case 'beta':
				$version .= '.0-beta.'.$infoRow['build'];
				break;
			case 'dev':
				$version .= '.0-dev.'.$infoRow['build'];
				break;
			default:
				$version .= '.'.$infoRow['build'];
		}

		$this->update('info', array('version' => $version));

		// Drop the unneeded columns
		$this->dropColumn('info', 'build');
		$this->dropColumn('info', 'releaseDate');
		$this->dropColumn('info', 'track');

		// Update the info model
		$info = craft()->getInfo();
		$info->version = $version;

		return true;
	}
}
