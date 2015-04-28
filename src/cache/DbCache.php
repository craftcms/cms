<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\cache;

use Craft;
use craft\app\db\Connection;
use craft\app\enums\ConfigCategory;

/**
 * DbCache implements a cache application component by storing cached data in a database.
 *
 * DbCache stores cache data in a DB table named [[cacheTableName]]. If the table does not exist, it will be
 * automatically created.
 *
 * DbCache relies on [PDO](http://www.php.net/manual/en/ref.pdo.php) to access database. By default, it will use
 * the database connection information stored in your craft/config/db.php file.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class DbCache extends \yii\caching\DbCache
{
	// Public Methods
	// =========================================================================

	/**
	 * @return Connection
	 */
	public function getDbConnection()
	{
		return Craft::$app->getDb();
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @param Connection   $db
	 * @param string         $tableName
	 */
	protected function createCacheTable($db, $tableName)
	{
		if (!Craft::$app->getDb()->tableExists(Craft::$app->getConfig()->get('cacheTableName', ConfigCategory::DbCache), true))
		{
			parent::createCacheTable($db, $tableName);
		}
	}
}
