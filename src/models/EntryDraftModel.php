<?php
namespace Craft;

craft()->requireEdition(Craft::Client);

/**
 * Class EntryDraftModel
 *
 * @package craft.app.models
 */
class EntryDraftModel extends BaseEntryRevisionModel
{
	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array_merge(parent::defineAttributes(), array(
			'draftId' => AttributeType::Number,
			'name'    => AttributeType::String,
		));
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
		$fieldContent = isset($entryData['fields']) ? $entryData['fields'] : null;
		$attributes['draftId'] = $attributes['id'];
		$attributes['id'] = $attributes['entryId'];
		$attributes['revisionNotes'] = $attributes['notes'];
		$title = $entryData['title'];
		unset($attributes['data'], $entryData['fields'], $attributes['entryId'], $attributes['notes'], $entryData['title']);

		$attributes = array_merge($attributes, $entryData);

		// Initialize the draft
		$draft = parent::populateModel($attributes);

		if ($title)
		{
			$draft->getContent()->title = $title;
		}

		if ($fieldContent)
		{
			$draft->setContentFromRevision($fieldContent);
		}

		return $draft;
	}
}
