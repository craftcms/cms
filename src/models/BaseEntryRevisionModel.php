<?php
namespace Craft;

craft()->requireEdition(Craft::Client);

/**
 *
 */
class BaseEntryRevisionModel extends EntryModel
{
	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array_merge(parent::defineAttributes(), array(
			'creatorId'   => AttributeType::Number,
			'dateUpdated' => AttributeType::DateTime,
			'dateCreated' => AttributeType::DateTime,
		));
	}

	/**
	 * Sets the revision content.
	 *
	 * @param array $content
	 */
	public function setContentFromRevision($content)
	{
		// Swap the field IDs with handles
		$contentByFieldHandles = array();

		foreach ($content as $fieldId => $value)
		{
			$field = craft()->fields->getFieldById($fieldId);

			if ($field)
			{
				$contentByFieldHandles[$field->handle] = $value;
			}
		}

		// Set the values and prep them
		$this->setContentFromPost($contentByFieldHandles);
	}

	/**
	 * Returns the draft's creator.
	 *
	 * @return UserModel|null
	 */
	public function getCreator()
	{
		return craft()->users->getUserById($this->creatorId);
	}
}
