<?php
namespace Blocks;

/**
 * Links block type class
 */
class LinksBlockType extends BaseBlockType
{
	/**
	 * Returns the type of block this is.
	 *
	 * @return string
	 */
	public function getName()
	{
		$name = Blocks::t('Links');
		if ($this->getSettings()->criteriaId)
		{
			$criteria = blx()->links->getCriteriaRecordById($this->getSettings()->criteriaId);
			$linkType = blx()->links->getLinkType($criteria->rightEntityType);
			if ($linkType)
			{
				$name .= ' ('.$linkType->getName().')';
			}
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
			'criteriaId'  => AttributeType::Number,
			'addLabel'    => array(AttributeType::String, 'required' => true, 'default' => 'Add Links'),
			'removeLabel' => array(AttributeType::String, 'required' => true, 'default' => 'Remove Links'),
			'limit'       => array(AttributeType::Number, 'min' => 0),
		);
	}

	/**
	 * Returns the block's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		if ($this->getSettings()->criteriaId)
		{
			$criteria = blx()->links->getCriteriaRecordById($this->getSettings()->criteriaId);
			$linkType = blx()->links->getLinkType($criteria->rightEntityType);
		}

		if (empty($linkType))
		{
			$linkType = blx()->links->getLinkType('Entry');
		}

		return blx()->templates->render('_components/blocktypes/Links/settings', array(
			'linkType' => new LinkTypeVariable($linkType),
			'settings' => $this->getSettings()
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
		if (isset($settings['types'][$settings['type']]))
		{
			$linkTypeSettings = $settings['types'][$settings['type']];
		}
		else
		{
			$linkTypeSettings = array();
		}

		$type = $settings['type'];

		unset($settings['types'], $settings['type']);

		// Give the link type a chance to pre-process any of its settings
		$linkType = blx()->links->getLinkType($type);
		$linkTypeSettings = $linkType->prepSettings($linkTypeSettings);

		if (isset($settings['criteriaId']))
		{
			$criteria = LinkCriteriaRecord::model()->findById($settings['criteriaId']);

			// Has the entity type changed?
			if ($criteria && $criteria->rightEntityType != $type)
			{
				// Delete the previous links
				blx()->db->createCommand()->delete('links', array('criteriaId' => $criteria->id));
			}
		}

		if (empty($criteria))
		{
			$criteria = new LinkCriteriaRecord();
		}

		$criteria->ltrHandle = $this->model->handle;
		$criteria->leftEntityType = $this->model->getClassHandle();
		$criteria->rightEntityType = $type;
		$criteria->rightSettings = $linkTypeSettings;

		$criteria->save();

		$settings['criteriaId'] = $criteria->id;

		return $settings;
	}

	/**
	 * Preps the block value for use.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	public function prepValue($value)
	{
		$criteriaId = $this->getSettings()->criteriaId;

		// $value will be an array of entity IDs if there was a validation error
		// or we're loading a draft/version.
		if (is_array($value))
		{
			$criteria = blx()->links->getCriteriaRecordById($criteriaId);
			if ($criteria)
			{
				return blx()->links->getEntitiesById($criteria->rightEntityType, array_filter($value));
			}
		}
		else if ($this->entity && $this->entity->id)
		{
			return blx()->links->getLinkedEntities($criteriaId, $this->entity->id);
		}

		return array();
	}

	/**
	 * Returns the block's input HTML.
	 *
	 * @param string $name
	 * @param mixed  $entities
	 * @return string
	 */
	public function getInputHtml($name, $entities)
	{
		if (!$entities)
		{
			$entities = array();
		}

		$criteria = blx()->links->getCriteriaRecordById($this->getSettings()->criteriaId);
		$linkType = blx()->links->getLinkType($criteria->rightEntityType);

		$settings = $this->getSettings()->getAttributes();
		$settings['type'] = $criteria->rightEntityType;
		$settings['linkTypeSettings'] = $criteria->rightSettings;
		$settings['addLabel'] = Blocks::t($settings['addLabel']);
		$jsonSettings = JsonHelper::encode($settings);

		$entityIds = JsonHelper::encode(array_keys($entities));

		blx()->templates->includeJs('new Blocks.ui.LinksBlock("'.$name.'", '.$jsonSettings.', '.$entityIds.');');

		return blx()->templates->render('_components/blocktypes/Links/input', array(
			'name'     => $name,
			'linkType' => $linkType,
			'settings' => $this->getSettings(),
			'entities' => $entities,
		));
	}

	/**
	 * Performs any additional actions after the entity has been saved.
	 */
	public function onAfterEntitySave()
	{
		$rawValue = $this->entity->getRawContent($this->model->handle);
		$entityIds = is_array($rawValue) ? array_filter($rawValue) : array();
		blx()->links->setLinks($this->getSettings()->criteriaId, $this->entity->id, $entityIds);
	}
}
