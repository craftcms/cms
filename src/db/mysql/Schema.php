<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\db\mysql;

use Craft;

/**
 * @inheritDoc \yii\db\mysql\Schema
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Schema extends \yii\db\mysql\Schema
{
	// Properties
	// =========================================================================

	/**
	 * @var int The maximum length that objects' names can be.
	 */
	public $maxObjectNameLength = 64;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc \yii\base\Object::init()
	 */
	public function init()
	{
		parent::init();

		$this->typeMap['mediumtext'] = 'mediumtext';
	}

	/**
	 * Creates a query builder for the database.
	 * This method may be overridden by child classes to create a DBMS-specific query builder.
	 * @return QueryBuilder query builder instance
	 */
	public function createQueryBuilder()
	{
		return new QueryBuilder($this->db);
	}

	/**
	 * Quotes a database name for use in a query.
	 *
	 * @param $name
	 *
	 * @return string
	 */
	public function quoteDatabaseName($name)
	{
		return '`'.$name.'`';
	}

	// Protected Methods
	// =========================================================================

	/**
	 * Returns all table names in the database which start with the tablePrefix.
	 *
	 * @param string $schema
	 *
	 * @return string
	 */
	protected function findTableNames($schema = null)
	{
		if (!$schema)
		{
			$likeSql = ($this->db->tablePrefix ? ' LIKE \''.$this->db->tablePrefix.'%\'' : '');
			return $this->db->createCommand('SHOW TABLES'.$likeSql)->queryColumn();
		}
		else
		{
			return parent::findTableNames();
		}
	}
}
