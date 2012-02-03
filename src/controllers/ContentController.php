<?php
namespace Blocks;

/**
 * Handles site management tasks
 */
class ContentController extends BaseController
{
	/**
	 * Saves a site
	 */
	public function actionSaveSection()
	{
		$this->requirePostRequest();

		// Are we editing an existing section?
		$postSectionId = Blocks::app()->request->getPost('section_id');
		if ($postSectionId)
			$section = Blocks::app()->content->getSectionById($postSectionId);

		// Otherwise create a new section
		if (empty($section))
			$section = new Section;

		$postSection = Blocks::app()->request->getPost('section');
		$section->name = $postSection['name'];
		$section->handle = $postSection['handle'];

		if ($section->save())
		{
			Blocks::app()->user->setMessage(MessageStatus::Success, 'Section saved successfully.');

			$url = Blocks::app()->request->getPost('redirect');
			if ($url !== null)
				$this->redirect($url);
		}

		$this->loadRequestedTemplate(array('section' => $section));
	}
}
