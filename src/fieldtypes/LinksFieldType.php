<?php
namespace Blocks;

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
		$name = Blocks::t('Links');

		$entryType = $this->_getRightEntryType(false);

		if ($entryType)
		{
			$name .= ' ('.$entryType->getName().')';
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
			'reverseHandle' => AttributeType::String,
		);
	}

	/**
	 * Returns the field's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		$entryType = $this->_getRightEntryType();
		$criteria = $this->_getCriteria();

		if ($criteria)
		{
			$entryType->setLinkSettings($criteria->rightSettings);
		}

		return blx()->templates->render('_components/fieldtypes/Links/settings', array(
			'entryType' => new EntryTypeVariable($entryType),
			'settings'  => $this->getSettings()
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
		$entryTypeClass = $settings['type'];

		if (isset($settings['types'][$entryTypeClass]))
		{
			$entryTypeSettings = $settings['types'][$entryTypeClass];
		}
		else
		{
			$entryTypeSettings = array();
		}

		unset($settings['types'], $settings['type']);

		// Give the entry type a chance to pre-process any of its settings
		$entryType = blx()->links->getLinkableEntryType($entryTypeClass);

		if ($entryType)
		{
			$entryTypeSettings = $entryType->prepLinkSettings($entryTypeSettings);
		}

		if (isset($settings['criteriaId']))
		{
			$criteria = blx()->links->getCriteriaById($settings['criteriaId']);

			// Has the entry type changed?
			if ($criteria && $criteria->rightEntryType != $entryTypeClass)
			{
				// Delete the previous links
				blx()->db->createCommand()->delete('links', array('criteriaId' => $criteria->id));
			}
		}

		if (empty($criteria))
		{
			$criteria = new LinkCriteriaModel();
		}

		$criteria->ltrHandle      = $this->model->handle;
		$criteria->rtlHandle      = ($settings['reverseHandle'] ? $settings['reverseHandle'] : null);
		$criteria->leftEntryType  = $this->model->getClassHandle();
		$criteria->rightEntryType = $entryTypeClass;
		$criteria->rightSettings  = $entryTypeSettings;

		if (!blx()->links->saveCriteria($criteria))
		{
			throw new Exception(Blocks::t('Could not save the link criteria'));
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
			// $value will be an array of entry IDs if there was a validation error
			// or we're loading a draft/version.
			if (is_array($value))
			{
				return blx()->links->getEntriesById($criteria->rightEntryType, array_filter($value));
			}
			else if ($this->entry && $this->entry->id)
			{
				return blx()->links->getLinkedEntries($criteria, $this->entry->id);
			}
		}

		return array();
	}

	/**
	 * Returns the field's input HTML.
	 *
	 * @param string $name
	 * @param mixed  $entries
	 * @return string
	 */
	public function getInputHtml($name, $entries)
	{
		if (!$entries)
		{
			$entries = array();
		}

		$criteria = $this->_getCriteria();

		if (!$criteria)
		{
			$criteria = new LinkCriteriaModel();
		}

		$entryType = $this->_getRightEntryType();

		$settings = array_merge($this->getSettings()->getAttributes(), array(
			'type'              => $entryType->getClassHandle(),
			'entryTypeSettings' => $criteria->rightSettings,
		));

		$entryIds = array();

		foreach ($entries as $entry)
		{
			$entryIds[] = (int) $entry->id;
		}

		$id = 'links-'.$this->model->id;

		blx()->templates->includeJs('new Blocks.LinksField("'.$id.'", "'.$name.'", '.JsonHelper::encode($settings).', '.JsonHelper::encode($entryIds).');');

		return blx()->templates->render('_components/fieldtypes/Links/input', array(
			'id'       => $id,
			'name'     => $name,
			'entries'  => $entries,
			'settings' => $settings,
		));
	}

	/**
	 * Performs any additional actions after the entry has been saved.
	 */
	public function onAfterEntrySave()
	{
		$rawValue = $this->entry->getRawContent($this->model->handle);
		$entryIds = is_array($rawValue) ? array_filter($rawValue) : array();
		blx()->links->saveLinks($this->getSettings()->criteriaId, $this->entry->id, $entryIds);
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
				$this->_criteria = blx()->links->getCriteriaById($criteriaId);
			}
			else
			{
				$this->_criteria = null;
			}
		}

		return $this->_criteria;
	}

	/**
	 * Returns the right entry type.
	 *
	 * @access private
	 * @return BaseEntryType|null
	 */
	private function _getRightEntryType($defaultToSectionEntries = true)
	{
		$criteria = $this->_getCriteria();

		if ($criteria)
		{
			$entryType = blx()->entries->getEntryType($criteria->rightEntryType);
		}

		if (empty($entryType))
		{
			if ($defaultToSectionEntries)
			{
				$entryType = blx()->links->getLinkableEntryType('SectionEntry');
			}
			else
			{
				$entryType = null;
			}
		}

		return $entryType;
	}
}
