<?php
namespace Craft;

/**
 * Section model class
 *
 * Used for transporting section data throughout the system.
 */
class SectionModel extends BaseModel
{
	private $_locales;
	private $_fieldLayout;

	/**
	 * Use the translated section name as the string representation.
	 *
	 * @return string
	 */
	function __toString()
	{
		return Craft::t($this->name);
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'id'            => AttributeType::Number,
			'name'          => AttributeType::String,
			'handle'        => AttributeType::String,
			'titleLabel'    => array(AttributeType::String, 'default' => Craft::t('Title')),
			'hasUrls'       => AttributeType::Bool,
			'template'      => AttributeType::String,
			'fieldLayoutId' => AttributeType::Number,
		);
	}

	/**
	 * @return array
	 */
	public function behaviors()
	{
		return array(
			'fieldLayout' => new FieldLayoutBehavior(),
		);
	}

	/**
	 * Returns the section's locale models
	 *
	 * @return array
	 */
	public function getLocales()
	{
		if (!isset($this->_locales))
		{
			if ($this->id)
			{
				$this->_locales = craft()->sections->getSectionLocales($this->id, 'locale');
			}
			else
			{
				$this->_locales = array();
			}
		}

		return $this->_locales;
	}

	/**
	 * Sets the section's locale models.
	 *
	 * @param array $locales
	 */
	public function setLocales($locales)
	{
		$this->_locales = $locales;
	}

	/**
	 * Adds locale-specific errors to the model.
	 *
	 * @param array $errors
	 * @param string $localeId
	 */
	public function addLocaleErrors($errors, $localeId)
	{
		foreach ($errors as $attribute => $localeErrors)
		{
			$key = $attribute.'-'.$localeId;
			foreach ($localeErrors as $error)
			{
				$this->addError($key, $error);
			}
		}
	}
}
