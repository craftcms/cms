<?php
namespace Blocks;

/**
 *
 */
class EtEndPoints
{
	/**
	 * @static
	 * @return string
	 */
	public static function Check()
	{
		return 'http://et.blockscms.com/admin.php/'.Blocks::app()->getConfig('actionTriggerWord').'/app/core/check';
	}

	/**
	 * @static
	 * @return string
	 */
	public static function DownloadPackage()
	{
		return 'http://et.blockscms.com/admin.php/'.Blocks::app()->getConfig('actionTriggerWord').'/app/core/downloadpackage';
	}

	/**
	 * @static
	 * @return string
	 */
	public static function GetCoreReleaseFileMD5()
	{
		return 'http://et.blockscms.com/admin.php/'.Blocks::app()->getConfig('actionTriggerWord').'/app/core/getcorereleasefilemd5';
	}

	/**
	 * @static
	 * @return string
	 */
	public static function ValidateKeysByCredentials()
	{
		return 'http://et.blockscms.com/admin.php/'.Blocks::app()->getConfig('actionTriggerWord').'/app/core/validateKeysByCredentials';
	}

	/**
	 * @static
	 * @return string
	 */
	public static function Ping()
	{
		return 'http://et.blockscms.com/admin.php/'.Blocks::app()->getConfig('actionTriggerWord').'/app/core/ping';
	}
}
