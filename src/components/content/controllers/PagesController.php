<?php
namespace Blocks;

/**
 * Handles page management tasks
 */
class PagesController extends BaseEntityController
{
	/**
	 * Returns the block service instance.
	 *
	 * @return PagesService
	 */
	protected function getService()
	{
		return blx()->pages;
	}

	/**
	 * Populates a block model from post.
	 *
	 * @access protected
	 * @return EntryBlockModel
	 */
	protected function populateBlockFromPost()
	{
		$block = parent::populateBlockFromPost();
		$block->pageId = blx()->request->getRequiredPost('pageId');

		return $block;
	}

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
			blx()->userSession->setNotice(Blocks::t('Page saved.'));

			$this->redirectToPostedUrl(array(
				'pageId' => $page->id
			));
		}
		else
		{
			blx()->userSession->setError(Blocks::t('Couldn’t save page.'));

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

		$page->setContent(blx()->request->getPost('blocks'));

		if (blx()->pages->savePageContent($page))
		{
			blx()->userSession->setNotice(Blocks::t('Page saved.'));

			$this->redirectToPostedUrl(array(
				'pageId' => $page->id
			));
		}
		else
		{
			blx()->userSession->setError(Blocks::t('Couldn’t save page.'));

			$this->renderRequestedTemplate(array(
				'page' => $page
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
