<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\widgets;

use Craft;
use craft\app\enums\AttributeType;
use craft\app\enums\SectionType;
use craft\app\helpers\ArrayHelper;
use craft\app\helpers\JsonHelper;
use craft\app\models\Section as SectionModel;

/**
 * Class QuickPost widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class QuickPost extends BaseWidget
{
	// Properties
	// =========================================================================

	/**
	 * @var bool
	 */
	public $multipleInstances = true;

	/**
	 * @var
	 */
	private $_section;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc ComponentTypeInterface::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('app', 'Quick Post');
	}

	/**
	 * @inheritDoc SavableComponentTypeInterface::getSettingsHtml()
	 *
	 * @return string
	 */
	public function getSettingsHtml()
	{
		// Find the sections the user has permission to create entries in
		$sections = [];

		foreach (Craft::$app->sections->getAllSections() as $section)
		{
			if ($section->type !== SectionType::Single)
			{
				if (Craft::$app->getUser()->checkPermission('createEntries:'.$section->id))
				{
					$sections[] = $section;
				}
			}
		}

		return Craft::$app->templates->render('_components/widgets/QuickPost/settings', [
			'sections' => $sections,
			'settings' => $this->getSettings()
		]);
	}

	/**
	 * Preps the settings before they're saved to the database.
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public function prepSettings($settings)
	{
		$sectionId = $settings['section'];

		if (isset($settings['sections']))
		{
			if (isset($settings['sections'][$sectionId]))
			{
				$settings = array_merge($settings, $settings['sections'][$sectionId]);
			}

			unset($settings['sections']);
		}

		return $settings;
	}

	/**
	 * @inheritDoc WidgetInterface::getTitle()
	 *
	 * @return string
	 */
	public function getTitle()
	{
		if (Craft::$app->getEdition() >= Craft::Client)
		{
			$section = $this->_getSection();

			if ($section)
			{
				return Craft::t('app', 'Post a new {section} entry', ['section' => $section->name]);
			}
		}

		return $this->getName();
	}

	/**
	 * @inheritDoc WidgetInterface::getBodyHtml()
	 *
	 * @return string|false
	 */
	public function getBodyHtml()
	{
		Craft::$app->templates->includeTranslations('Entry saved.', 'Couldn’t save entry.');
		Craft::$app->templates->includeJsResource('js/QuickPostWidget.js');

		$section = $this->_getSection();

		if (!$section)
		{
			return '<p>'.Craft::t('app', 'No section has been selected yet.').'</p>';
		}

		$entryTypes = $section->getEntryTypes('id');

		if (!$entryTypes)
		{
			return '<p>'.Craft::t('app', 'No entry types exist for this section.').'</p>';
		}

		if ($this->getSettings()->entryType && isset($entryTypes[$this->getSettings()->entryType]))
		{
			$entryTypeId = $this->getSettings()->entryType;
		}
		else
		{
			$entryTypeId = ArrayHelper::getFirstValue(array_keys($entryTypes));
		}

		$entryType = $entryTypes[$entryTypeId];

		$params = [
			'sectionId'   => $section->id,
			'typeId' => $entryTypeId,
		];

		Craft::$app->templates->startJsBuffer();

		$html = Craft::$app->templates->render('_components/widgets/QuickPost/body', [
			'section'   => $section,
			'entryType' => $entryType,
			'settings'  => $this->getSettings()
		]);

		$fieldJs = Craft::$app->templates->clearJsBuffer(false);

		Craft::$app->templates->includeJs('new Craft.QuickPostWidget(' .
			$this->model->id.', ' .
			JsonHelper::encode($params).', ' .
			"function() {\n".$fieldJs .
		"\n});");

		return $html;
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
		return [
			'section'   => [AttributeType::Number, 'required' => true],
			'entryType' => AttributeType::Number,
			'fields'    => AttributeType::Mixed,
		];
	}

	// Private Methods
	// =========================================================================

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
				$this->_section = Craft::$app->sections->getSectionById($sectionId);
			}
		}

		return $this->_section;
	}
}
