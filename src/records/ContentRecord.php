<?php
namespace Blocks;

/**
 * Entry content record class
 */
class ContentRecord extends BaseRecord
{
	private $_requiredFields;
	private $_attributeConfigs;

	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'content';
	}

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		$attributes['locale'] = array(AttributeType::Locale, 'required' => true);

		if (blx()->isInstalled() && !blx()->isConsole())
		{
			foreach (blx()->fields->getAllFields() as $field)
			{
				$fieldType = blx()->fields->populateFieldType($field);

				if (!empty($fieldType))
				{
					$attribute = $fieldType->defineContentAttribute();

					if ($attribute)
					{
						$attribute = ModelHelper::normalizeAttributeConfig($attribute);
						$attribute['label'] = $field->name;

						if (isset($this->_requiredFields) && in_array($field->id, $this->_requiredFields))
						{
							$attribute['required'] = true;
						}

						$attributes[$field->handle] = $attribute;
					}
				}
			}
		}

		return $attributes;
	}

	/**
	 * Returns this record's normalized attribute configs.
	 *
	 * @return array
	 */
	public function getAttributeConfigs()
	{
		if (!isset($this->_attributeConfigs))
		{
			$this->_attributeConfigs = parent::getAttributeConfigs();
		}

		return $this->_attributeConfigs;
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'element' => array(static::BELONGS_TO, 'ElementRecord', 'required' => true, 'onDelete' => static::CASCADE),
			'locale'  => array(static::BELONGS_TO, 'LocaleRecord', 'locale', 'required' => true, 'onDelete' => static::CASCADE, 'onUpdate' => static::CASCADE),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => 'elementId,locale', 'unique' => true),
		);
	}

	/**
	 * Sets the required fields.
	 *
	 * @param array $requiredFields
	 */
	public function setRequiredFields($requiredFields)
	{
		$this->_requiredFields = $requiredFields;

		if (isset($this->_attributeConfigs))
		{
			foreach (blx()->fields->getAllFields() as $field)
			{
				if (in_array($field->id, $this->_requiredFields) && isset($this->_attributeConfigs[$field->handle]))
				{
					$this->_attributeConfigs[$field->handle]['required'] = true;
				}
			}
		}
	}
}
