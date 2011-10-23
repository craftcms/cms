<?php

class BlocksBase extends Yii
{
	public static function getYiiVersion()
	{
		return Yii::getVersion();
	}

	public static function dump($target)
	{
		return CVarDumper::dump($target, 10, true) ;
	}
}
