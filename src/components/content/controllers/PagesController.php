<?php
namespace Blocks;

/**
 * Handles page management tasks
 */
class PagesController extends BaseController
{
	/**
	 * Saves a page.
	 */
	public function actionSavePage()
	{
		$this->requirePostRequest();

		$page = new PageModel();
		$page->id = blx()->request->getPost('pageId');

		$page->title = blx()->request->getPost('title');
		$page->uri = blx()->request->getPost('uri');
		$page->template = blx()->request->getPost('template');

		if (blx()->pages->savePage($page))
		{
			blx()->user->setNotice(Blocks::t('Page saved.'));

			$this->redirectToPostedUrl(array(
				'pageId' => $page->id
			));
		}
		else
		{
			blx()->user->setError(Blocks::t('Couldn’t save page.'));

			$this->renderRequestedTemplate(array(
				'page' => $page
			));
		}
	}

	/**
	 * Saves a page's content.
	 */
	public function actionSavePageContent()
	{
		$this->requirePostRequest();

		$pageId = blx()->request->getRequiredPost('pageId');
		$page = blx()->pages->getPageById($pageId);

		$page->setBlockValues(blx()->request->getPost('blocks'));

		if (blx()->pages->savePageContent($page))
		{
			blx()->user->setNotice(Blocks::t('Page saved.'));

			$this->redirectToPostedUrl(array(
				'pageId' => $page->id
			));
		}
		else
		{
			blx()->user->setError(Blocks::t('Couldn’t save page.'));

			$this->renderRequestedTemplate(array(
				'content' => $content
			));
		}
	}

	/**
	 * Deletes a page.
	 */
	public function actionDeletePage()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$pageId = blx()->request->getRequiredPost('id');

		blx()->pages->deletePageById($pageId);
		$this->returnJson(array('success' => true));
	}
}
