<?php

class BlocksActveRecord extends CActiveRecord
{
	function init($className)
	{
		$this->className = $className;
	}

	public function relations()
	{
		return $className::$relations;
	}

	public function rules()
	{
		
	}
}
