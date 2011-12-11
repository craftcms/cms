<?php

class Blocks extends Yii
{
	private static $_edition = '@@@edition@@@';
	private static $_version = /*versionNumber*/ '0.11';
	private static $_build = '@@@build@@@';

	public static function getEdition()
	{
		if (strpos(self::$_edition, '@@@') !== false)
			self::$_edition = self::getStoredEdition();

		return self::$_edition;
	}

	public static function getStoredEdition()
	{
		$info = Info::model()->findAll();
		return !empty($info) ? $info[0]->edition : null;
	}

	public static function getVersion()
	{
		if (strpos(self::$_version, '@@@') !== false)
			self::$_version = self::getStoredVersion();

		return self::$_version;
	}

	public static function getStoredVerison()
	{
		$info = Info::model()->findAll();
		return !empty($info) ? $info[0]->version : null;
	}

	public static function getBuild()
	{
		if (strpos(self::$_build, '@@@') !== false)
			self::$_build = self::getStoredBuild();

		return self::$_build;
	}

	public static function getStoredBuild()
	{
		$info = Info::model()->findAll();
		return !empty($info) ? $info[0]->build : null;
	}

	public static function getYiiVersion()
	{
		return parent::getVersion();
	}

	public static function dump($target)
	{
		return CVarDumper::dump($target, 10, true) ;
	}
}
