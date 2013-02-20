<?php
namespace Blocks;

Blocks::requirePackage(BlocksPackage::PublishPro);

/**
 *
 */
class EntryVersionModel extends SectionEntryModel
{
	/**
	 * @return array
	 */
	public function defineAttributes()
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
		unset($attributes['data'], $entryData['fields'], $attributes['entryId']);

		$attributes = array_merge($attributes, $entryData);

		// Initialize the version
		$version = parent::populateModel($attributes);
		$version->setContentIndexedByFieldId($fieldContent);

		return $version;
	}

	/**
	 * Returns the version's creator.
	 *
	 * @return UserModel|null
	 */
	public function getCreator()
	{
		return blx()->users->getUserById($this->creatorId);
	}
}
