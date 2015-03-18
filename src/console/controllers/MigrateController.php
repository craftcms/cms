<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\console\controllers;

use yii\base\NotSupportedException;

/**
 * Manages Craft and plugin migrations.
 *
 * A migration means a set of persistent changes to the application environment that is shared among different
 * developers. For example, in an application backed by a database, a migration may refer to a set of changes to
 * the database, such as creating a new table, adding a new table column.
 *
 * This controllers provides support for tracking the migration history, updating migrations, and creating new
 * migration skeleton files..
 *
 * The migration history is stored in a database table named [[migrationTable]]. The table will be automatically
 * created the first time this controller is executed, if it does not exist.
 *
 * Below are some common usages of this command:
 *
 * ~~~
 * # creates a new migration named 'create_user_table'
 * yii migrate/create create_user_table
 *
 * # applies ALL new migrations
 * yii migrate
 * ~~~
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class MigrateController extends \yii\console\controllers\MigrateController
{
	/**
	 * Craft doesn’t support down migrations.
	 *
	 * @param int $limit
	 *
	 * @return int|void
	 * @throws NotSupportedException
	 */
	public function actionDown($limit = 1)
	{
		throw new NotSupportedException('Down migrations are not supported.');
	}

	/**
	 * Craft doesn’t support redoing migrations.
	 *
	 * @param int $limit
	 *
	 * @return int|void
	 * @throws NotSupportedException
	 */
	public function actionRedo($limit = 1)
	{
		throw new NotSupportedException('Redoing migrations are not supported.');
	}

	/**
	 * Craft doesn’t support running migrations up or down to a specific version.
	 *
	 * @param string $version
	 *
	 * @throws NotSupportedException
	 */
	public function actionTo($version)
	{
		throw new NotSupportedException('Running migrations to a specific point is not supported.');
	}

	/**
	 * Craft doesn’t support changing migration history.
	 *
	 * @param string $version
	 *
	 * @return int|void
	 * @throws NotSupportedException
	 */
	public function actionMark($version)
	{
		throw new NotSupportedException('Marking migrations is not supported.');
	}
}
