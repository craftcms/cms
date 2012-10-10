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

		$entry = new EntryModel();

		$entry->id = blx()->request->getPost('entryId');
		$entry->title = blx()->request->getPost('title');
		$entry->slug = blx()->request->getPost('slug');
		$entry->postDate = $this->getDateFromPost('postDate');
		$entry->expiryDate = $this->getDateFromPost('expiryDate');
		$entry->enabled = blx()->request->getPost('enabled');
		$entry->tags = blx()->request->getPost('tags');

		$entry->setBlockValues(blx()->request->getPost('blocks'));

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

		if (blx()->entries->saveEntry($entry))
		{
			if (blx()->request->isAjaxRequest())
			{
				$return['success']   = true;
				$return['entry']     = $entry->getAttributes();
				$return['cpEditUrl'] = $entry->getCpEditUrl();

				if (Blocks::hasPackage(BlocksPackage::Users))
				{
					$return['author'] = $entry->getAuthor()->getAttributes();
				}

				$this->returnJson($return);
			}
			else
			{
				blx()->user->setNotice(Blocks::t('Entry saved.'));

				$this->redirectToPostedUrl(array(
					'entryId' => $entry->id
				));
			}
		}
		else
		{
			if (blx()->request->isAjaxRequest())
			{
				$this->returnJson(array(
					'errors' => $entry->getErrors(),
				));
			}
			else
			{
				blx()->user->setError(Blocks::t('Couldn’t save entry.'));

				$this->renderRequestedTemplate(array(
					'entry' => $entry
				));
			}
		}
	}
}
