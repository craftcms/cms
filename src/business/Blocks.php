<?php

class Blocks extends BlocksBase
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
}
