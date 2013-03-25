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

		if (!$columns)
		{
			$columns = array('col1' => array('heading' => '', 'handle' => '', 'type' => 'singleline'));
		}

		return craft()->templates->render('_components/fieldtypes/Table/settings', array(
			'columns' => $columns,
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
						$row[$col['handle']] = $row[$colId];
					}
				}
			}

			return $value;
		}
	}
}
