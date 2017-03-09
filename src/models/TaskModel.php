<?php
namespace Craft;

/**
 * Class TaskModel
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.models
 * @since     2.0
 */
class TaskModel extends BaseComponentModel
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
			$this->_taskType = craft()->components->populateComponentByTypeAndModel(ComponentType::Task, $this);

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
		return array(
			'id'          => $this->id,
			'level'       => $this->level,
			'description' => $this->getDescription(),
			'status'      => $this->status,
			'progress'    => $this->getProgress(),
			'age'         => time() - $this->dateUpdated->getTimestamp(),
		);
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
		return array_merge(parent::defineAttributes(), array(
			'level'       => AttributeType::Number,
			'description' => AttributeType::String,
			'parentId'    => AttributeType::Mixed,
			'totalSteps'  => AttributeType::Number,
			'currentStep' => AttributeType::Number,
			'status'      => array(AttributeType::Enum, 'values' => array(TaskStatus::Pending, TaskStatus::Error, TaskStatus::Running), 'default' => TaskStatus::Pending),
			'dateCreated' => AttributeType::DateTime,
			'dateUpdated' => AttributeType::DateTime,
		));
	}
}
