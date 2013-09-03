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
	private $_entryTypes;

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
			'id'       => AttributeType::Number,
			'name'     => AttributeType::String,
			'handle'   => AttributeType::String,
			'type'     => array(AttributeType::Enum, 'values' => array(SectionType::Single, SectionType::Channel, SectionType::Structure)),
			'hasUrls'  => array(AttributeType::Bool, 'default' => true),
			'template' => AttributeType::String,
			'maxDepth' => AttributeType::Number,
		);
	}

	/**
	 * Returns whether this is the homepage section.
	 *
	 * @return bool
	 */
	public function isHomepage()
	{
		return ($this->type == SectionType::Single && $this->urlFormat == '__home__');
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

	/**
	 * Returns the section's entry types.
	 *
	 * @return array
	 */
	public function getEntryTypes()
	{
		if (!isset($this->_entryTypes))
		{
			if ($this->id)
			{
				$this->_entryTypes = craft()->sections->getEntryTypesBySectionId($this->id, 'id');
			}
			else
			{
				$this->_entryTypes = array();
			}
		}

		return $this->_entryTypes;
	}

	/**
	 * Returns the section's URL format (or URL) for the current locale.
	 *
	 * @return string|null
	 */
	public function getUrlFormat()
	{
		$locales = $this->getLocales();

		if ($locales)
		{
			$localeIds = array_keys($locales);

			// Does this section target the current locale?
			if (in_array(craft()->language, $localeIds))
			{
				$localeId = craft()->language;
			}
			else
			{
				$localeId = array_unshift($localeIds);
			}

			return $locales[$localeId]->urlFormat;
		}
	}
}
