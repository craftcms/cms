<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\enums\ElementType;
use craft\app\enums\SectionType;
use craft\app\errors\Exception;
use craft\app\events\EntryEvent;
use craft\app\helpers\DateTimeHelper;
use craft\app\models\Entry as EntryModel;
use craft\app\records\Entry as EntryRecord;
use yii\base\Component;

/**
 * The Entries service provides APIs for managing entries in Craft.
 *
 * An instance of the Entries service is globally accessible in Craft via [[Application::entries `Craft::$app->entries`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Entries extends Component
{
	// Constants
	// =========================================================================

	/**
     * @event Event The event that is triggered before an entry is saved.
     */
    const EVENT_BEFORE_SAVE_ENTRY = 'beforeSaveEntry';

	/**
     * @event Event The event that is triggered after an entry is saved.
     */
    const EVENT_AFTER_SAVE_ENTRY = 'afterSaveEntry';

	/**
     * @event Event The event that is triggered before an entry is deleted.
     */
    const EVENT_BEFORE_DELETE_ENTRY = 'beforeDeleteEntry';

	/**
     * @event Event The event that is triggered after an entry is deleted.
     */
    const EVENT_AFTER_DELETE_ENTRY = 'afterDeleteEntry';

	// Public Methods
	// =========================================================================

	/**
	 * Returns an entry by its ID.
	 *
	 * ```php
	 * $entry = Craft::$app->entries->getEntryById($entryId);
	 * ```
	 *
	 * @param int    $entryId  The entry’s ID.
	 * @param string $localeId The locale to fetch the entry in.
	 *                         Defaults to [[Application::getLanguage() `Craft::$app->getLanguage()`]].
	 *
	 * @return EntryModel|null The entry with the given ID, or `null` if an entry could not be found.
	 */
	public function getEntryById($entryId, $localeId = null)
	{
		return Craft::$app->elements->getElementById($entryId, ElementType::Entry, $localeId);
	}

	/**
	 * Saves a new or existing entry.
	 *
	 * ```php
	 * $entry = new EntryModel();
	 * $entry->sectionId = 10;
	 * $entry->typeId    = 1;
	 * $entry->authorId  = 5;
	 * $entry->enabled   = true;
	 *
	 * $entry->getContent()->title = "Hello World!";
	 *
	 * $entry->setContentFromPost(array(
	 *     'body' => "<p>I can’t believe I literally just called this “Hello World!”.</p>",
	 * ));
	 *
	 * $success = Craft::$app->entries->saveEntry($entry);
	 *
	 * if (!$success)
	 * {
	 *     Craft::log('Couldn’t save the entry "'.$entry->title.'"', LogLevel::Error);
	 * }
	 * ```
	 *
	 * @param EntryModel $entry The entry to be saved.
	 *
	 * @return bool
	 * @throws Exception
	 * @throws \Exception
	 */
	public function saveEntry(EntryModel $entry)
	{
		$isNewEntry = !$entry->id;

		$hasNewParent = $this->_checkForNewParent($entry);

		if ($hasNewParent)
		{
			if ($entry->newParentId)
			{
				$parentEntry = $this->getEntryById($entry->newParentId, $entry->locale);

				if (!$parentEntry)
				{
					throw new Exception(Craft::t('No entry exists with the ID “{id}”.', ['id' => $entry->newParentId]));
				}
			}
			else
			{
				$parentEntry = null;
			}

			$entry->setParent($parentEntry);
		}

		// Get the entry record
		if (!$isNewEntry)
		{
			$entryRecord = EntryRecord::model()->findById($entry->id);

			if (!$entryRecord)
			{
				throw new Exception(Craft::t('No entry exists with the ID “{id}”.', ['id' => $entry->id]));
			}
		}
		else
		{
			$entryRecord = new EntryRecord();
		}

		// Get the section
		$section = Craft::$app->sections->getSectionById($entry->sectionId);

		if (!$section)
		{
			throw new Exception(Craft::t('No section exists with the ID “{id}”.', ['id' => $entry->sectionId]));
		}

		// Verify that the section is available in this locale
		$sectionLocales = $section->getLocales();

		if (!isset($sectionLocales[$entry->locale]))
		{
			throw new Exception(Craft::t('The section “{section}” is not enabled for the locale {locale}', ['section' => $section->name, 'locale' => $entry->locale]));
		}

		// Set the entry data
		$entryType = $entry->getType();

		$entryRecord->sectionId  = $entry->sectionId;

		if ($section->type == SectionType::Single)
		{
			$entryRecord->authorId   = $entry->authorId = null;
			$entryRecord->expiryDate = $entry->expiryDate = null;
		}
		else
		{
			$entryRecord->authorId   = $entry->authorId;
			$entryRecord->postDate   = $entry->postDate;
			$entryRecord->expiryDate = $entry->expiryDate;
			$entryRecord->typeId     = $entryType->id;
		}

		if ($entry->enabled && !$entryRecord->postDate)
		{
			// Default the post date to the current date/time
			$entryRecord->postDate = $entry->postDate = DateTimeHelper::currentUTCDateTime();
		}

		$entryRecord->validate();
		$entry->addErrors($entryRecord->getErrors());

		if ($entry->hasErrors())
		{
			return false;
		}

		if (!$entryType->hasTitleField)
		{
			$entry->getContent()->title = Craft::$app->templates->renderObjectTemplate($entryType->titleFormat, $entry);
		}

		$transaction = Craft::$app->db->getCurrentTransaction() === null ? Craft::$app->db->beginTransaction() : null;

		try
		{
			// Fire a 'beforeSaveEntry' event
			$event = new EntryEvent([
				'entry' => $entry
			]);

			$this->trigger(static::EVENT_BEFORE_SAVE_ENTRY, $event);

			// Is the event giving us the go-ahead?
			if ($event->performAction)
			{
				// Save the element
				$success = Craft::$app->elements->saveElement($entry);

				// If it didn't work, rollback the transaction in case something changed in onBeforeSaveEntry
				if (!$success)
				{
					if ($transaction !== null)
					{
						$transaction->rollback();
					}

					// If "title" has an error, check if they've defined a custom title label.
					if ($entry->getError('title'))
					{
						// Grab all of the original errors.
						$errors = $entry->getErrors();

						// Grab just the title error message.
						$originalTitleError = $errors['title'];

						// Clear the old.
						$entry->clearErrors();

						// Create the new "title" error message.
						$errors['title'] = str_replace('Title', $entryType->titleLabel, $originalTitleError);

						// Add all of the errors back on the model.
						$entry->addErrors($errors);
					}

					return false;
				}

				// Now that we have an element ID, save it on the other stuff
				if ($isNewEntry)
				{
					$entryRecord->id = $entry->id;
				}

				// Save the actual entry row
				$entryRecord->save(false);

				if ($section->type == SectionType::Structure)
				{
					// Has the parent changed?
					if ($hasNewParent)
					{
						if (!$entry->newParentId)
						{
							Craft::$app->structures->appendToRoot($section->structureId, $entry);
						}
						else
						{
							Craft::$app->structures->append($section->structureId, $entry, $parentEntry);
						}
					}

					// Update the entry's descendants, who may be using this entry's URI in their own URIs
					Craft::$app->elements->updateDescendantSlugsAndUris($entry);
				}

				// Save a new version
				if (Craft::$app->getEdition() >= Craft::Client && $section->enableVersioning)
				{
					Craft::$app->entryRevisions->saveVersion($entry);
				}
			}
			else
			{
				$success = false;
			}

			// Commit the transaction regardless of whether we saved the entry, in case something changed
			// in onBeforeSaveEntry
			if ($transaction !== null)
			{
				$transaction->commit();
			}
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}

		if ($success)
		{
			// Fire an 'afterSaveEntry' event
			$this->trigger(static::EVENT_AFTER_SAVE_ENTRY, new EntryEvent([
				'entry' => $entry
			]));
		}

		return $success;
	}

	/**
	 * Deletes an entry(s).
	 *
	 * @param EntryModel|EntryModel[] $entries An entry, or an array of entries, to be deleted.
	 *
	 * @throws \Exception
	 * @return bool Whether the entry deletion was successful.
	 */
	public function deleteEntry($entries)
	{
		if (!$entries)
		{
			return false;
		}

		$transaction = Craft::$app->db->getCurrentTransaction() === null ? Craft::$app->db->beginTransaction() : null;

		try
		{
			if (!is_array($entries))
			{
				$entries = [$entries];
			}

			$entryIds = [];

			foreach ($entries as $entry)
			{
				// Fire a 'beforeDeleteEntry' event
				$this->trigger(static::EVENT_BEFORE_DELETE_ENTRY, new EntryEvent([
					'entry' => $entry
				]));

				$section = $entry->getSection();

				if ($section->type == SectionType::Structure)
				{
					// First let's move the entry's children up a level, so this doesn't mess up the structure
					$children = $entry->getChildren()->status(null)->localeEnabled(false)->limit(null)->find();

					foreach ($children as $child)
					{
						Craft::$app->structures->moveBefore($section->structureId, $child, $entry, 'update', true);
					}
				}

				$entryIds[] = $entry->id;
			}

			// Delete 'em
			$success = Craft::$app->elements->deleteElementById($entryIds);

			if ($transaction !== null)
			{
				$transaction->commit();
			}
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}

		if ($success)
		{
			foreach ($entries as $entry)
			{
				// Fire an 'afterDeleteEntry' event
				$this->trigger(static::EVENT_AFTER_DELETE_ENTRY, new EntryEvent([
					'entry' => $entry
				]));
			}

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Deletes an entry(s) by its ID.
	 *
	 * @param int|array $entryId The ID of an entry to delete, or an array of entry IDs.
	 *
	 * @return bool Whether the entry deletion was successful.
	 */
	public function deleteEntryById($entryId)
	{
		if (!$entryId)
		{
			return false;
		}

		$criteria = Craft::$app->elements->getCriteria(ElementType::Entry);
		$criteria->id = $entryId;
		$criteria->limit = null;
		$criteria->status = null;
		$criteria->localeEnabled = false;
		$entries = $criteria->find();

		if ($entries)
		{
			return $this->deleteEntry($entries);
		}
		else
		{
			return false;
		}
	}

	// Private Methods
	// =========================================================================

	/**
	 * Checks if an entry was submitted with a new parent entry selected.
	 *
	 * @param EntryModel $entry
	 *
	 * @return bool
	 */
	private function _checkForNewParent(EntryModel $entry)
	{
		// Make sure this is a Structure section
		if ($entry->getSection()->type != SectionType::Structure)
		{
			return false;
		}

		// Is it a brand new entry?
		if (!$entry->id)
		{
			return true;
		}

		// Was a new parent ID actually submitted?
		if ($entry->newParentId === null)
		{
			return false;
		}

		// Is it set to the top level now, but it hadn't been before?
		if ($entry->newParentId === '' && $entry->level != 1)
		{
			return true;
		}

		// Is it set to be under a parent now, but didn't have one before?
		if ($entry->newParentId !== '' && $entry->level == 1)
		{
			return true;
		}

		// Is the parentId set to a different entry ID than its previous parent?
		$criteria = Craft::$app->elements->getCriteria(ElementType::Entry);
		$criteria->ancestorOf = $entry;
		$criteria->ancestorDist = 1;
		$criteria->status = null;
		$criteria->localeEnabled = null;

		$oldParent = $criteria->first();
		$oldParentId = ($oldParent ? $oldParent->id : '');

		if ($entry->newParentId != $oldParentId)
		{
			return true;
		}

		// Must be set to the same one then
		return false;
	}
}
