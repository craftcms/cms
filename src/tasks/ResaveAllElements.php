<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\tasks;

use Craft;
use craft\app\enums\AttributeType;

/**
 * The resave all elements task.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ResaveAllElements extends BaseTask
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_elementClasses;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc TaskInterface::getDescription()
	 *
	 * @return string
	 */
	public function getDescription()
	{
		if ($this->getSettings()->localizableOnly)
		{
			return Craft::t('app', 'Resaving all localizable elements');
		}
		else
		{
			return Craft::t('app', 'Resaving all elements');
		}
	}

	/**
	 * @inheritDoc TaskInterface::getTotalSteps()
	 *
	 * @return int
	 */
	public function getTotalSteps()
	{
		$this->_elementClasses = [];
		$localizableOnly = $this->getSettings()->localizableOnly;

		foreach (Craft::$app->elements->getAllElementClasses() as $elementClass)
		{
			if (!$localizableOnly || $elementClass::isLocalized())
			{
				$this->_elementClasses[] = $elementClass::className();
			}
		}

		return count($this->_elementClasses);
	}

	/**
	 * @inheritDoc TaskInterface::runStep()
	 *
	 * @param int $step
	 *
	 * @return bool
	 */
	public function runStep($step)
	{
		return $this->runSubTask('ResaveElements', null, [
			'elementClass' => $this->_elementClasses[$step],
			'criteria' => [
				'locale'        => $this->getSettings()->locale,
				'status'        => null,
				'localeEnabled' => null,
			]
		]);
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
			'locale'          => [AttributeType::Locale, 'default' => Craft::$app->language],
			'localizableOnly' => AttributeType::Bool
		];
	}
}
