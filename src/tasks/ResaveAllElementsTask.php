<?php
namespace Craft;

/**
 * Resave All Elements Task
 */
class ResaveAllElementsTask extends BaseTask
{
	private $_elementTypes;

	/**
	 * Returns the default description for this task.
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return Craft::t('Resaving all elements');
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
			'locale' => array(AttributeType::Locale, 'default' => craft()->language),
		);
	}

	/**
	 * Returns the total number of steps for this task.
	 *
	 * @return int
	 */
	public function getTotalSteps()
	{
		$this->_elementTypes = array();

		foreach (craft()->elements->getAllElementTypes() as $elementType)
		{
			$this->_elementTypes[] = $elementType->getClassHandle();
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
