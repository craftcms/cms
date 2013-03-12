<?php
namespace Craft;

/**
 * Links fieldtype class
 */
class LinksFieldType extends BaseFieldType
{
	private $_criteria;

	/**
	 * Returns the type of field this is.
	 *
	 * @return string
	 */
	public function getName()
	{
		$name = Craft::t('Links');

		$elementType = $this->_getRightElementType(false);

		if ($elementType)
		{
			$name .= ' ('.$elementType->getName().')';
		}

		return $name;
	}

	/**
	 * Returns the content attribute config.
	 *
	 * @return mixed
	 */
	public function defineContentAttribute()
	{
		return false;
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
			'criteriaId'    => AttributeType::Number,
			'addLabel'      => array(AttributeType::String, 'required' => true, 'default' => 'Add Links'),
			'removeLabel'   => array(AttributeType::String, 'required' => true, 'default' => 'Remove Links'),
			'limit'         => array(AttributeType::Number, 'min' => 0),
			/* HIDE */
			'reverseHandle' => AttributeType::String,
			/* end HIDE */
		);
	}

	/**
	 * Returns the field's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		$elementType = $this->_getRightElementType();
		$criteria = $this->_getCriteria();

		if ($criteria)
		{
			$elementType->setLinkSettings($criteria->rightSettings);
		}

		return craft()->templates->render('_components/fieldtypes/Links/settings', array(
			'elementType' => new ElementTypeVariable($elementType),
			'settings'    => $this->getSettings()
		));
	}

	/**
	 * Preps the settings before they're saved to the database.
	 *
	 * @param array $settings
	 * @return array
	 */
	public function prepSettings($settings)
	{
		$elementTypeClass = $settings['type'];

		if (isset($settings['types'][$elementTypeClass]))
		{
			$elementTypeSettings = $settings['types'][$elementTypeClass];
		}
		else
		{
			$elementTypeSettings = array();
		}

		unset($settings['types'], $settings['type']);

		// Give the element type a chance to pre-process any of its settings
		$elementType = craft()->links->getLinkableElementType($elementTypeClass);

		if ($elementType)
		{
			$elementTypeSettings = $elementType->prepLinkSettings($elementTypeSettings);
		}

		if (isset($settings['criteriaId']))
		{
			$criteria = craft()->links->getCriteriaById($settings['criteriaId']);

			// Has the element type changed?
			if ($criteria && $criteria->rightElementType != $elementTypeClass)
			{
				// Delete the previous links
				craft()->db->createCommand()->delete('links', array('criteriaId' => $criteria->id));
			}
		}

		if (empty($criteria))
		{
			$criteria = new LinkCriteriaModel();
		}

		$criteria->ltrHandle        = $this->model->handle;
		/* HIDE */
		$criteria->rtlHandle        = ($settings['reverseHandle'] ? $settings['reverseHandle'] : null);
		/* end HIDE */
		$criteria->leftElementType  = $this->model->getClassHandle();
		$criteria->rightElementType = $elementTypeClass;
		$criteria->rightSettings    = $elementTypeSettings;

		if (!craft()->links->saveCriteria($criteria))
		{
			throw new Exception(Craft::t('Could not save the link criteria'));
		}

		$settings['criteriaId'] = $criteria->id;

		return $settings;
	}

	/**
	 * Preps the field value for use.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	public function prepValue($value)
	{
		$criteria = $this->_getCriteria();

		if ($criteria)
		{
			// $value will be an array of element IDs if there was a validation error
			// or we're loading a draft/version.
			if (is_array($value))
			{
				return craft()->links->getElementsById($criteria->rightElementType, array_filter($value));
			}
			else if ($this->element && $this->element->id)
			{
				return craft()->links->getLinkedElements($criteria, $this->element->id);
			}
		}

		return array();
	}

	/**
	 * Returns the field's input HTML.
	 *
	 * @param string $name
	 * @param mixed  $elements
	 * @return string
	 */
	public function getInputHtml($name, $elements)
	{
		if (!$elements)
		{
			$elements = array();
		}

		$criteria = $this->_getCriteria();

		if (!$criteria)
		{
			$criteria = new LinkCriteriaModel();
		}

		$elementType = $this->_getRightElementType();

		$settings = array_merge($this->getSettings()->getAttributes(), array(
			'type'                => $elementType->getClassHandle(),
			'elementTypeSettings' => $criteria->rightSettings,
		));

		$elementIds = array();

		foreach ($elements as $element)
		{
			$elementIds[] = (int) $element->id;
		}

		$id = 'links-'.$this->model->id;

		craft()->templates->includeJs('new Craft.LinksField("'.$id.'", "'.$name.'", '.JsonHelper::encode($settings).', '.JsonHelper::encode($elementIds).');');

		return craft()->templates->render('_components/fieldtypes/Links/input', array(
			'id'       => $id,
			'name'     => $name,
			'elements' => $elements,
			'settings' => $settings,
		));
	}

	/**
	 * Performs any additional actions after the element has been saved.
	 */
	public function onAfterElementSave()
	{
		$rawValue = $this->element->getRawContent($this->model->handle);
		$elementIds = is_array($rawValue) ? array_filter($rawValue) : array();
		craft()->links->saveLinks($this->getSettings()->criteriaId, $this->element->id, $elementIds);
	}

	/**
	 * Returns the criteria used by this field.
	 *
	 * @access private
	 * @return LinkCriteriaModel|null
	 */
	private function _getCriteria()
	{
		if (!isset($this->_criteria))
		{
			$criteriaId = $this->getSettings()->criteriaId;

			if ($criteriaId)
			{
				$this->_criteria = craft()->links->getCriteriaById($criteriaId);
			}
			else
			{
				$this->_criteria = null;
			}
		}

		return $this->_criteria;
	}

	/**
	 * Returns the right element type.
	 *
	 * @access private
	 * @return BaseElementType|null
	 */
	private function _getRightElementType($defaultToEntries = true)
	{
		$criteria = $this->_getCriteria();

		if ($criteria)
		{
			$elementType = craft()->elements->getElementType($criteria->rightElementType);
		}

		if (empty($elementType))
		{
			if ($defaultToEntries)
			{
				$elementType = craft()->links->getLinkableElementType(ElementType::Entry);
			}
			else
			{
				$elementType = null;
			}
		}

		return $elementType;
	}
}
