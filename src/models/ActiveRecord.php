<?php
namespace Blocks;

/**
 * @abstract
 */
abstract class ActiveRecord extends \CActiveRecord
{
	/**
	 * Returns an instance of the specified model
	 *
	 * @static
	 * @param string $class
	 * @return object The model instance
	 */
	public static function model($class = __CLASS__)
	{
		return parent::model(get_called_class());
	}
}
