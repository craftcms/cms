<?php

abstract class BlocksModel
{
	private static $hasSettings = false;
	private static $hasContent = false;
	private static $hasCustomBlocks = false;

	private static $hasMany = array();
	private static $hasOne = array();
	private static $hasAndBelongsToMany = array();
	private static $belongsTo = array();

	private static $attributes = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
		if (method_exists($this, 'init'))
		{
			$args = func_get_args();
			call_user_func_array(array($this, 'init'), $args);
		}
	}

	/**
	 * Whether this BlocksModel has settings (stored in blx_blocksmodelclass_settings)
	 * @return bool
	 */
	public function getHasSettings()
	{
		return self::$hasSettings;
	}

	/**
	 * Whether this BlocksModel has content (joined to blx_content via blx_blocksmodelclass_content)
	 * @return bool
	 */
	public function getHasContent()
	{
		return self::$hasContent;
	}

	/**
	 * Whether this BlocksModel has custom blocks (joined to blx_contentblocks via blx_blocksmodelclass_blocks)
	 * @return bool
	 */
	public function getHasCustomBlocks()
	{
		return self::$hasCustomBlocks;
	}

	/**
	 * One-to-many relationships
	 * @return array
	 */
	public function getHasMany()
	{
		return self::$hasMany;
	}

	/**
	 * One-to-one relationships
	 * @return array
	 */
	public function getHasOne()
	{
		return self::$hasOne;
	}

	/**
	 * Many-to-many relationships
	 * @return array
	 */
	public function getHasAndBelongsToMany()
	{
		return self::$hasAndBelongsToMany;
	}

	/**
	 * One-to-many or one-to-one relationships
	 * @return array
	 */
	public function getBelongsTo()
	{
		return self::$belongsTo;
	}

	/**
	 * The BlocksModel's non-relational attributes
	 * @return array
	 */
	public function getAttributes()
	{
		return self::$attributes;
	}

	/**
	 * Creates the table(s) necessary for this BlocksModel to save its data
	 * @static
	 */
	public static function install()
	{
		
	}

	/**
	 * Returns the active record model for this BlocksModel
	 * @static
	 *
	 * @param string $className
	 *
	 * @return mixed
	 */
	public static function model($className = __CLASS__)
	{
		return BlocksActiveRecord::model($className);
	}
}
