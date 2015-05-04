<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\tasks;

use Craft;
use craft\app\base\Field;
use craft\app\base\Task;
use craft\app\base\FieldInterface;
use craft\app\fields\Matrix;

/**
 * FindAndReplace represents a Find and Replace background task.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FindAndReplace extends Task
{
	// Properties
	// =========================================================================

	/**
	 * @var string The search text
	 */
	public $find;

	/**
	 * @var string The replacement text
	 */
	public $replace;

	/**
	 * @var integer The Matrix field ID, if searching against a Matrix field’s content
	 */
	public $matrixFieldId;

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
	 * @inheritdoc
	 */
	public function getTotalSteps()
	{
		$this->_textColumns = [];
		$this->_matrixFieldIds = [];

		// Is this for a Matrix field?
		if ($this->matrixFieldId)
		{
			/** @var Matrix $matrixField */
			$matrixField = Craft::$app->getFields()->getFieldById($this->matrixFieldId);

			if (!$matrixField || $matrixField->type != 'Matrix')
			{
				return 0;
			}

			$this->_table = Craft::$app->getMatrix()->getContentTableName($matrixField);

			$blockTypes = Craft::$app->getMatrix()->getBlockTypesByFieldId($this->matrixFieldId);

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
			$this->_table = '{{%content}}';

			foreach (Craft::$app->getFields()->getAllFields() as $field)
			{
				$this->_checkField($field, 'field_');
			}
		}

		return count($this->_textColumns) + count($this->_matrixFieldIds);
	}

	/**
	 * @inheritdoc
	 */
	public function runStep($step)
	{
		// If replace is null, there is invalid settings JSON in the database. Guard against it so we don't
		// inadvertently nuke textual content in the database.
		if ($this->replace !== null)
		{
			if (isset($this->_textColumns[$step]))
			{
				Craft::$app->getDb()->createCommand()->replace($this->_table, $this->_textColumns[$step], $this->find, $this->replace)->execute();
				return true;
			}
			else
			{
				$step -= count($this->_textColumns);

				if (isset($this->_matrixFieldIds[$step]))
				{
					$field = Craft::$app->getFields()->getFieldById($this->_matrixFieldIds[$step]);

					if ($field)
					{
						return $this->runSubTask([
							'type'          => FindAndReplace::className(),
							'description'   => Craft::t('app', 'Working in Matrix field “{field}”', ['field' => $field->name]),
							'find'          => $this->find,
							'replace'       => $this->replace,
							'matrixFieldId' => $field->id
						]);
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
			Craft::error('Invalid "replace" in the Find and Replace task probably caused by invalid JSON in the database.', __METHOD__);
			return false;
		}
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	protected function getDefaultDescription()
	{
		return Craft::t('app', 'Replacing “{find}” with “{replace}”', [
			'find'    => $this->find,
			'replace' => $this->replace
		]);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Checks whether the given field is saving data into a textual column, and saves it accordingly.
	 *
	 * @param FieldInterface|Field $field
	 * @param string               $fieldColumnPrefix
	 *
	 * @return bool
	 */
	private function _checkField(FieldInterface $field, $fieldColumnPrefix)
	{
		if ($field instanceof Matrix)
		{
			$this->_matrixFieldIds[] = $field->id;
		}
		else if ($field::hasContentColumn())
		{
			$columnType = $field->getContentColumnType();

			if (preg_match('/^\w+/', $columnType, $matches))
			{
				$columnType = strtolower($matches[0]);

				if (in_array($columnType, ['tinytext', 'mediumtext', 'longtext', 'text', 'varchar', 'string', 'char']))
				{
					$this->_textColumns[] = $fieldColumnPrefix.$field->handle;
				}
			}
		}
	}
}
