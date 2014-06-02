<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140603_000005_asset_sources extends BaseMigration
{
	/**
	 * Convert allowed source storage format from just an integer to "folder:X" format.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// Grab all the Assets fields.
		$rows = craft()->db->createCommand()
			->select('id, settings')
			->from('fields')
			->where('type = :type', array(':type' => "Assets"))
			->queryAll();

		// For each existing Assets field
		foreach ($rows as $row)
		{
			// Convert the "sources" setting, if it's saved in the wrong format.
			$settings = json_decode($row['settings']);
			if (is_object($settings) && !empty($settings->sources) && is_array($settings->sources))
			{
				foreach($settings->sources as &$source)
				{
					if (is_numeric($source))
					{
						$source = 'folder:'.$source;
					}
				}
				$this->update('fields',
					array('settings' => json_encode($settings)),
					'id = :id',
					array(
						':id' => $row['id']
					)
				);
			}
		}

		return true;
	}
}
