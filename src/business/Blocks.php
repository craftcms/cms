<?php

class Blocks extends Yii
{
	public static function getVersion($db = false)
	{
		if (!$db)
			return /*versionNumber*/ '0.11';

		$blocksVersion = Info::model()->findAll();
		return !empty($blocksVersion) ? $blocksVersion[0]->version : null;
	}

	public static function getBuildNumber($db = false)
	{
		if (!$db)
			return '@@@buildNumber@@@';

		$blocksBuildNumber = Info::model()->findAll();
		return !empty($blocksBuildNumber) ? $blocksBuildNumber[0]->build_number : null;
	}

	public static function getEdition($db = false)
	{
		if (!$db)
			return '@@@edition@@@';

		$blocksEdition = Info::model()->findAll();
		return !empty($blocksEdition) ? $blocksEdition[0]->edition : null;
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
