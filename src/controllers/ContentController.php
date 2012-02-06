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

		$section->site_id = Blocks::app()->sites->currentSite->id;

		$section->name = Blocks::app()->request->getPost('name');
		$section->handle = Blocks::app()->request->getPost('handle');

		$maxEntries = Blocks::app()->request->getPost('max_entries');
		$section->max_entries = ($maxEntries ? $maxEntries : null);

		$section->sortable = (Blocks::app()->request->getPost('sortable') === 'y');

		$urlFormat = Blocks::app()->request->getPost('url_format');
		$section->url_format = ($urlFormat ? $urlFormat : null);

		$template = Blocks::app()->request->getPost('template');
		$section->template = ($template ? $template : null);

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
