<?php
namespace Craft;

Craft::requirePackage(CraftPackage::PublishPro);

/**
 *
 */
class EntryDraftModel extends EntryModel
{
	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		$attributes = parent::defineAttributes();

		$attributes['draftId'] = AttributeType::Number;
		$attributes['creatorId'] = AttributeType::Number;
		$attributes['name'] = AttributeType::String;

		return $attributes;
	}

	/**
	 * Populates a new model instance with a given set of attributes.
	 *
	 * @static
	 * @param mixed $attributes
	 * @return EntryDraftModel
	 */
	public static function populateModel($attributes)
	{
		if ($attributes instanceof \CModel)
		{
			$attributes = $attributes->getAttributes();
		}

		// Merge the draft and entry data
		$entryData = $attributes['data'];
		$fieldContent = $entryData['fields'];
		$attributes['draftId'] = $attributes['id'];
		$attributes['id'] = $attributes['entryId'];
		$title = $entryData['title'];
		unset($attributes['data'], $entryData['fields'], $attributes['entryId'], $entryData['title']);

		$attributes = array_merge($attributes, $entryData);

		// Initialize the draft
		$draft = parent::populateModel($attributes);
		$draft->getContent()->title = $title;
		$draft->getContent()->setValuesByFieldId($fieldContent);

		return $draft;
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
