<?php
namespace Craft;

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
		return Craft::t('Recent Entries');
	}

	/**
	 * Defines the settings.
	 *
	 * @access protected
	 * @return array
	 */
	protected function defineSettings()
	{
		if (craft()->getEdition() >= Craft::Client)
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
		return craft()->templates->render('_components/widgets/RecentEntries/settings', array(
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
		if (craft()->getEdition() >= Craft::Client)
		{
			$sectionId = $this->getSettings()->section;

			if (is_numeric($sectionId))
			{
				$section = craft()->sections->getSectionById($sectionId);

				if ($section)
				{
					return Craft::t('Recently in {section}', array('section' => $section->name));
				}
			}
		}

		return Craft::t('Recent Entries');
	}

	/**
	 * Returns the widget's body HTML.
	 *
	 * @return string|false
	 */
	public function getBodyHtml()
	{
		$params = array();

		if (craft()->getEdition() >= Craft::Client)
		{
			$sectionId = $this->getSettings()->section;

			if (is_numeric($sectionId))
			{
				$params['sectionId'] = (int)$sectionId;
			}
		}

		$js = 'new Craft.RecentEntriesWidget('.$this->model->id.', '.JsonHelper::encode($params).');';

		craft()->templates->includeJsResource('js/RecentEntriesWidget.js');
		craft()->templates->includeJs($js);
		craft()->templates->includeTranslations('by {author}');

		$entries = $this->_getEntries();

		return craft()->templates->render('_components/widgets/RecentEntries/body', array(
			'entries' => $entries
		));
	}

	/**
	 *
	 */
	private function _getEntries()
	{
		$sectionIds = $this->_getSectionIds();

		$somethingToDisplay = false;

		// If they have Client or Pro installed, only display the sections they are allowed to edit.
		if (craft()->getEdition() >= Craft::Client)
		{
			if ($this->getSettings()->section == '*' || in_array($this->getSettings()->section, $sectionIds))
			{
				$somethingToDisplay = true;
			}
		}

		// If they don't have Client or Pro, OR they have Client/Pro and have permission to edit sections in it.
		if ((craft()->getEdition() == Craft::Personal || (craft()->getEdition() >= Craft::Client && $somethingToDisplay)) && count($sectionIds) > 0)
		{
			$criteria = $this->_getCriteria($sectionIds);
			$entries = $criteria->find();
		}
		else
		{
			$entries = array();
		}

		return $entries;
	}

	/**
	 * @return array
	 */
	private function _getSectionIds()
	{
		$sectionIds = array();

		foreach (craft()->sections->getEditableSections() as $section)
		{
			if ($section->type != SectionType::Single)
			{
				$sectionIds[] = $section->id;
			}
		}

		return $sectionIds;
	}

	/**
	 * @param $sectionIds
	 * @throws Exception
	 * @return ElementCriteriaModel
	 */
	private function _getCriteria($sectionIds)
	{
		$criteria = craft()->elements->getCriteria(ElementType::Entry);
		$criteria->status = null;
		$criteria->localeEnabled = null;
		$criteria->limit = $this->getSettings()->limit;
		$criteria->order = 'dateCreated DESC';

		// Section is only defined if Client/Pro is installed.
		if (craft()->getEdition() >= Craft::Client)
		{
			if ($this->getSettings()->section == '*')
			{
				$criteria->sectionId = $sectionIds;
			}
			else
			{
				$criteria->sectionId = $this->getSettings()->section;
			}
		}
		else
		{
			$criteria->sectionId = $sectionIds;
		}

		return $criteria;
	}
}
