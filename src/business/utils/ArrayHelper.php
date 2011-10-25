<?php

class ArrayHelper
{
	public static function IsValidArray($value)
	{
		return (isset($value) && is_array($value));
	}
}
