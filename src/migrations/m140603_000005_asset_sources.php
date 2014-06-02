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
		$fields = craft()->db->createCommand()
			->select('id, settings')
			->from('fields')
			->where('type = :type', array(':type' => "Assets"))
			->queryAll();

		if ($fields)
		{
			// Grab all of the top-level folder IDs
			$folders = craft()->db->createCommand()
				->select('id, sourceId')
				->from('assetfolders')
				->where('parentId is null')
				->queryAll();

			if ($folders)
			{
				// Create an associative array of them by source ID
				$folderIdsBySourceId = array();

				foreach ($folders as $folder)
				{
					$folderIdsBySourceId[$folder['sourceId']] = $folder['id'];
				}

				// Now update the fields
				foreach ($fields as $field)
				{
					$settings = JsonHelper::decode($field['settings']);

					if (is_array($settings['sources']))
					{
						// Are there any source IDs?
						$anySourceIds = false;

						foreach ($settings['sources'] as $key => $source)
						{
							if (isset($folderIdsBySourceId[$source]))
							{
								$settings['sources'][$key] = 'folder:'.$folderIdsBySourceId[$source];
								$anySourceIds = true;
							}
						}

						if ($anySourceIds)
						{
							$this->update('fields', array(
								'settings' => JsonHelper::encode($settings)
							), array(
								'id' => $field['id']
							));
						}
					}
				}
			}
		}

		return true;
	}
}
