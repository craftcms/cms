<?php
namespace Craft;

/**
 * Class RecentEntriesWidget
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.widgets
 * @since     1.0
 */
class RecentEntriesWidget extends BaseWidget
{
	// Properties
	// =========================================================================

	/**
	 * @var bool
	 */
	public $multipleInstances = true;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc IComponentType::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Recent Entries');
	}

	/**
	 * @inheritDoc ISavableComponentType::getSettingsHtml()
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
	 * @inheritDoc IWidget::getTitle()
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
					$translatedSectionName = Craft::t($section->name);
					return Craft::t('Recently in {section}', array('section' => $translatedSectionName));
				}
			}
		}

		return Craft::t('Recent Entries');
	}

	/**
	 * @inheritDoc IWidget::getBodyHtml()
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

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseSavableComponentType::defineSettings()
	 *
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'section' => array(AttributeType::Mixed, 'default' => '*'),
			'limit'   => array(AttributeType::Number, 'default' => 10),
		);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns the recent entries, based on the widget settings and user permissions.
	 *
	 * @return array
	 */
	private function _getEntries()
	{
		// Make sure that the user is actually allowed to edit entries in the current locale. Otherwise grab entries in
		// their first editable locale.
		$editableLocaleIds = craft()->i18n->getEditableLocaleIds();
		$targetLocale = craft()->language;

		if (!$editableLocaleIds)
		{
			return array();
		}

		if (!in_array($targetLocale, $editableLocaleIds))
		{
			$targetLocale = $editableLocaleIds[0];
		}

		// Normalize the target section ID value.
		$editableSectionIds = $this->_getEditableSectionIds();
		$targetSectionId = $this->getSettings()->section;

		if (!$targetSectionId || $targetSectionId == '*' || !in_array($targetSectionId, $editableSectionIds))
		{
			$targetSectionId = array_merge($editableSectionIds);
		}

		if (!$targetSectionId)
		{
			return array();
		}

		$criteria = craft()->elements->getCriteria(ElementType::Entry);
		$criteria->status = null;
		$criteria->localeEnabled = null;
		$criteria->locale = $targetLocale;
		$criteria->sectionId = $targetSectionId;
		$criteria->editable = true;
		$criteria->limit = $this->getSettings()->limit;
		$criteria->order = 'elements.dateCreated desc';

		return $criteria->find();
	}

	/**
	 * Returns the Channel and Structure section IDs that the user is allowed to edit.
	 *
	 * @return array
	 */
	private function _getEditableSectionIds()
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
}
