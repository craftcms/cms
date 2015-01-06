<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use craft\app\Craft;
use craft\app\enums\AttributeType;
use craft\app\models\EntryDraft as EntryDraftModel;

craft()->requireEdition(Craft::Client);

/**
 * Class EntryDraft model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class EntryDraft extends BaseEntryRevisionModel
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
		return array_merge(parent::defineAttributes(), [
			'draftId' => AttributeType::Number,
			'name'    => AttributeType::String,
		]);
	}
}
