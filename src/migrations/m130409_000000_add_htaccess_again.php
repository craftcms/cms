<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130409_000000_add_htaccess_again extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$content = 'deny from all'.PHP_EOL;
		$file = '.htaccess';

		try
		{
			$craftFolder = CRAFT_BASE_PATH;

			$fullFilePath = $craftFolder.$file;

			if (!IOHelper::fileExists($fullFilePath))
			{
				if (IOHelper::isWritable($craftFolder))
				{
					if (IOHelper::createFile($fullFilePath))
					{
						if (IOHelper::writeToFile($fullFilePath, $content))
						{
							Craft::log('Successfully added '.$file.' to '.$fullFilePath);
						}
						else
						{
							Craft::log($file.' does not exist at '.$fullFilePath.'.  We created the file, but were unable to write to it.', \CLogger::LEVEL_WARNING);
						}
					}
					else
					{
						Craft::log($file.' does not exist at '.$fullFilePath.', and we tried to create it, but could not.', \CLogger::LEVEL_WARNING);
					}
				}
				else
				{
					Craft::log($file.' does not exist at '.$fullFilePath.', but we do not have write access to that folder.', \CLogger::LEVEL_WARNING);
				}
			}
			else
			{
				Craft::log($file.' already exists at '.$fullFilePath.'.');
			}
		}
		catch (\Exception $e)
		{
			// Log and swallow
			Craft::log('There was a problem trying to the .htaccess file: '.$e->getMessage(), \CLogger::LEVEL_ERROR);
		}

		return true;
	}
}
