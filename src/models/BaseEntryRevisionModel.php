<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\helpers\ElementHelper;
use craft\app\elements\Entry;
use craft\app\elements\User;

Craft::$app->requireEdition(Craft::Client);

/**
 * Class BaseEntryRevision model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class BaseEntryRevisionModel extends Entry
{
	// Public Methods
	// =========================================================================

	/**
	 * @var integer The revision creator’s user ID
	 */
	public $creatorId;

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
		$contentByFieldHandles = [];

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
	 * @return User|null
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
}
