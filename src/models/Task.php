<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\enums\AttributeType;
use craft\app\enums\ComponentType;
use craft\app\enums\TaskStatus;
use craft\app\tasks\BaseTask;

/**
 * Class Task model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Task extends BaseComponentModel
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_taskType;

	// Public Methods
	// =========================================================================

	/**
	 * Returns the task's description.
	 *
	 * @return string
	 */
	public function getDescription()
	{
		// Was a description explicitly set when creating this task?
		$description = $this->getAttribute('description');

		if (!$description)
		{
			$taskType = $this->getTaskType();

			if ($taskType)
			{
				$description = $taskType->getDescription();
			}
		}

		if (!$description)
		{
			$description = $this->type;
		}

		return $description;
	}

	/**
	 * Returns the task's progress.
	 *
	 * @return float|null
	 */
	public function getProgress()
	{
		if ($this->totalSteps && $this->currentStep)
		{
			return $this->currentStep / $this->totalSteps;
		}
		else
		{
			return 0;
		}
	}

	/**
	 * Returns the field type this field is using.
	 *
	 * @return BaseTask|null
	 */
	public function getTaskType()
	{
		if (!isset($this->_taskType))
		{
			$this->_taskType = Craft::$app->components->populateComponentByTypeAndModel(ComponentType::Task, $this);

			// Might not actually exist
			if (!$this->_taskType)
			{
				$this->_taskType = false;
			}
		}

		// Return 'null' instead of 'false' if it doesn't exist
		if ($this->_taskType)
		{
			return $this->_taskType;
		}
	}

	/**
	 * Returns info about the task for JS.
	 *
	 * @return array
	 */
	public function getInfo()
	{
		return [
			'id'          => $this->id,
			'level'       => $this->level,
			'description' => $this->getDescription(),
			'status'      => $this->status,
			'progress'    => $this->getProgress(),
		];
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseModel::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array_merge(parent::defineAttributes(), [
			'level'       => AttributeType::Number,
			'description' => AttributeType::String,
			'parentId'    => AttributeType::Mixed,
			'totalSteps'  => AttributeType::Number,
			'currentStep' => AttributeType::Number,
			'status'      => [AttributeType::Enum, 'values' => [TaskStatus::Pending, TaskStatus::Error, TaskStatus::Running], 'default' => TaskStatus::Pending],
		]);
	}
}
