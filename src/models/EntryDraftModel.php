<?php
namespace Craft;

craft()->requireEdition(Craft::Client);

/**
 * Class EntryDraftModel
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.models
 * @since     1.0
 */
class EntryDraftModel extends BaseEntryRevisionModel
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseModel::populateModel()
	 *
	 * @param mixed $attributes
	 *
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

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseModel::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array_merge(parent::defineAttributes(), array(
			'draftId' => AttributeType::Number,
			'name'    => AttributeType::String,
		));
	}
}
