<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130313_200447_these_were_supposed_to_be_here_already extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$htaccessContent = 'deny from all'.PHP_EOL;
		$webConfigContent = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<configuration>
    <system.webServer>
         <authorization>
              <remove users=\"*\" roles=\"\" verbs=\"\" />
         </authorization>
    </system.webServer>
</configuration>".PHP_EOL;

		$files = array('.htaccess' => $htaccessContent, 'web.config' => $webConfigContent);

		try
		{
			$craftFolder = CRAFT_BASE_PATH;

			foreach ($files as $file => $content)
			{
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
								Craft::log($file.' does not exist at '.$fullFilePath.'.  We created the file, but were unable to write to it.', LogLevel::Warning);
							}
						}
						else
						{
							Craft::log($file.' does not exist at '.$fullFilePath.', and we tried to create it, but could not.', LogLevel::Warning);
						}
					}
					else
					{
						Craft::log($file.' does not exist at '.$fullFilePath.', but we do not have write access to that folder.', LogLevel::Warning);
					}
				}
				else
				{
					Craft::log($file.' already exists at '.$fullFilePath.'.');
				}
			}
		}
		catch (\Exception $e)
		{
			// Log and swallow
			Craft::log('There was a problem trying to add the .htaccess/web.config files: '.$e->getMessage(), LogLevel::Error);
		}

		return true;
	}
}
