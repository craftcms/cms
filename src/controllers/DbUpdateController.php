<?php
namespace Blocks;

/**
 *
 */
class DbUpdateController extends BaseController
{
	/**
	 * Index
	 */
	public function actionIndex()
	{
		$this->loadTemplate('_special/dbupdate');
	}

	/**
	 *
	 */
	public function actionUpdate()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		try
		{
			// Take the system offline.
			blx()->updates->turnSystemOffBeforeUpdate();

			// run migrations to top
			if (blx()->migrations->runToTop())
			{
				// update db with version info.
				if (blx()->updates->setNewBlocksInfo(Blocks::getVersion(false), Blocks::getBuild(false), Blocks::getReleaseDate(false)))
				{
					// flush update cache.
					blx()->updates->flushUpdateInfoFromCache();
					blx()->user->setNotice('Database successfully updated.');

					// Bring the system back online.
					blx()->updates->turnSystemOnAfterUpdate();

					$this->returnJson(array('success' => true));
				}
			}

			$this->returnJson(array('error' => 'There was a problem updating the database.'));
		}
		catch (\Exception $e)
		{
			Blocks::log($e->getMessage(), \CLogger::LEVEL_ERROR);
			$this->returnJson(array('error' => 'There was a problem updating the database.'));
		}

	}
}
