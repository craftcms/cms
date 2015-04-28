<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web\twig\variables;

use craft\app\dates\DateTime;
use craft\app\helpers\AppHelper;
use craft\app\helpers\IOHelper;

/**
 * Class App variable.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class App
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns the Craft edition.
	 *
	 * @return string
	 */
	public function getEdition()
	{
		return \Craft::$app->getEdition();
	}

	/**
	 * Returns the name of the Craft edition.
	 *
	 * @return string
	 */
	public function getEditionName()
	{
		return \Craft::$app->getEditionName();
	}

	/**
	 * Returns the edition Craft is actually licensed to run in.
	 *
	 * @return int|null
	 */
	public function getLicensedEdition()
	{
		return \Craft::$app->getLicensedEdition();
	}

	/**
	 * Returns the name of the edition Craft is actually licensed to run in.
	 *
	 * @return string|null
	 */
	public function getLicensedEditionName()
	{
		return \Craft::$app->getLicensedEditionName();
	}

	/**
	 * Returns whether Craft is running with the wrong edition.
	 *
	 * @return bool
	 */
	public function hasWrongEdition()
	{
		return \Craft::$app->hasWrongEdition();
	}

	/**
	 * Returns whether Craft is elligible to be upgraded to a different edition.
	 *
	 * @return bool
	 */
	public function canUpgradeEdition()
	{
		return \Craft::$app->canUpgradeEdition();
	}

	/**
	 * Returns whether Craft is running on a domain that is eligible to test out
	 * the editions.
	 *
	 * @return bool
	 */
	public function canTestEditions()
	{
		return \Craft::$app->canTestEditions();
	}

	/**
	 * Returns the installed Craft version.
	 *
	 * @return string
	 */
	public function getVersion()
	{
		return \Craft::$app->version;
	}

	/**
	 * Returns the installed Craft build.
	 *
	 * @return string
	 */
	public function getBuild()
	{
		return \Craft::$app->build;
	}

	/**
	 * Returns the installed Craft release date.
	 *
	 * @return DateTime
	 */
	public function getReleaseDate()
	{
		return \Craft::$app->releaseDate;
	}

	/**
	 * Returns the site name.
	 *
	 * @return string
	 */
	public function getSiteName()
	{
		return \Craft::$app->getSiteName();
	}

	/**
	 * Returns the site URL.
	 *
	 * @return string
	 */
	public function getSiteUrl()
	{
		return \Craft::$app->getSiteUrl();
	}

	/**
	 * Returns the site UID.
	 *
	 * @return string
	 */
	public function getSiteUid()
	{
		return \Craft::$app->getSiteUid();
	}

	/**
	 * Returns the site language.
	 *
	 * @return string
	 */
	public function getLocale()
	{
		return \Craft::$app->language;
	}

	/**
	 * Returns whether the system is on.
	 *
	 * @return string
	 */
	public function isSystemOn()
	{
		return \Craft::$app->isSystemOn();
	}

	/**
	 * Returns whether the update info is cached.
	 *
	 * @return bool
	 */
	public function isUpdateInfoCached()
	{
		return \Craft::$app->getUpdates()->isUpdateInfoCached();
	}

	/**
	 * Returns how many updates are available.
	 *
	 * @return int
	 */
	public function getTotalAvailableUpdates()
	{
		return \Craft::$app->getUpdates()->getTotalAvailableUpdates();
	}

	/**
	 * Returns whether a critical update is available.
	 *
	 * @return bool
	 */
	public function isCriticalUpdateAvailable()
	{
		return \Craft::$app->getUpdates()->isCriticalUpdateAvailable();
	}

	/**
	 * Return max upload size in bytes.
	 *
	 * @return int
	 */
	public function getMaxUploadSize()
	{
		$maxUpload = AppHelper::getPhpConfigValueInBytes('upload_max_filesize');
		$maxPost = AppHelper::getPhpConfigValueInBytes('post_max_size');
		$memoryLimit = AppHelper::getPhpConfigValueInBytes('memory_limit');

		$uploadInBytes = min($maxUpload, $maxPost);

		if ($memoryLimit > 0)
		{
			$uploadInBytes = min($uploadInBytes, $memoryLimit);
		}

		$configLimit = (int) \Craft::$app->getConfig()->get('maxUploadFileSize');

		if ($configLimit)
		{
			$uploadInBytes = min($uploadInBytes, $configLimit);
		}

		return $uploadInBytes;
	}

	/**
	 * Returns a list of file kinds.
	 *
	 * @return array
	 */
	public function getFileKinds()
	{
		return IOHelper::getFileKinds();
	}
}
