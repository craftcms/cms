<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\migrations;

/**
 * This view is used by app/console/controllers/MigrateController.php.
 *
 * The following variables are available in this view:
 */
/* @var $className         string The new migration class name. */
/* @var $migrationNameDesc string The format of the migration class name. */
/* @var $namespace         string The namespace of the generated migration. */

echo "<?php\n";
?>

namespace <?= $namespace ?>

use yii\db\Schema;
use yii\db\Migration;

/**
 * The class name is the UTC timestamp in the format of <?= $migrationNameDesc ?>.
 */
class <?= $className ?> extends Migration
{
	/**
	 * The migration code in this method is wrapped inside of a database transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		return true;
	}
}
