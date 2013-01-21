<?php
namespace Blocks;

/**
 *
 */
class DbMigration extends \CDbMigration
{

	/**
	 * This method contains the logic to be executed when applying this migration.
	 * Child classes may implement this method to provide actual migration logic.
	 *
	 * @return boolean
	 */
	public function up()
	{
		$transaction = $this->getDbConnection()->beginTransaction();

		try
		{
			if ($this->safeUp() === false)
			{
				$transaction->rollback();
				return false;
			}

			$transaction->commit();
		}
		catch(\Exception $e)
		{
			Blocks::log($e->getMessage().' ('.$e->getFile().':'.$e->getLine().')', \CLogger::LEVEL_ERROR);
			Blocks::log($e->getTraceAsString(), \CLogger::LEVEL_ERROR);

			$transaction->rollback();
			return false;
		}
	}
}
