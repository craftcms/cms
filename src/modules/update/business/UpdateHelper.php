<?php

class UpdateHelper
{
	public static function rollBackFileChanges($manifestFile)
	{
		$manifestData = explode(PHP_EOL, $manifestFile->getContents());

		foreach ($manifestData as $row)
		{
			$rowData = explode(';', $row);
			$file = Blocks::app()->file->set(BLOCKS_BASE_PATH.$rowData[1].'.bak');

			if ($file->exists)
				$file->rename($rowData[1]);
		}
	}

	public static function doFileUpdate($masterManifest, $updaterType, $firstCheck)
	{
		$manifestData = explode(PHP_EOL, $masterManifest->getContents());

		try
		{
			foreach ($manifestData as $row)
			{
				$rowData = explode(';', $row);

				// this isn't the first call in the update process and we've done all of the required updaterType updates
				if ($rowData[3] !== $updaterType && !$firstCheck)
					continue;

				// first call in the update process, we need to see if any updater files need updating.
				if ($rowData[3] == UpdaterType::Updater && $firstCheck)
				{
					$manifestId = explode('_', $masterManifest->getFileName());
					Blocks::app()->request->redirect(array('update/updaterupdate', 'manifestId' => $manifestId[1]), true);
				}

				$relativePath = self::stripRootBlocksPath($rowData[1]);
				$destFile = Blocks::app()->file->set(BLOCKS_BASE_PATH.$relativePath);
				$sourceFile = Blocks::app()->file->set($rowData[0].DIRECTORY_SEPARATOR.$rowData[1]);

				switch ($rowData[2])
				{
					// update the file
					case PatchManifestFileAction::Add:
						$sourceFile->copy($destFile->getRealPath(), true);
						break;

					case PatchManifestFileAction::Remove:
						// rename in case we need to rollback.  the cleanup will remove the backup files.
						$destFile->rename($destFile->getRealPath().'.bak');
						break;

					default:
						Blocks::log('Unknown PatchManifestFileAction');
						UpdateHelper::rollBackFileChanges($manifestData);
						return false;
				}
			}
		}
		catch (Exception $e)
		{
			Blocks::log('Error updating files: '.$e->getMessage());
			UpdateHelper::rollBackFileChanges($masterManifest);
			return false;
		}

		return true;
	}

	public static function stripRootBlocksPath($path)
	{
		if (strpos($path, 'blocks') == 0)
			$path = substr($path, 7);

		return $path;
	}
}
