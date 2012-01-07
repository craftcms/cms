<?php

abstract class BlocksDataType
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
	 * Whether this datatype has settings (stored in blx_DataTypeClass_settings)
	 */
	public function getHasSettings()
	{
		return self::$hasSettings;
	}

	/**
	 * Whether this datatype has content (joined to blx_content via blx_DataTypeClass_content)
	 */
	public function getHasContent()
	{
		return self::$hasContent;
	}

	/**
	 * Whether this datatype has custom blocks (joined to blx_contentblocks via blx_DataTypeClass_blocks)
	 */
	public function getHasCustomBlocks()
	{
		return self::$hasCustomBlocks;
	}

	/**
	 * One-to-many relationships
	 */
	public function getHasMany()
	{
		return self::$hasMany;
	}

	/**
	 * One-to-one relationships
	 */
	public function getHasOne()
	{
		return self::$hasOne;
	}

	/**
	 * Many-to-many relationships
	 */
	public function getHasAndBelongsToMany()
	{
		return self::$hasAndBelongsToMany;
	}

	/**
	 * One-to-many or one-to-one relationships
	 */
	public function getBelongsTo()
	{
		return self::$belongsTo;
	}

	/**
	 * The datatype's non-relational attributes
	 */
	public function getAttributes()
	{
		return self::$attributes;
	}

	/**
	 * Creates the table(s) necessary for this datatype to save its data
	 * @static
	 */
	public static function install()
	{
		
	}

	/**
	 * Returns the active record model for this datatype
	 * @static
	 */
	public static function model($className = __CLASS__)
	{
		return BlocksActiveRecord::model($className);
	}
}
