<?php
namespace Craft;

Craft::requirePackage(CraftPackage::PublishPro);

/**
 *
 */
class EntryVersionModel extends EntryModel
{
	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		$attributes = parent::defineAttributes();

		$attributes['versionId'] = AttributeType::Number;
		$attributes['creatorId'] = AttributeType::Number;
		$attributes['notes'] = AttributeType::String;
		$attributes['dateCreated'] = AttributeType::DateTime;

		return $attributes;
	}

	/**
	 * Populates a new model instance with a given set of attributes.
	 *
	 * @static
	 * @param mixed $attributes
	 * @return EntryVersionModel
	 */
	public static function populateModel($attributes)
	{
		if ($attributes instanceof \CModel)
		{
			$attributes = $attributes->getAttributes();
		}

		// Merge the version and entry data
		$entryData = $attributes['data'];
		$fieldContent = $entryData['fields'];
		$attributes['versionId'] = $attributes['id'];
		$attributes['id'] = $attributes['entryId'];
		$title = $entryData['title'];
		unset($attributes['data'], $entryData['fields'], $attributes['entryId'], $entryData['title']);

		$attributes = array_merge($attributes, $entryData);

		// Initialize the version
		$version = parent::populateModel($attributes);
		$version->getContent()->title = $title;
		$version->getContent()->setValuesByFieldId($fieldContent);

		return $version;
	}

	/**
	 * Returns the version's creator.
	 *
	 * @return UserModel|null
	 */
	public function getCreator()
	{
		return craft()->users->getUserById($this->creatorId);
	}
}
