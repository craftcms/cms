<?php
namespace Blocks;

/**
 * Handles content management tasks
 */
class EntriesController extends BaseController
{
	/**
	 * Saves an entry.
	 */
	public function actionSaveEntry()
	{
		$this->requirePostRequest();

		$entry = new EntryPackage();

		$entry->id = blx()->request->getPost('entryId');
		$entry->title = blx()->request->getPost('title');
		$entry->slug = blx()->request->getPost('slug');
		$entry->blocks = blx()->request->getPost('blocks');
		$entry->postDate = $this->getDateFromPost('postDate');
		$entry->expiryDate = $this->getDateFromPost('expiryDate');
		$entry->enabled = blx()->request->getPost('enabled');

		if (Blocks::hasPackage(BlocksPackage::Users))
		{
			$entry->authorId = blx()->account->getCurrentUser()->id;
		}

		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			$entry->sectionId = blx()->request->getRequiredPost('sectionId');
		}

		if (Blocks::hasPackage(BlocksPackage::Language))
		{
			$entry->language = blx()->request->getPost('language');
		}

		if ($entry->save())
		{
			blx()->user->setNotice(Blocks::t('Entry saved.'));

			$this->redirectToPostedUrl(array(
				'entryId' => $entry->id
			));
		}
		else
		{
			blx()->user->setError(Blocks::t('Couldn’t save entry.'));
		}

		$this->renderRequestedTemplate(array(
			'entry' => $entry
		));
	}
}
