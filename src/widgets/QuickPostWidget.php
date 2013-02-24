<?php
namespace Craft;

/**
 *
 */
class QuickPostWidget extends BaseWidget
{
	public $multipleInstances = true;

	private $_section;

	/**
	 * Returns the type of widget this is.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Quick Post');
	}

	/**
	 * Defines the settings.
	 *
	 * @access protected
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'section' => array(AttributeType::Number, 'required' => true),
			'fields'  => AttributeType::Mixed,
		);
	}

	/**
	 * Returns the widget's body HTML.
	 *
	 * @return string
	 */
	public function getSettingsHtml()
	{
		return craft()->templates->render('_components/widgets/QuickPost/settings', array(
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
		$sectionId = $settings['section'];

		if (isset($settings['fields']['section'.$sectionId]))
		{
			$settings['fields'] = $settings['fields']['section'.$sectionId];
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
		$section = $this->_getSection();

		if ($section)
		{
			return Craft::t('Post a new {section} entry', array('section' => $section->name));
		}
	}

	/**
	 * Gets the widget's body HTML.
	 *
	 * @return string
	 */
	public function getBodyHtml()
	{
		craft()->templates->includeTranslations('Entry saved.', 'Couldn’t save entry.');
		craft()->templates->includeJsResource('js/QuickPostWidget.js');

		$section = $this->_getSection();

		if (!$section)
		{
			return '<p>'.Craft::t('No section has been selected yet.').'</p>';
		}

		$params = array('sectionId' => $section->id);
		craft()->templates->includeJs('new Craft.QuickPostWidget('.$this->model->id.', '.JsonHelper::encode($params).', function() {');

		$html = craft()->templates->render('_components/widgets/QuickPost/body', array(
			'section'  => $section,
			'settings' => $this->getSettings()
		));

		craft()->templates->includeJs('});');

		return $html;
	}

	/**
	 * Returns the widget's section.
	 *
	 * @return SectionModel|false
	 */
	private function _getSection()
	{
		if (!isset($this->_section))
		{
			$this->_section = false;

			$sectionId = $this->getSettings()->section;

			if ($sectionId)
			{
				$this->_section = craft()->sections->getSectionById($sectionId);
			}
		}

		return $this->_section;
	}
}
