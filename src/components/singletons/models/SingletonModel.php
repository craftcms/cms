<?php
namespace Blocks;

/**
 * Singleton model class
 *
 * Used for transporting page data throughout the system.
 */
class SingletonModel extends EntryModel
{
	protected $entryType = 'Singleton';

	private $_locales;
	private $_fieldLayout;

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array_merge(parent::defineAttributes(), array(
			'name'          => AttributeType::Name,
			'template'      => AttributeType::String,
			'fieldLayoutId' => AttributeType::Mixed,
		));
	}

	/**
	 * Returns the singleton's locale models
	 *
	 * @return array
	 */
	public function getLocales()
	{
		if (!isset($this->_locales))
		{
			if ($this->id)
			{
				$this->_locales = blx()->singletons->getSingletonLocales($this->id, 'locale');
			}
			else
			{
				$this->_locales = array();
			}
		}

		return $this->_locales;
	}

	/**
	 * Sets the singleton's locale models.
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
	 * Returns the singleton's field layout.
	 *
	 * @return FieldLayoutModel
	 */
	public function getFieldLayout()
	{
		if (!isset($this->_fieldLayout))
		{
			if ($this->fieldLayoutId)
			{
				$this->_fieldLayout = blx()->fields->getLayoutById($this->fieldLayoutId);
			}

			if (empty($this->_fieldLayout))
			{
				$this->_fieldLayout = new FieldLayoutModel();
			}
		}

		return $this->_fieldLayout;
	}

	/**
	 * Sets the singleton's field layout.
	 *
	 * @param FieldLayoutModel $fieldLayout
	 */
	public function setFieldLayout(FieldLayoutModel $fieldLayout)
	{
		$this->_fieldLayout = $fieldLayout;
	}
}
