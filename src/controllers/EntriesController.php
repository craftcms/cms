<?php
namespace Blocks;

/**
 * Handles entry tasks
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

		$entry->sectionId  = blx()->request->getRequiredPost('sectionId');
		$entry->locale     = blx()->request->getPost('locale', blx()->i18n->getPrimarySiteLocale()->getId());
		$entry->id         = blx()->request->getPost('entryId');
		$entry->authorId   = blx()->request->getPost('author', blx()->userSession->getUser()->id);
		$entry->title      = blx()->request->getPost('title');
		$entry->slug       = blx()->request->getPost('slug');
		$entry->postDate   = $this->getDateFromPost('postDate');
		$entry->expiryDate = $this->getDateFromPost('expiryDate');
		$entry->enabled    = blx()->request->getPost('enabled');
		$entry->tags       = blx()->request->getPost('tags');

		$fields = blx()->request->getPost('fields');
		$entry->setContent($fields);

		if (blx()->entries->saveEntry($entry))
		{
			if (blx()->request->isAjaxRequest())
			{
				$return['success']   = true;
				$return['entry']     = $entry->getAttributes();
				$return['cpEditUrl'] = $entry->getCpEditUrl();
				$return['author']    = $entry->getAuthor()->getAttributes();
				$return['postDate']  = $entry->postDate->w3cDate();

				$this->returnJson($return);
			}
			else
			{
				blx()->userSession->setNotice(Blocks::t('Entry saved.'));

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
				blx()->userSession->setError(Blocks::t('Couldn’t save entry.'));

				$this->renderRequestedTemplate(array(
					'entry' => $entry
				));
			}
		}
	}

	/**
	 * Deletes an entry.
	 */
	public function actionDeleteEntry()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$entryId = blx()->request->getRequiredPost('id');

		blx()->elements->deleteElementById($entryId);
		$this->returnJson(array('success' => true));
	}
}
