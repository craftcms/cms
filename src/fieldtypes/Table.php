<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\fieldtypes;

use Craft;
use craft\app\enums\AttributeType;
use craft\app\helpers\JsonHelper;
use craft\app\helpers\StringHelper;

/**
 * Table fieldtype
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Table extends BaseFieldType
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc ComponentTypeInterface::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('app', 'Table');
	}

	/**
	 * @inheritDoc FieldTypeInterface::defineContentAttribute()
	 *
	 * @return mixed
	 */
	public function defineContentAttribute()
	{
		return AttributeType::Mixed;
	}

	/**
	 * @inheritDoc SavableComponentTypeInterface::getSettingsHtml()
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		$columns = $this->getSettings()->columns;
		$defaults = $this->getSettings()->defaults;

		if (!$columns)
		{
			$columns = ['col1' => ['heading' => '', 'handle' => '', 'type' => 'singleline']];

			// Update the actual settings model for getInputHtml()
			$this->getSettings()->columns = $columns;
		}

		if ($defaults === null)
		{
			$defaults = ['row1' => []];
		}

		$columnSettings = [
			'heading' => [
				'heading' => Craft::t('app', 'Column Heading'),
				'type' => 'singleline',
				'autopopulate' => 'handle'
			],
			'handle' => [
				'heading' => Craft::t('app', 'Handle'),
				'class' => 'code',
				'type' => 'singleline'
			],
			'width' => [
				'heading' => Craft::t('app', 'Width'),
				'class' => 'code',
				'type' => 'singleline',
				'width' => 50
			],
			'type' => [
				'heading' => Craft::t('app', 'Type'),
				'class' => 'thin',
				'type' => 'select',
				'options' => [
					'singleline' => Craft::t('app', 'Single-line Text'),
					'multiline' => Craft::t('app', 'Multi-line text'),
					'number' => Craft::t('app', 'Number'),
					'checkbox' => Craft::t('app', 'Checkbox'),
				]
			],
		];

		Craft::$app->templates->includeJsResource('js/TableFieldSettings.js');
		Craft::$app->templates->includeJs('new Craft.TableFieldSettings(' .
			'"'.Craft::$app->templates->namespaceInputName('columns').'", ' .
			'"'.Craft::$app->templates->namespaceInputName('defaults').'", ' .
			JsonHelper::encode($columns).', ' .
			JsonHelper::encode($defaults).', ' .
			JsonHelper::encode($columnSettings) .
		');');

		$columnsField = Craft::$app->templates->renderMacro('_includes/forms', 'editableTableField', [
			[
				'label'        => Craft::t('app', 'Table Columns'),
				'instructions' => Craft::t('app', 'Define the columns your table should have.'),
				'id'           => 'columns',
				'name'         => 'columns',
				'cols'         => $columnSettings,
				'rows'         => $columns,
				'addRowLabel'  => Craft::t('app', 'Add a column'),
				'initJs'       => false
			]
		]);

		$defaultsField = Craft::$app->templates->renderMacro('_includes/forms', 'editableTableField', [
			[
				'label'        => Craft::t('app', 'Default Values'),
				'instructions' => Craft::t('app', 'Define the default values for the field.'),
				'id'           => 'defaults',
				'name'         => 'defaults',
				'cols'         => $columns,
				'rows'         => $defaults,
				'initJs'       => false
			]
		]);

		return $columnsField.$defaultsField;
	}

	/**
	 * @inheritDoc FieldTypeInterface::getInputHtml()
	 *
	 * @param string $name
	 * @param mixed  $value
	 *
	 * @return string
	 */
	public function getInputHtml($name, $value)
	{
		$input = '<input type="hidden" name="'.$name.'" value="">';

		$tableHtml = $this->_getInputHtml($name, $value, false);

		if ($tableHtml)
		{
			$input .= $tableHtml;
		}

		return $input;
	}

	/**
	 * @inheritDoc FieldTypeInterface::prepValueFromPost()
	 *
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	public function prepValueFromPost($value)
	{
		if (is_array($value))
		{
			// Drop the string row keys
			return array_values($value);
		}
	}

	/**
	 * @inheritDoc FieldTypeInterface::prepValue()
	 *
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	public function prepValue($value)
	{
		if (is_array($value) && ($columns = $this->getSettings()->columns))
		{
			// Make the values accessible from both the col IDs and the handles
			foreach ($value as &$row)
			{
				foreach ($columns as $colId => $col)
				{
					if ($col['handle'])
					{
						$row[$col['handle']] = (isset($row[$colId]) ? $row[$colId] : null);
					}
				}
			}

			return $value;
		}
	}

	/**
	 * @inheritDoc BaseFieldType::getStaticHtml()
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	public function getStaticHtml($value)
	{
		return $this->_getInputHtml(StringHelper::randomString(), $value, true);
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
			'columns' => AttributeType::Mixed,
			'defaults' => AttributeType::Mixed,
		];
	}

	/**
	 * @inheritDoc ISavableComponentType::prepSettings()
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public function prepSettings($settings)
	{
		if (!isset($settings['defaults']))
		{
			$settings['defaults'] = array();
		}

		return $settings;
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns the field's input HTML.
	 *
	 * @param string $name
	 * @param mixed  $value
	 * @param bool  $static
	 *
	 * @return string
	 */
	private function _getInputHtml($name, $value, $static)
	{
		$columns = $this->getSettings()->columns;

		if ($columns)
		{
			// Translate the column headings
			foreach ($columns as &$column)
			{
				if (!empty($column['heading']))
				{
					$column['heading'] = Craft::t('app', $column['heading']);
				}
			}

			if ($this->isFresh())
			{
				$defaults = $this->getSettings()->defaults;

				if (is_array($defaults))
				{
					$value = array_values($defaults);
				}
			}

			$id = Craft::$app->templates->formatInputId($name);

			return Craft::$app->templates->render('_includes/forms/editableTable', [
				'id'     => $id,
				'name'   => $name,
				'cols'   => $columns,
				'rows'   => $value,
				'static' => $static
			]);
		}
	}
}
