<?php

/**
 *
 */
class bSiteBlock extends bBaseBlocksModel
{
	protected $tableName = 'siteblocks';
	protected $model = 'bSite';
	protected $foreignKey = 'site';

	/**
	 * Returns an instance of the specified model
	 * @return object The model instance
	 * @static
	 */
	public static function model($class = __CLASS__)
	{
		return parent::model($class);
	}
}
