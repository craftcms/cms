<?php

abstract class BlocksDataType
{
	/**
	 * @static bool Whether this datatype has settings (stored in blx_DataTypeClass_settings)
	 */
	public static $hasSettings = false;

	/**
	 * @static bool Whether this datatype has content (joined to blx_content via blx_DataTypeClass_content)
	 */
	public static $hasContent = false;

	/**
	 * @static bool Whether this datatype has custom blocks (joined to blx_contentblocks via blx_DataTypeClass_blocks)
	 */
	public static $hasCustomBlocks = false;

	/**
	 * @static array One-to-many relationships
	 */
	public static $hasMany = array();

	/**
	 * @static array One-to-one relationships
	 */
	public static $hasOne = array();

	/**
	 * @static array Many-to-many relationships
	 */
	public static $hasAndBelongsToMany = array();

	/**
	 * @static array One-to-many or one-to-one relationships
	 */
	public static $belongsTo = array();

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
