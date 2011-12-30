<?php

class ETEndPoints
{
	public static function Check()
	{
		return 'http://et.blockscms.com/admin.php/'.Blocks::app()->config('actionTriggerWord').'/app/core/check';
	}

	public static function DownloadPackage()
	{
		return 'http://et.blockscms.com/admin.php/'.Blocks::app()->config('actionTriggerWord').'/app/core/downloadpackage';
	}

	public static function GetCoreReleaseFileMD5()
	{
		return 'http://et.blockscms.com/admin.php/'.Blocks::app()->config('actionTriggerWord').'/app/core/getcorereleasefilemd5';
	}

	public static function ValidateKeysByCredentials()
	{
		return 'http://et.blockscms.com/admin.php/'.Blocks::app()->config('actionTriggerWord').'/app/core/validateKeysByCredentials';
	}

	public static function Ping()
	{
		return 'http://et.blockscms.com/admin.php/'.Blocks::app()->config('actionTriggerWord').'/app/core/ping';
	}
}
