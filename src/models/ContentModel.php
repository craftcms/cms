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
		$attributes = array(
			'id'        => AttributeType::Number,
			'elementId' => array(AttributeType::Number, 'required' => true),
			'locale'    => array(AttributeType::Locale, 'required' => true)
		);

		if (Craft::isInstalled() && !craft()->isConsole())
		{
			$allFields = craft()->fields->getAllFields();
			foreach ($allFields as $field)
			{
				$fieldType = craft()->fields->populateFieldType($field);

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

		if (isset($this->_attributeConfigs))
		{
			foreach (craft()->fields->getAllFields() as $field)
			{
				if (in_array($field->id, $this->_requiredFields) && isset($this->_attributeConfigs[$field->handle]))
				{
					$this->_attributeConfigs[$field->handle]['required'] = true;
				}
			}
		}
	}
}
