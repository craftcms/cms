<?php
namespace Blocks;


/**
 * Rebranding functions
 */
class RebrandVariable
{
	/**
	 * Returns true if there is an admin panel logo uploaded.
	 *
	 * @return bool
	 */
	public function isLogoUploaded()
	{
		if (Blocks::hasPackage(BlocksPackage::Rebrand))
		{
			$fileList = IOHelper::getFolderContents(blx()->path->getUploadsPath() . 'logo/', false);
			return !empty($fileList);
		}
		return false;
	}

	/**
	 * Return the URL to the admin panel logo.
	 *
	 * @return string
	 */
	public function getLogoUrl()
	{
		if (Blocks::hasPackage(BlocksPackage::Rebrand))
		{
			$fileList = IOHelper::getFolderContents(blx()->path->getUploadsPath() . 'logo/', false);
			if (!empty($fileList))
			{
				return UrlHelper::getResourceUrl('uploads/logo/' . pathinfo(reset($fileList), PATHINFO_BASENAME));
			}
		}

		return false;
	}
}
