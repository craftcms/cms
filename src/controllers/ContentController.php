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
		$sectionId = Blocks::app()->request->getPost('id');
		if ($sectionId)
			$section = Blocks::app()->content->getSectionById($sectionId);

		// Otherwise create a new section
		if (empty($section))
			$section = new Section;

		$section->name = Blocks::app()->request->getPost('name');
		$section->handle = Blocks::app()->request->getPost('handle');

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
