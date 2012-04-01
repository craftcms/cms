<?php
namespace Blocks;

/**
 *
 */
class DbUpdateController extends Controller
{
	/**
	 * All db update actions require the user to be logged in
	 */
	public function init()
	{
		$this->requireLogin();
	}

	public function actionDbUpdateRequired()
	{
		$update = !b()->isDbUpdateNeeded ? false : true;
		$this->loadTemplate('update/db', array('dbUpdate' => $update));
	}

	/**
	 *
	 */
	public function actionUpdate()
	{
		$this->requirePostRequest();

		// run migrations to top
		if (b()->updates->runMigrationsToTop())
		{
			// update db with version info.
			if (b()->updates->setNewVersionAndBuild(Blocks::getVersion(false), Blocks::getBuild(false)))
			{
				// flush update cache.
				b()->updates->flushUpdateInfoFromCache();
				b()->user->setMessage(MessageType::Success, 'Database successfully updated.');

				$url = b()->request->getPost('redirect');
				if ($url !== null)
					$this->redirect($url);
			}
		}

		b()->user->setMessage(MessageType::Error, 'There was a problem updating the database.');
		$this->loadTemplate('update/db');
	}
}
