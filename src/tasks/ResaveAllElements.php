<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\tasks;

use Craft;
use craft\app\base\Task;

/**
 * ResaveAllElements represents a Resave All Elements background task.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ResaveAllElements extends Task
{
	// Properties
	// =========================================================================

	/**
	 * @var string The locale ID to fetch the elements in
	 */
	public $locale;

	/**
	 * @var string Whether only localizable elements should be resaved
	 */
	public $localizableOnly;

	/**
	 * @var
	 */
	private $_elementType;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		if ($this->locale === null)
		{
			$this->locale = Craft::$app->language;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function getTotalSteps()
	{
		$this->_elementType = [];
		$localizableOnly = $this->localizableOnly;

		foreach (Craft::$app->getElements()->getAllElementTypes() as $elementType)
		{
			if (!$localizableOnly || $elementType::isLocalized())
			{
				$this->_elementType[] = $elementType::className();
			}
		}

		return count($this->_elementType);
	}

	/**
	 * @inheritdoc
	 */
	public function runStep($step)
	{
		return $this->runSubTask([
			'type'        => ResaveElements::className(),
			'elementType' => $this->_elementType[$step],
			'criteria'    => ['locale' => $this->locale, 'status' => null, 'localeEnabled' => null]
		]);
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	protected function getDefaultDescription()
	{
		if ($this->localizableOnly)
		{
			return Craft::t('app', 'Resaving all localizable elements');
		}
		else
		{
			return Craft::t('app', 'Resaving all elements');
		}
	}
}
