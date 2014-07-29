<?php
namespace Craft;

/**
 * Resave All Elements Task.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.tasks
 * @since     1.0
 */
class ResaveAllElementsTask extends BaseTask
{
	private $_elementTypes;

	/**
	 * Defines the settings.
	 *
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'locale'          => array(AttributeType::Locale, 'default' => craft()->language),
			'localizableOnly' => AttributeType::Bool
		);
	}

	/**
	 * Returns the default description for this task.
	 *
	 * @return string
	 */
	public function getDescription()
	{
		if ($this->getSettings()->localizableOnly)
		{
			return Craft::t('Resaving all localizable elements');
		}
		else
		{
			return Craft::t('Resaving all elements');
		}
	}

	/**
	 * Returns the total number of steps for this task.
	 *
	 * @return int
	 */
	public function getTotalSteps()
	{
		$this->_elementTypes = array();
		$localizableOnly = $this->getSettings()->localizableOnly;

		foreach (craft()->elements->getAllElementTypes() as $elementType)
		{
			if (!$localizableOnly || $elementType->isLocalized())
			{
				$this->_elementTypes[] = $elementType->getClassHandle();
			}
		}

		return count($this->_elementTypes);
	}

	/**
	 * Runs a task step.
	 *
	 * @param int $step
	 * @return bool
	 */
	public function runStep($step)
	{
		return $this->runSubTask('ResaveElements', null, array(
			'elementType' => $this->_elementTypes[$step],
			'criteria' => array(
				'locale'        => $this->getSettings()->locale,
				'status'        => null,
				'localeEnabled' => null,
			)
		));
	}
}
