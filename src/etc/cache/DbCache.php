<?php
namespace Craft;

/**
 * DbCache implements a cache application component by storing cached data in a database.
 *
 * DbCache stores cache data in a DB table named {@link cacheTableName}.
 * If the table does not exist, it will be automatically created.
 *
 * DbCache relies on {@link http://www.php.net/manual/en/ref.pdo.php PDO} to access database.
 * By default, it will use the database connection information stored in your craft/config/db.php file.
 *
 * @package craft.app.etc.cache
 */
class DbCache extends \CDbCache
{
	/**
	 * @return DbConnection
	 */
	public function getDbConnection()
	{
		return craft()->db;
	}
}
