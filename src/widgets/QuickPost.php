<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\widgets;

use Craft;
use craft\app\base\Widget;
use craft\app\helpers\ArrayHelper;
use craft\app\helpers\JsonHelper;
use craft\app\models\Section;
use craft\app\web\View;

/**
 * QuickPost represents a Quick Post dashboard widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class QuickPost extends Widget
{
	// Static
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public static function displayName()
	{
		return Craft::t('app', 'Quick Post');
	}

	/**
	 * @inheritdoc
	 */
	public static function populateModel($model, $config)
	{
		// If we're saving the widget settings, all of the section-specific
		// attributes will be tucked away in a 'sections' array
		if (isset($config['sections'], $config['section']))
		{
			$sectionId = $config['section'];

			if (isset($config['sections'][$sectionId]))
			{
				$config = array_merge($config, $config['sections'][$sectionId]);
			}

			unset($config['sections']);
		}

		parent::populateModel($model, $config);
	}

	// Properties
	// =========================================================================

	/**
	 * @var integer The ID of the section that the widget should post to
	 */
	public $section;

	/**
	 * @var integer The ID of the entry type that the widget should create
	 */
	public $entryType;

	/**
	 * @var integer[] The IDs of the fields that the widget should show
	 */
	public $fields;

	/**
	 * @var
	 */
	private $_section;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		$rules = parent::rules();
		$rules[] = [['section'], 'required'];
		$rules[] = [['section', 'entryType'], 'integer'];
		return $rules;
	}

	/**
	 * @inheritdoc
	 */
	public function getSettingsHtml()
	{
		// Find the sections the user has permission to create entries in
		$sections = [];

		foreach (Craft::$app->getSections()->getAllSections() as $section)
		{
			if ($section->type !== Section::TYPE_SINGLE)
			{
				if (Craft::$app->getUser()->checkPermission('createEntries:'.$section->id))
				{
					$sections[] = $section;
				}
			}
		}

		return Craft::$app->getView()->renderTemplate('_components/widgets/QuickPost/settings', [
			'sections' => $sections,
			'widget' => $this
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function getTitle()
	{
		if (Craft::$app->getEdition() >= Craft::Client)
		{
			$section = $this->_getSection();

			if ($section !== null)
			{
				return Craft::t('app', 'Post a new {section} entry', ['section' => $section->name]);
			}
		}

		return self::displayName();
	}

	/**
	 * @inheritdoc
	 */
	public function getBodyHtml()
	{
		Craft::$app->getView()->includeTranslations('Entry saved.', 'Couldn’t save entry.');
		Craft::$app->getView()->registerJsResource('js/QuickPostWidget.js');

		$section = $this->_getSection();

		if ($section === null)
		{
			return '<p>'.Craft::t('app', 'No section has been selected yet.').'</p>';
		}

		$entryTypes = $section->getEntryTypes('id');

		if (!$entryTypes)
		{
			return '<p>'.Craft::t('app', 'No entry types exist for this section.').'</p>';
		}

		if ($this->entryType && isset($entryTypes[$this->entryType]))
		{
			$entryTypeId = $this->entryType;
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

		Craft::$app->getView()->startJsBuffer();

		$html = Craft::$app->getView()->renderTemplate('_components/widgets/QuickPost/body', [
			'section'   => $section,
			'entryType' => $entryType,
			'widget'  => $this
		]);

		$lines = [];
		$fieldJs = Craft::$app->getView()->clearJsBuffer(false);

		foreach ([View::POS_HEAD, View::POS_BEGIN, View::POS_END, View::POS_LOAD, View::POS_READY] as $pos)
		{
			if (!empty($fieldJs[$pos]))
			{
				$lines[] = implode("\n", $fieldJs[$pos]);
			}
		}

		$fieldJs = empty($lines) ? '' : implode("\n", $lines);

		Craft::$app->getView()->registerJs('new Craft.QuickPostWidget(' .
			$this->id.', ' .
			JsonHelper::encode($params).', ' .
			"function() {\n".$fieldJs .
		"\n});");

		return $html;
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns the widget's section.
	 *
	 * @return Section|null
	 */
	private function _getSection()
	{
		if ($this->_section === null)
		{
			if ($this->section)
			{
				$this->_section = Craft::$app->getSections()->getSectionById($this->section);
			}

			if ($this->_section === null)
			{
				$this->_section = false;
			}
		}

		return $this->_section ?: null;
	}
}
