<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use craft\app\Craft;
use craft\app\enums\AttributeType;
use craft\app\models\EntryVersion  as EntryVersionModel;

craft()->requireEdition(Craft::Client);

/**
 * Class EntryVersion model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class EntryVersion extends BaseEntryRevisionModel
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseModel::populateModel()
	 *
	 * @param mixed $attributes
	 *
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
		$attributes['revisionNotes'] = $attributes['notes'];
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
			'versionId'   => AttributeType::Number,
			'num'         => AttributeType::Number,
		]);
	}
}
