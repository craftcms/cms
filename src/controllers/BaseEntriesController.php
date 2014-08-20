<?php
namespace Craft;

/**
 * BaseController is a base class that any entry related controllers, such as {@link EntriesController} and
 * {@link EntryRevisionsController} extend to share common functionality.
 *
 * It extend's Yii's {@link \CController} overwriting specific methods as required.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.controllers
 * @since     2.1
 */
abstract class BaseEntriesController extends BaseController
{
	// Protected Methods
	// =========================================================================

	/**
	 * Enforces all Edit Entry permissions.
	 *
	 * @param EntryModel $entry
	 *
	 * @return null
	 */
	protected function enforceEditEntryPermissions(EntryModel $entry)
	{
		$userSessionService = craft()->userSession;
		$permissionSuffix = ':'.$entry->sectionId;

		if (craft()->isLocalized())
		{
			// Make sure they have access to this locale
			$userSessionService->requirePermission('editLocale:'.$entry->locale);
		}

		// Make sure the user is allowed to edit entries in this section
		$userSessionService->requirePermission('editEntries'.$permissionSuffix);

		// Is it a new entry?
		if (!$entry->id)
		{
			// Make sure they have permission to create new entries in this section
			$userSessionService->requirePermission('createEntries'.$permissionSuffix);
		}
		else
		{
			switch ($entry->getClassHandle())
			{
				case 'Entry':
				{
					// If it's another user's entry (and it's not a Single), make sure they have permission to edit those
					if (
						$entry->authorId != $userSessionService->getUser()->id &&
						$entry->getSection()->type != SectionType::Single
					)
					{
						$userSessionService->requirePermission('editPeerEntries'.$permissionSuffix);
					}

					break;
				}

				case 'EntryDraft':
				{
					// If it's another user's draft, make sure they have permission to edit those
					if (
						$entry->getClassHandle() == 'EntryDraft' &&
						$entry->creatorId != $userSessionService->getUser()->id
					)
					{
						$userSessionService->requirePermission('editPeerEntryDrafts'.$permissionSuffix);
					}

					break;
				}
			}
		}
	}
}
