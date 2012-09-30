<?php
namespace Blocks;

/**
 *
 */
class RecentEntriesWidget extends BaseWidget
{
	public $multipleInstances = true;

	/**
	 * Returns the type of widget this is.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Blocks::t('Recent Entries');
	}

	/**
	 * Defines the settings.
	 *
	 * @access protected
	 * @return array
	 */
	protected function defineSettings()
	{
		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			$settings['section'] = array(AttributeType::Mixed, 'default' => '*');
		}

		$settings['limit'] = array(AttributeType::Number, 'default' => 10);

		return $settings;
	}

	/**
	 * Returns the widget's body HTML.
	 *
	 * @return string
	 */
	public function getSettingsHtml()
	{
		return blx()->templates->render('_components/widgets/RecentEntries/settings', array(
			'settings' => $this->getSettings()
		));
	}

	/**
	 * Gets the widget's title.
	 *
	 * @return string
	 */
	public function getTitle()
	{
		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			$sectionId = $this->getSettings()->section;
			if (is_numeric($sectionId))
			{
				$section = blx()->sections->getSectionById($sectionId);
				if ($section)
				{
					return Blocks::t('Recently in {section}', array('section' => $section->name));
				}
			}
		}

		return Blocks::t('Recent Entries');
	}

	/**
	 * Gets the widget's body HTML.
	 *
	 * @return string
	 */
	public function getBodyHtml()
	{
		$params = array();

		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			$sectionId = $this->getSettings()->section;
			if (is_numeric($sectionId))
			{
				$params['sectionId'] = (int)$sectionId;
			}
		}

		$js = 'new Blocks.RecentEntriesWidget('.$this->model->id.', '.JsonHelper::encode($params).');';

		blx()->templates->includeJsResource('js/RecentEntriesWidget.js');
		blx()->templates->includeJs($js);
		blx()->templates->includeTranslations('by {author}');

		return blx()->templates->render('_components/widgets/RecentEntries/body', array(
			'settings' => $this->getSettings()
		));
	}
}
