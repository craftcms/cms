<?php

/**
 *
 */
class ETEndPoints
{
	/**
	 * @access public
	 *
	 * @static
	 * @return string
	 */
	public static function Check()
	{
		return 'http://et.blockscms.com/admin.php/'.Blocks::app()->config('actionTriggerWord').'/app/core/check';
	}

	/**
	 * @access public
	 *
	 * @static
	 *
	 * @return string
	 */
	public static function DownloadPackage()
	{
		return 'http://et.blockscms.com/admin.php/'.Blocks::app()->config('actionTriggerWord').'/app/core/downloadpackage';
	}

	/**
	 * @access public
	 *
	 * @static
	 *
	 * @return string
	 */
	public static function GetCoreReleaseFileMD5()
	{
		return 'http://et.blockscms.com/admin.php/'.Blocks::app()->config('actionTriggerWord').'/app/core/getcorereleasefilemd5';
	}

	/**
	 * @access public
	 *
	 * @static
	 *
	 * @return string
	 */
	public static function ValidateKeysByCredentials()
	{
		return 'http://et.blockscms.com/admin.php/'.Blocks::app()->config('actionTriggerWord').'/app/core/validateKeysByCredentials';
	}

	/**
	 * @access public
	 *
	 * @static
	 *
	 * @return string
	 */
	public static function Ping()
	{
		return 'http://et.blockscms.com/admin.php/'.Blocks::app()->config('actionTriggerWord').'/app/core/ping';
	}
}
