<?php
namespace Craft;

/**
 *
 */
class TableFieldType extends BaseFieldType
{
	/**
	 * Returns the type of field this is.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Table');
	}

	/**
	 * Returns the content attribute config.
	 *
	 * @return mixed
	 */
	public function defineContentAttribute()
	{
		return AttributeType::Mixed;
	}

	/**
	 * Defines the settings.
	 *
	 * @access protected
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'columns' => AttributeType::Mixed,
			'defaults' => AttributeType::Mixed,
		);
	}

	/**
	 * Returns the field's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		$columns = $this->getSettings()->columns;
		$defaults = $this->getSettings()->defaults;

		if (!$columns)
		{
			$columns = array('col1' => array('heading' => '', 'handle' => '', 'type' => 'singleline'));

			// Update the actual settings model for getInputHtml()
			$this->getSettings()->columns = $columns;
		}

		if (!$defaults)
		{
			$defaults = array('row1' => array());
		}

		$columnSettings = array(
			'heading' => array(
				'heading' => Craft::t('Column Heading'),
				'type' => 'singleline',
				'autopopulate' => 'handle'
			),
			'handle' => array(
				'heading' => Craft::t('Handle'),
				'class' => 'code',
				'type' => 'singleline'
			),
			'width' => array(
				'heading' => Craft::t('Width'),
				'class' => 'code',
				'type' => 'singleline',
				'width' => 50
			),
			'type' => array(
				'heading' => Craft::t('Type'),
				'class' => 'thin',
				'type' => 'select',
				'options' => array(
					'singleline' => Craft::t('Single-line Text'),
					'multiline' => Craft::t('Multi-line text'),
					'number' => Craft::t('Number'),
					'checkbox' => Craft::t('Checkbox'),
				)
			),
		);

		craft()->templates->includeJsResource('js/TableFieldSettings.js');
		craft()->templates->includeJs('new Craft.TableFieldSettings(' .
			'"'.craft()->templates->namespaceInputName('columns').'", ' .
			'"'.craft()->templates->namespaceInputName('defaults').'", ' .
			JsonHelper::encode($columns).', ' .
			JsonHelper::encode($defaults).', ' .
			JsonHelper::encode($columnSettings) .
		');');

		$columnsField = craft()->templates->renderMacro('_includes/forms', 'editableTableField', array(
			array(
				'label'        => Craft::t('Table Columns'),
				'instructions' => Craft::t('Define the columns your table should have.'),
				'id'           => 'columns',
				'name'         => 'columns',
				'cols'         => $columnSettings,
				'rows'         => $columns,
				'addRowLabel'  => Craft::t('Add a column'),
				'initJs'       => false
			)
		));

		$defaultsField = craft()->templates->renderMacro('_includes/forms', 'editableTableField', array(
			array(
				'label'        => Craft::t('Default Values'),
				'instructions' => Craft::t('Define the default values for the field.'),
				'id'           => 'defaults',
				'name'         => 'defaults',
				'cols'         => $columns,
				'rows'         => $defaults,
				'initJs'       => false
			)
		));

		return $columnsField . $defaultsField;
	}

	/**
	 * Returns the field's input HTML.
	 *
	 * @param string $name
	 * @param mixed  $value
	 * @return string
	 */
	public function getInputHtml($name, $value)
	{
		$input = '<input type="hidden" name="'.$name.'" value="">';

		$columns = $this->getSettings()->columns;

		if ($columns)
		{
			if ($this->isFresh())
			{
				$defaults = $this->getSettings()->defaults;

				if (is_array($defaults))
				{
					$value = array_values($defaults);
				}
			}

			$id = rtrim(preg_replace('/[\[\]]+/', '-', $name), '-');

			$input .= craft()->templates->render('_includes/forms/editableTable', array(
				'id'   => $id,
				'name' => $name,
				'cols' => $columns,
				'rows' => $value
			));
		}

		return $input;
	}

	/**
	 * Preps the post data before it's saved to the database.
	 *
	 * @access protected
	 * @param mixed $value
	 * @return mixed
	 */
	protected function prepPostData($value)
	{
		if (is_array($value))
		{
			// Drop the string row keys
			return array_values($value);
		}
	}

	/**
	 * Preps the field value for use.
	 *
	 * @param mixed $value
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
}
