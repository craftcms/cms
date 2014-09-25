<?php
namespace Craft;

/**
 * Find and Replace task.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.tasks
 * @since     2.0
 */
class FindAndReplaceTask extends BaseTask
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_table;

	/**
	 * @var
	 */
	private $_textColumns;

	/**
	 * @var
	 */
	private $_matrixFieldIds;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc ITask::getDescription()
	 *
	 * @return string
	 */
	public function getDescription()
	{
		$settings = $this->getSettings();

		return Craft::t('Replacing “{find}” with “{replace}”', array(
			'find'    => $settings->find,
			'replace' => $settings->replace
		));
	}

	/**
	 * @inheritDoc ITask::getTotalSteps()
	 *
	 * @return int
	 */
	public function getTotalSteps()
	{
		$this->_textColumns = array();
		$this->_matrixFieldIds = array();

		// Is this for a Matrix field?
		$matrixFieldId = $this->getSettings()->matrixFieldId;

		if ($matrixFieldId)
		{
			$matrixField = craft()->fields->getFieldById($matrixFieldId);

			if (!$matrixField || $matrixField->type != 'Matrix')
			{
				return 0;
			}

			$this->_table = craft()->matrix->getContentTableName($matrixField);

			$blockTypes = craft()->matrix->getBlockTypesByFieldId($matrixFieldId);

			foreach ($blockTypes as $blockType)
			{
				$fieldColumnPrefix = 'field_'.$blockType->handle.'_';

				foreach ($blockType->getFields() as $field)
				{
					$this->_checkField($field, $fieldColumnPrefix);
				}
			}
		}
		else
		{
			$this->_table = 'content';

			foreach (craft()->fields->getAllFields() as $field)
			{
				$this->_checkField($field, 'field_');
			}
		}

		return count($this->_textColumns) + count($this->_matrixFieldIds);
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
		$settings = $this->getSettings();

		// If replace is null, there is invalid settings JSON in the database. Guard against it so we don't
		// inadvertently nuke textual content in the database.
		if ($settings->replace !== null)
		{
			if (isset($this->_textColumns[$step]))
			{
				craft()->db->createCommand()->replace($this->_table, $this->_textColumns[$step], $settings->find, $settings->replace);
				return true;
			}
			else
			{
				$step -= count($this->_textColumns);

				if (isset($this->_matrixFieldIds[$step]))
				{
					$field = craft()->fields->getFieldById($this->_matrixFieldIds[$step]);

					if ($field)
					{
						return $this->runSubTask('FindAndReplace', Craft::t('Working in Matrix field “{field}”', array('field' => $field->name)), array(
							'find'          => $settings->find,
							'replace'       => $settings->replace,
							'matrixFieldId' => $field->id
						));
					}
					else
					{
						// Oh what the hell.
						return true;
					}
				}
				else
				{
					return false;
				}
			}
		}
		else
		{
			Craft::log('Invalid "replace" in the Find and Replace task probably caused by invalid JSON in the database.', LogLevel::Error);
			return false;
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
			'find'          => AttributeType::String,
			'replace'       => AttributeType::String,
			'matrixFieldId' => AttributeType::String,
		);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Checks whether the given field is saving data into a textual column, and saves it accordingly.
	 *
	 * @param FieldModel $field
	 * @param string     $fieldColumnPrefix
	 *
	 * @return bool
	 */
	private function _checkField(FieldModel $field, $fieldColumnPrefix)
	{
		if ($field->type == 'Matrix')
		{
			$this->_matrixFieldIds[] = $field->id;
		}
		else
		{
			$fieldType = $field->getFieldType();

			if ($fieldType)
			{
				$attributeConfig = $fieldType->defineContentAttribute();

				if ($attributeConfig && $attributeConfig != AttributeType::Number)
				{
					$attributeConfig = ModelHelper::normalizeAttributeConfig($attributeConfig);

					if ($attributeConfig['type'] == AttributeType::String)
					{
						$this->_textColumns[] = $fieldColumnPrefix.$field->handle;
					}
				}
			}
		}
	}
}
