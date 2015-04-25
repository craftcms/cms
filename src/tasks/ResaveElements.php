<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\tasks;

use Craft;
use craft\app\base\Element;
use craft\app\base\Task;
use craft\app\base\ElementInterface;
use craft\app\helpers\StringHelper;

/**
 * ResaveElements represents a Resave Elements background task.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ResaveElements extends Task
{
	// Properties
	// =========================================================================

	/**
	 * @var string|ElementInterface The element type that should be resaved
	 */
	public $elementType;

	/**
	 * @var array The element criteria that determines which elements should be resaved
	 */
	public $criteria;

	/**
	 * @var string
	 */
	private $_localeId;

	/**
	 * @var integer[]
	 */
	private $_elementIds;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function getTotalSteps()
	{
		$class = $this->elementType;

		// Let's save ourselves some trouble and just clear all the caches for this element class
		Craft::$app->getTemplateCache()->deleteCachesByElementType($class);

		// Now find the affected element IDs
		$query = $class::find()
			->configure($this->criteria)
			->offset(null)
			->limit(null)
			->orderBy(null);

		$this->_localeId = $query->locale;
		$this->_elementIds = $query->ids();

		return count($this->_elementIds);
	}

	/**
	 * @inheritdoc
	 */
	public function runStep($step)
	{
		$class = $this->elementType;

		try
		{
			/** @var Element $element */
			$element = $class::find()
				->id($this->_elementIds[$step])
				->locale($this->_localeId)
				->one();

			if (!$element || Craft::$app->getElements()->saveElement($element, false))
			{
				return true;
			}
			else
			{
				$error = 'Encountered the following validation errors when trying to save '.$element::className().' element "'.$element.'" with the ID "'.$element->id.'":';

				foreach ($element->getAllErrors() as $attributeError)
				{
					$error .= "\n - {$attributeError}";
				}

				return $error;
			}
		}
		catch (\Exception $e)
		{
			return 'An exception was thrown while trying to save the '.StringHelper::toLowerCase($class::displayName()).' with the ID “'.$this->_elementIds[$step].'”: '.$e->getMessage();
		}
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	protected function getDefaultDescription()
	{
		return Craft::t('app', 'Resaving {class} elements', [
			'class' => StringHelper::toLowerCase($this->elementType)
		]);
	}
}
