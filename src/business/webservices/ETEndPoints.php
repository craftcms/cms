<?php

class ETEndPoints
{
	public static function Check()
	{
		return 'http://et.blockscms.com/admin.php/'.Blocks::app()->config('actionTriggerWord').'/core/check';
	}

	public static function DownloadPackage()
	{
		return 'http://et.blockscms.com/admin.php/'.Blocks::app()->config('actionTriggerWord').'/core/downloadpackage';
	}

	public static function GetCoreReleaseFileMD5()
	{
		return 'http://et.blockscms.com/admin.php/'.Blocks::app()->config('actionTriggerWord').'/core/getcorereleasefilemd5';
	}

	public static function ValidateKeysByCredentials()
	{
		return 'http://et.blockscms.com/admin.php/'.Blocks::app()->config('actionTriggerWord').'/core/validateKeysByCredentials';
	}

	public static function Ping()
	{
		return 'http://et.blockscms.com/admin.php/'.Blocks::app()->config('actionTriggerWord').'/core/ping';
	}
}
