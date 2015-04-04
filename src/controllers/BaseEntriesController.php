<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\elements\Entry;
use craft\app\models\EntryDraft;
use craft\app\models\Section;
use craft\app\web\Controller;

/**
 * BaseEntriesController is a base class that any entry-related controllers, such as [[EntriesController]] and
 * [[EntryRevisionsController]], extend to share common functionality.
 *
 * It extends [[Controller]], overwriting specific methods as required.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class BaseEntriesController extends Controller
{
	// Protected Methods
	// =========================================================================

	/**
	 * Enforces all Edit Entry permissions.
	 *
	 * @param Entry $entry
	 *
	 * @return null
	 */
	protected function enforceEditEntryPermissions(Entry $entry)
	{
		$userSessionService = Craft::$app->getUser();
		$permissionSuffix = ':'.$entry->sectionId;

		if (Craft::$app->isLocalized())
		{
			// Make sure they have access to this locale
			$this->requirePermission('editLocale:'.$entry->locale);
		}

		// Make sure the user is allowed to edit entries in this section
		$this->requirePermission('editEntries'.$permissionSuffix);

		// Is it a new entry?
		if (!$entry->id)
		{
			// Make sure they have permission to create new entries in this section
			$this->requirePermission('createEntries'.$permissionSuffix);
		}
		else
		{
			switch ($entry::className())
			{
				case Entry::className():
				{
					// If it's another user's entry (and it's not a Single), make sure they have permission to edit those
					if (
						$entry->authorId != $userSessionService->getIdentity()->id &&
						$entry->getSection()->type != Section::TYPE_SINGLE
					)
					{
						$this->requirePermission('editPeerEntries'.$permissionSuffix);
					}

					break;
				}

				case EntryDraft::className():
				{
					// If it's another user's draft, make sure they have permission to edit those
					/** @var EntryDraft $entry */
					if ($entry->creatorId != $userSessionService->getIdentity()->id)
					{
						$this->requirePermission('editPeerEntryDrafts'.$permissionSuffix);
					}

					break;
				}
			}
		}
	}
}
