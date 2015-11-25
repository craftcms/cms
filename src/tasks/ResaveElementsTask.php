<?php
namespace Craft;

/**
 * Resave Elements Task
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.tasks
 * @since     2.0
 */
class ResaveElementsTask extends BaseTask
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_elementType;

	/**
	 * @var
	 */
	private $_localeId;

	/**
	 * @var
	 */
	private $_elementIds;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc ITask::getDescription()
	 *
	 * @return string
	 */
	public function getDescription()
	{
		$elementType = craft()->elements->getElementType($this->getSettings()->elementType);

		return Craft::t('Resaving {type}', array(
			'type' => StringHelper::toLowerCase($elementType->getName())
		));
	}

	/**
	 * @inheritDoc ITask::getTotalSteps()
	 *
	 * @return int
	 */
	public function getTotalSteps()
	{
		$settings = $this->getSettings();

		// Let's save ourselves some trouble and just clear all the caches for this element type
		craft()->templateCache->deleteCachesByElementType($settings->elementType);

		// Now find the affected element IDs
		$criteria = craft()->elements->getCriteria($settings->elementType, $settings->criteria);
		$criteria->offset = null;
		$criteria->limit = null;
		$criteria->order = null;

		$this->_elementType = $criteria->getElementType()->getClassHandle();
		$this->_localeId = $criteria->locale;
		$this->_elementIds = $criteria->ids();

		return count($this->_elementIds);
	}

	/**
	 * @inheritDoc ITask::runStep()
	 *
	 * @param int $step
	 *
	 * @return bool
	 */
	public function runStep($step)
	{
		try
		{
			$element = craft()->elements->getElementById($this->_elementIds[$step], $this->_elementType, $this->_localeId);

			if (!$element || craft()->elements->saveElement($element, false))
			{
				return true;
			}
			else
			{
				$error = 'Encountered the following validation errors when trying to save '.strtolower($element->getElementType()).' element "'.$element.'" with the ID "'.$element->id.'":';

				foreach ($element->getAllErrors() as $attributeError)
				{
					$error .= "\n - {$attributeError}";
				}

				return $error;
			}
		}
		catch (\Exception $e)
		{
			return 'An exception was thrown while trying to save the '.$this->_elementType.' with the ID “'.$this->_elementIds[$step].'”: '.$e->getMessage();
		}
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
			'elementType' => AttributeType::String,
			'criteria'    => AttributeType::Mixed,
		);
	}
}
