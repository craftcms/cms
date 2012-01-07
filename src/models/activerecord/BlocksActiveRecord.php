<?php

class BlocksActiveRecord extends CActiveRecord
{
	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	public function relations()
	{
		//return $className::$relations;
	}

	public function rules()
	{
		
	}
}
