<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\tasks;

use Craft;
use craft\app\base\ElementInterface;
use craft\app\enums\AttributeType;
use craft\app\helpers\StringHelper;

/**
 * The resave elements task.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ResaveElements extends BaseTask
{
	// Properties
	// =========================================================================

	/**
	 * @var ElementInterface
	 */
	private $_elementClass;

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
	 * @inheritDoc TaskInterface::getDescription()
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return Craft::t('app', 'Resaving {class} elements', [
			'class' => StringHelper::toLowerCase($this->getSettings()->elementClass)
		]);
	}

	/**
	 * @inheritDoc TaskInterface::getTotalSteps()
	 *
	 * @return int
	 */
	public function getTotalSteps()
	{
		$settings = $this->getSettings();
		/** @var ElementInterface $class */
		$class = $settings->elementClass;

		// Let's save ourselves some trouble and just clear all the caches for this element class
		Craft::$app->templateCache->deleteCachesByElementClass($class);

		// Now find the affected element IDs
		$query = $class::find()
			->configure($settings->criteria)
			->offset(null)
			->limit(null)
			->order(null);

		$this->_elementClass = $class;
		$this->_localeId = $query->locale;
		$this->_elementIds = $query->ids();

		return count($this->_elementIds);
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
		try
		{
			$class = $this->_elementClass;
			$element = $class::find()
				->id($this->_elementIds[$step])
				->locale($this->_localeId)
				->one();

			if (!$element || Craft::$app->elements->saveElement($element, false))
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
			return 'An exception was thrown while trying to save the '.$this->_elementClass.' with the ID “'.$this->_elementIds[$step].'”: '.$e->getMessage();
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
		return [
			'elementClass' => AttributeType::String,
			'criteria'     => AttributeType::Mixed,
		];
	}
}
