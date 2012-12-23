<?php
namespace Blocks;

/**
 * Handles section management tasks
 */
class SectionsController extends BaseEntityController
{
	/**
	 * Returns the block service instance.
	 *
	 * @return SectionBlocksService
	 */
	protected function getService()
	{
		return blx()->sections;
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
		$block->sectionId = blx()->request->getRequiredPost('sectionId');

		return $block;
	}

	/**
	 * Saves a section
	 */
	public function actionSaveSection()
	{
		$this->requirePostRequest();

		$section = new SectionModel();

		$section->id         = blx()->request->getPost('sectionId');
		$section->name       = blx()->request->getPost('name');
		$section->handle     = blx()->request->getPost('handle');
		$section->titleLabel = blx()->request->getPost('titleLabel');
		$section->hasUrls    = (bool) blx()->request->getPost('hasUrls');
		$section->urlFormat  = blx()->request->getPost('urlFormat');
		$section->template   = blx()->request->getPost('template');

		if (blx()->sections->saveSection($section))
		{
			blx()->userSession->setNotice(Blocks::t('Section saved.'));

			$this->redirectToPostedUrl(array(
				'sectionId' => $section->id
			));
		}
		else
		{
			blx()->userSession->setError(Blocks::t('Couldn’t save section.'));
		}

		// Reload the original template
		$this->renderRequestedTemplate(array(
			'section' => $section
		));
	}

	/**
	 * Deletes a section.
	 */
	public function actionDeleteSection()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$sectionId = blx()->request->getRequiredPost('id');

		blx()->sections->deleteSectionById($sectionId);
		$this->returnJson(array('success' => true));
	}
}
