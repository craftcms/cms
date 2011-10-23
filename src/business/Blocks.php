<?php

class Blocks extends Yii
{
	public static function getVersion()
	{
		return /*versionNumber*/ '0.1';
	}

	public static function getBuildNumber()
	{
		return '@@@buildNumber@@@';
	}

	public static function getEdition()
	{
		return '@@@edition@@@';
	}

	public static function getYiiVersion()
	{
		return Yii::getVersion();
	}

	public static function dump($target)
	{
		return CVarDumper::dump($target, 10, true) ;
	}
}
