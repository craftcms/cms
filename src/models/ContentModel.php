<?php
namespace Craft;

/**
 * Entry content model class
 */
class ContentModel extends BaseModel
{
	private $_requiredFields;
	private $_attributeConfigs;

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		$requiredTitle = (isset($this->_requiredFields) && in_array('title', $this->_requiredFields));

		$attributes = array(
			'id'        => AttributeType::Number,
			'elementId' => AttributeType::Number,
			'locale'    => AttributeType::Locale,
			'title'     => array(AttributeType::String, 'required' => $requiredTitle),
		);

		if (Craft::isInstalled() && !craft()->isConsole())
		{
			$allFields = craft()->fields->getAllFields();

			foreach ($allFields as $field)
			{
				$fieldType = craft()->fields->populateFieldType($field);

				if ($fieldType)
				{
					$attributeConfig = $fieldType->defineContentAttribute();
				}

				// Default to Mixed
				if (!$fieldType || !$attributeConfig)
				{
					$attributeConfig = AttributeType::Mixed;
				}

				$attributeConfig = ModelHelper::normalizeAttributeConfig($attributeConfig);
				$attributeConfig['label'] = $field->name;

				if (isset($this->_requiredFields) && in_array($field->id, $this->_requiredFields))
				{
					$attributeConfig['required'] = true;
				}

				$attributes[$field->handle] = $attributeConfig;
			}
		}

		return $attributes;
	}

	/**
	 * Returns this model's normalized attribute configs.
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
	 * Sets the required fields.
	 *
	 * @param array $requiredFields
	 */
	public function setRequiredFields($requiredFields)
	{
		$this->_requiredFields = $requiredFields;

		// Have the attributes already been defined?
		if (isset($this->_attributeConfigs))
		{
			foreach (craft()->fields->getAllFields() as $field)
			{
				if (in_array($field->id, $this->_requiredFields) && isset($this->_attributeConfigs[$field->handle]))
				{
					$this->_attributeConfigs[$field->handle]['required'] = true;
				}
			}

			if (in_array('title', $this->_requiredFields))
			{
				$this->_attributeConfigs['title']['required'] = true;
			}
		}
	}

	/**
	 * Sets content values indexed by the field ID.
	 *
	 * @param array $values
	 */
	public function setValuesByFieldId($values)
	{
		foreach ($values as $fieldId => $value)
		{
			$field = craft()->fields->getFieldById($fieldId);

			if ($field)
			{
				$fieldHandle = $field->handle;
				$this->$fieldHandle = $value;
			}
		}
	}
}
