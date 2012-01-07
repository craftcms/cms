<?php

abstract class BlocksModel
{
	protected static $hasSettings = false;
	protected static $hasContent = false;
	protected static $hasCustomBlocks = false;

	protected static $hasMany = array();
	protected static $hasOne = array();
	protected static $hasAndBelongsToMany = array();
	protected static $belongsTo = array();

	protected static $attributes = array();

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
		return static::$hasSettings;
	}

	/**
	 * Whether this BlocksModel has content (joined to blx_content via blx_blocksmodelclass_content)
	 * @return bool
	 */
	public function getHasContent()
	{
		return static::$hasContent;
	}

	/**
	 * Whether this BlocksModel has custom blocks (joined to blx_contentblocks via blx_blocksmodelclass_blocks)
	 * @return bool
	 */
	public function getHasCustomBlocks()
	{
		return static::$hasCustomBlocks;
	}

	/**
	 * One-to-many relationships
	 * @return array
	 */
	public function getHasMany()
	{
		return static::$hasMany;
	}

	/**
	 * One-to-one relationships
	 * @return array
	 */
	public function getHasOne()
	{
		return static::$hasOne;
	}

	/**
	 * Many-to-many relationships
	 * @return array
	 */
	public function getHasAndBelongsToMany()
	{
		return static::$hasAndBelongsToMany;
	}

	/**
	 * One-to-many or one-to-one relationships
	 * @return array
	 */
	public function getBelongsTo()
	{
		return static::$belongsTo;
	}

	/**
	 * The BlocksModel's non-relational attributes
	 * @return array
	 */
	public function getAttributes()
	{
		return static::$attributes;
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
