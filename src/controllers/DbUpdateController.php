<?php
namespace Blocks;

/**
 *
 */
class DbUpdateController extends Controller
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

		// run migrations to top
		if (b()->updates->runMigrationsToTop())
		{
			// update db with version info.
			if (b()->updates->setNewVersionAndBuild(Blocks::getVersion(false), Blocks::getBuild(false)))
			{
				// flush update cache.
				b()->updates->flushUpdateInfoFromCache();
				b()->user->setMessage(MessageType::Success, 'Database successfully updated.');

				$this->returnJson(array('success' => true));
			}
		}

		$this->returnJson(array('error' => 'There was a problem updating the database.'));
	}
}
