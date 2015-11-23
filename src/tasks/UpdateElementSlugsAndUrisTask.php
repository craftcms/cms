<?php
namespace Craft;

/**
 * Update Element Slugs and URIs Task
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
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

	/**
	 * @var
	 */
	private $_skipRemainingEntries;

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
		$this->_skipRemainingEntries = false;

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
		if ($this->_skipRemainingEntries)
		{
			return true;
		}

		$elementsService = craft()->elements;
		$settings = $this->getSettings();
		$element = $elementsService->getElementById($this->_elementIds[$step], $settings->elementType, $settings->locale);

		// Make sure they haven't deleted this element
		if (!$element)
		{
			return true;
		}

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
		else if ($step === 0)
		{
			// Don't bother updating the other entries
			$this->_skipRemainingEntries = true;
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
