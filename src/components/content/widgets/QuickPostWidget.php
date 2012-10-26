<?php
namespace Blocks;

/**
 *
 */
class QuickPostWidget extends BaseWidget
{
	public $multipleInstances = true;

	/**
	 * Returns the type of widget this is.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Blocks::t('Quick Post');
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
			$settings['section'] = array(AttributeType::Number, 'required' => true);
		}

		$settings['blocks'] = AttributeType::Mixed;

		return $settings;
	}

	/**
	 * Returns the widget's body HTML.
	 *
	 * @return string
	 */
	public function getSettingsHtml()
	{
		return blx()->templates->render('_components/widgets/QuickPost/settings', array(
			'settings' => $this->getSettings()
		));
	}

	/**
	 * Preps the settings before they're saved to the database.
	 *
	 * @param array $settings
	 * @return array
	 */
	public function prepSettings($settings)
	{
		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			$sectionId = $settings['section'];

			if (isset($settings['blocks']['section'.$sectionId]))
			{
				$settings['blocks'] = $settings['blocks']['section'.$sectionId];
			}
		}

		return $settings;
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
			$section = blx()->sections->getSectionById($this->getSettings()->section);

			if ($section)
			{
				return Blocks::t('Post a new {section} entry', array('section' => $section->name));
			}
		}
		else
		{
			return Blocks::t('Post a new blog entry');
		}
	}

	/**
	 * Gets the widget's body HTML.
	 *
	 * @return string
	 */
	public function getBodyHtml()
	{
		blx()->templates->includeTranslations('Entry saved.', 'Couldn’t save entry.');
		blx()->templates->includeJsResource('js/QuickPostWidget.js');

		$params = array();

		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			$sectionId = $this->getSettings()->section;

			if (is_numeric($sectionId))
			{
				$params['sectionId'] = (int)$sectionId;
			}
		}

		blx()->templates->includeJs('new Blocks.QuickPostWidget('.$this->model->id.', '.JsonHelper::encode($params).', function() {');
		$html = blx()->templates->render('_components/widgets/QuickPost/body', array(
			'settings' => $this->getSettings()
		));

		blx()->templates->includeJs('});');

		return $html;
	}
}
