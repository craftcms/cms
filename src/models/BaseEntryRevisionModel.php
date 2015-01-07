<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use craft\app\Craft;
use craft\app\enums\AttributeType;
use craft\app\helpers\ElementHelper;
use craft\app\models\Entry           as EntryModel;
use craft\app\models\User            as UserModel;

Craft::$app->requireEdition(Craft::Client);

/**
 * Class BaseEntryRevision model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class BaseEntryRevisionModel extends EntryModel
{
	// Public Methods
	// =========================================================================

	/**
	 * Sets the revision content.
	 *
	 * @param array $content
	 *
	 * @return null
	 */
	public function setContentFromRevision($content)
	{
		// Swap the field IDs with handles
		$contentByFieldHandles = array();

		foreach ($content as $fieldId => $value)
		{
			$field = Craft::$app->fields->getFieldById($fieldId);

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
		return Craft::$app->users->getUserById($this->creatorId);
	}

	/**
	 * Returns the element's full URL.
	 *
	 * @return string
	 */
	public function getUrl()
	{
		if ($this->uri === null)
		{
			ElementHelper::setUniqueUri($this);
		}

		return parent::getUrl();
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
			'creatorId'   => AttributeType::Number,
			'dateUpdated' => AttributeType::DateTime,
			'dateCreated' => AttributeType::DateTime,
		]);
	}
}
