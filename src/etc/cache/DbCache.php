<?php
namespace Craft;

/**
 * DbCache implements a cache application component by storing cached data in a database.
 *
 * DbCache stores cache data in a DB table named {@link cacheTableName}. If the table does not exist, it will be
 * automatically created.
 *
 * DbCache relies on [PDO](http://www.php.net/manual/en/ref.pdo.php) to access database. By default, it will use
 * the database connection information stored in your craft/config/db.php file.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.cache
 * @since     2.0
 */
class DbCache extends \CDbCache
{
	// Public Methods
	// =========================================================================

	/**
	 * @return DbConnection
	 */
	public function getDbConnection()
	{
		return craft()->db;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @param DbConnection   $db
	 * @param string         $tableName
	 */
	protected function createCacheTable($db, $tableName)
	{
		if (!craft()->db->tableExists(craft()->config->get('cacheTableName', ConfigFile::DbCache), true))
		{
			parent::createCacheTable($db, $tableName);
		}
	}
}
