<?php
namespace Craft;

craft()->requireEdition(Craft::Client);

/**
 *
 */
class EntryVersionModel extends BaseEntryRevisionModel
{
	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array_merge(parent::defineAttributes(), array(
			'versionId'   => AttributeType::Number,
			'num'         => AttributeType::Number,
		));
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
		$fieldContent = isset($entryData['fields']) ? $entryData['fields'] : null;
		$attributes['versionId'] = $attributes['id'];
		$attributes['id'] = $attributes['entryId'];
		$attributes['versionNotes'] = $attributes['notes'];
		$title = $entryData['title'];
		unset($attributes['data'], $entryData['fields'], $attributes['entryId'], $attributes['notes'], $entryData['title']);

		$attributes = array_merge($attributes, $entryData);

		// Initialize the version
		$version = parent::populateModel($attributes);
		$version->getContent()->title = $title;

		if ($fieldContent)
		{
			$version->setContentFromRevision($fieldContent);
		}

		return $version;
	}
}
