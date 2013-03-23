<?php
namespace Craft;

/**
 * Base element model class
 */
abstract class BaseElementModel extends BaseModel
{
	protected $elementType;

	private $_content;
	private $_preppedContent;
	private $_tags;

	const ENABLED  = 'enabled';
	const DISABLED = 'disabled';
	const ARCHIVED = 'archived';

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'id'          => AttributeType::Number,
			'type'        => array(AttributeType::String, 'default' => $this->elementType),
			'enabled'     => array(AttributeType::Bool, 'default' => true),
			'archived'    => array(AttributeType::Bool, 'default' => false),
			'locale'      => AttributeType::Locale,
			'uri'         => AttributeType::String,
			'dateCreated' => AttributeType::DateTime,
			'dateUpdated' => AttributeType::DateTime,
		);
	}

	/**
	 * Returns the element's full URL.
	 *
	 * @return string
	 */
	public function getUrl()
	{
		if ($this->uri !== null)
		{
			return UrlHelper::getSiteUrl($this->uri);
		}
	}

	/**
	 * Returns the element's CP edit URL.
	 *
	 * @return string|null
	 */
	public function getCpEditUrl()
	{
		if ($this->id)
		{
			return craft()->elements->getCpEditUrlForElement($this);
		}
	}

	/**
	 * Returns the element's status.
	 *
	 * @return string
	 */
	public function getStatus()
	{
		if ($this->archived)
		{
			return static::ARCHIVED;
		}
		else if (!$this->enabled)
		{
			return static::DISABLED;
		}
		else
		{
			return static::ENABLED;
		}
	}

	/**
	 * Is set?
	 *
	 * @param $name
	 * @return bool
	 */
	function __isset($name)
	{
		if (parent::__isset($name))
		{
			return true;
		}

		// Is $name a field handle?
		$field = craft()->fields->getFieldByHandle($name);
		if ($field)
		{
			return true;
		}

		// Is $name a RTL link handle?
		$linkCriteria = craft()->links->getCriteriaByTypeAndHandle($this->getAttribute('type'), $name, 'rtl');
		if ($linkCriteria)
		{
			return true;
		}

		return false;
	}

	/**
	 * Getter
	 *
	 * @param string $name
	 * @throws \Exception
	 * @return mixed
	 */
	function __get($name)
	{
		// Run through the BaseModel/CModel stuff first
		try
		{
			return parent::__get($name);
		}
		catch (\Exception $e)
		{
			// Is $name a field handle?
			$field = craft()->fields->getFieldByHandle($name);
			if ($field)
			{
				return $this->_getPreppedContentForField($field);
			}
			else if ($this->getAttribute('id'))
			{
				// Is $name a RTL link handle?
				$linkCriteria = craft()->links->getCriteriaByTypeAndHandle($this->getAttribute('type'), $name, 'rtl');

				if ($linkCriteria)
				{
					return craft()->links->getLinkedElements($linkCriteria, $this->getAttribute('id'), 'rtl');
				}
			}

			// Fine, throw the exception
			throw $e;
		}
	}

	/**
	 * Returns the raw content saved on this entity.
	 *
	 * @param string|null $fieldHandle
	 * @return mixed
	 */
	public function getRawContent($fieldHandle = null)
	{
		$content = $this->_getContent();

		if ($fieldHandle)
		{
			if (isset($content->$fieldHandle))
			{
				return $content->$fieldHandle;
			}
			else
			{
				return null;
			}
		}
		else
		{
			return $content;
		}
	}

	/**
	 * Sets content that's indexed by the field ID.
	 *
	 * @param array $content
	 */
	public function setContentIndexedByFieldId($content)
	{
		$this->_content = new ContentModel();

		foreach ($content as $fieldId => $value)
		{
			$field = craft()->fields->getFieldById($fieldId);
			if ($field)
			{
				$fieldHandle = $field->handle;
				$this->_content->$fieldHandle = $value;
			}
		}
	}

	/**
	 * Sets the content.
	 *
	 * @param array $content
	 */
	public function setContent($content)
	{
		$this->_content = new ContentModel($content);
	}

	/**
	 * Populates a new model instance with a given set of attributes.
	 *
	 * @static
	 * @param mixed $values
	 * @return BaseModel
	 */
	public static function populateModel($values)
	{
		// Strip out the element record attributes if this is getting called from a child class
		// based on an Active Record result eager-loaded with the ElementRecord
		if (isset($values['element']))
		{
			$elementAttributes = $values['element'];
			unset($values['element']);
		}

		$model = parent::populateModel($values);

		// Now set those ElementRecord attributes
		if (isset($elementAttributes))
		{
			if (isset($elementAttributes['i18n']))
			{
				$model->setAttributes($elementAttributes['i18n']);
				unset($elementAttributes['i18n']);
			}

			$model->setAttributes($elementAttributes);
		}

		return $model;
	}

	/**
	 * Returns the content for the element.
	 *
	 * @access private
	 * @return array
	 */
	private function _getContent()
	{
		if (!isset($this->_content))
		{
			if ($this->id)
			{
				$this->_content = craft()->content->getContent($this->id, $this->locale);
			}

			if (empty($this->_content))
			{
				$this->_content = new ContentModel();
			}
		}

		return $this->_content;
	}

	/**
	 * Returns the prepped content for a given field.
	 *
	 * @param FieldModel $field
	 * @return mixed
	 */
	private function _getPreppedContentForField(FieldModel $field)
	{
		if (!isset($this->_preppedContent) || !array_key_exists($field->handle, $this->_preppedContent))
		{
			$content = $this->_getContent();
			$fieldHandle = $field->handle;

			if (isset($content->$fieldHandle))
			{
				$value = $content->$fieldHandle;
			}
			else
			{
				$value = null;
			}

			$fieldType = craft()->fields->populateFieldType($field, $this);

			if ($fieldType)
			{
				$value = $fieldType->prepValue($value);
			}

			$this->_preppedContent[$field->handle] = $value;
		}

		return $this->_preppedContent[$field->handle];
	}
}
