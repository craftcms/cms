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
			JsonHelper::encode($columns).', ' .
			JsonHelper::encode($defaults).', ' .
			JsonHelper::encode($columnSettings) .
		');');

		return craft()->templates->render('_components/fieldtypes/Table/settings', array(
			'columns'        => $columns,
			'defaults'       => $defaults,
			'columnSettings' => $columnSettings
		));
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
		$columns = $this->getSettings()->columns;

		if ($columns)
		{
			// If this is a new entry, use the default values
			if (!isset($this->element))
			{
				$defaults = $this->getSettings()->defaults;

				if (is_array($defaults))
				{
					$value = array_values($defaults);
				}
			}

			$id = preg_replace('/[\[\]]+/', '-', $name);

			return craft()->templates->render('_components/fieldtypes/Table/input', array(
				'id'   => $id,
				'name' => $name,
				'cols' => $columns,
				'rows' => $value
			));
		}
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
