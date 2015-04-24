<?php
namespace Craft;

/**
 * Update Element Slugs and URIs Task
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.tasks
 * @since     2.0
 */
class UpdateElementSlugsAndUrisTask extends BaseTask
{
	// Properties
	// =========================================================================

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
		return Craft::t('Updating element slugs and URIs');
	}

	/**
	 * @inheritDoc ITask::getTotalSteps()
	 *
	 * @return int
	 */
	public function getTotalSteps()
	{
		$this->_elementIds = (array) $this->getSettings()->elementId;
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
		$elementsService = craft()->elements;
		$settings = $this->getSettings();
		$element = $elementsService->getElementById($this->_elementIds[$step], $settings->elementType, $settings->locale);

		$oldSlug = $element->slug;
		$oldUri = $element->uri;

		$elementsService->updateElementSlugAndUri($element, $settings->updateOtherLocales, false, false);

		// Only go deeper if something just changed
		if ($settings->updateDescendants && ($element->slug !== $oldSlug || $element->uri !== $oldUri))
		{
			$criteria = $elementsService->getCriteria($element->getElementType());
			$criteria->descendantOf = $element;
			$criteria->descendantDist = 1;
			$criteria->status = null;
			$criteria->localeEnabled = null;
			$criteria->locale = $element->locale;
			$childIds = $criteria->ids();

			if ($childIds)
			{
				$this->runSubTask('UpdateElementSlugsAndUris', Craft::t('Updating children'), array(
					'elementId'          => $childIds,
					'elementType'        => $settings->elementType,
					'locale'             => $settings->locale,
					'updateOtherLocales' => $settings->updateOtherLocales,
					'updateDescendants'  => true,
				));
			}
		}

		return true;
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
			'elementId' => AttributeType::Number,
			'elementType' => AttributeType::String,
			'locale' => AttributeType::Locale,
			'updateOtherLocales' => array(AttributeType::Bool, 'default' => true),
			'updateDescendants' => array(AttributeType::Bool, 'default' => true),
		);
	}
}
