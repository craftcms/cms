<?php
namespace Craft;

/**
 * Class LocalizationService
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.services
 * @since     1.0
 */
class LocalizationService extends BaseApplicationComponent
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_appLocales;

	/**
	 * @var
	 */
	private $_siteLocales;

	/**
	 * @var
	 */
	private $_localeData;

	// Public Methods
	// =========================================================================

	/**
	 * Returns an array of all known locales. The list of known locales is based on whatever files exist in
	 * framework/i18n/data/.
	 *
	 * @return array An array of LocaleModel objects.
	 */
	public function getAllLocales()
	{
		$locales = array();
		$localeIds = LocaleData::getLocaleIds();

		foreach ($localeIds as $localeId)
		{
			$locales[] = new LocaleModel($localeId);
		}

		return $locales;
	}

	/**
	 * Returns an array of locales that Craft is translated into. The list of locales is based on whatever files exist
	 * in craft/app/translations/.
	 *
	 * @return array An array of {@link LocaleModel} objects.
	 */
	public function getAppLocales()
	{
		if (!$this->_appLocales)
		{
			$this->_appLocales = array(new LocaleModel('en_us'));

			$path = craft()->path->getCpTranslationsPath();
			$folders = IOHelper::getFolderContents($path, false, ".*\.php");

			if (is_array($folders) && count($folders) > 0)
			{
				foreach ($folders as $dir)
				{
					$localeId = IOHelper::getFileName($dir, false);
					if ($localeId != 'en_us')
					{
						$this->_appLocales[] = new LocaleModel($localeId);
					}
				}
			}
		}

		return $this->_appLocales;
	}

	/**
	 * Returns an array of the locale IDs which Craft has been translated into. The list of locales is based on whatever
	 * files exist in craft/app/translations/.
	 *
	 * @return array An array of locale IDs.
	 */
	public function getAppLocaleIds()
	{
		$locales = $this->getAppLocales();
		$localeIds = array();

		foreach ($locales as $locale)
		{
			$localeIds[] = $locale->id;
		}

		return $localeIds;
	}

	/**
	 * Returns an array of the site locales. The list of locales is based on whatever was defined in Settings > Locales
	 * in the control panel.
	 *
	 * @return array An array of {@link LocaleModel} objects.
	 */
	public function getSiteLocales()
	{
		if (!isset($this->_siteLocales))
		{
			$query = craft()->db->createCommand()
				->select('locale')
				->from('locales')
				->order('sortOrder');

			if (craft()->getEdition() != Craft::Pro)
			{
				$query->limit(1);
			}

			$localeIds = $query->queryColumn();

			foreach ($localeIds as $localeId)
			{
				$this->_siteLocales[] = new LocaleModel($localeId);
			}

			if (empty($this->_siteLocales))
			{
				$this->_siteLocales = array(new LocaleModel('en_us'));
			}
		}

		return $this->_siteLocales;
	}

	/**
	 * Returns the site's primary locale. The primary locale is whatever is listed first in Settings > Locales in the
	 * control panel.
	 *
	 * @return LocaleModel A {@link LocaleModel} object representing the primary locale.
	 */
	public function getPrimarySiteLocale()
	{
		$locales = $this->getSiteLocales();
		return $locales[0];
	}

	/**
	 * Returns the site's primary locale ID. The primary locale is whatever is listed first in Settings > Locales in the
	 * control panel.
	 *
	 * @return string The primary locale ID.
	 */
	public function getPrimarySiteLocaleId()
	{
		return $this->getPrimarySiteLocale()->getId();
	}

	/**
	 * Returns an array of the site locale IDs. The list of locales is based on whatever was defined in Settings > Locales
	 * in the control panel.
	 *
	 * @return array An array of locale IDs.
	 */
	public function getSiteLocaleIds()
	{
		$locales = $this->getSiteLocales();
		$localeIds = array();

		foreach ($locales as $locale)
		{
			$localeIds[] = $locale->id;
		}

		return $localeIds;
	}

	/**
	 * Returns a list of locales that are editable by the current user.
	 *
	 * @return array
	 */
	public function getEditableLocales()
	{
		if (craft()->isLocalized())
		{
			$locales = $this->getSiteLocales();
			$editableLocales = array();

			foreach ($locales as $locale)
			{
				if (craft()->userSession->checkPermission('editLocale:'.$locale->getId()))
				{
					$editableLocales[] = $locale;
				}
			}

			return $editableLocales;
		}
		else
		{
			return $this->getSiteLocales();
		}
	}

	/**
	 * Returns an array of the editable locale IDs.
	 *
	 * @return array
	 */
	public function getEditableLocaleIds()
	{
		$locales = $this->getEditableLocales();
		$localeIds = array();

		foreach ($locales as $locale)
		{
			$localeIds[] = $locale->id;
		}

		return $localeIds;
	}

	/**
	 * Returns a locale by its ID.
	 *
	 * @param string $localeId
	 *
	 * @return LocaleModel
	 */
	public function getLocaleById($localeId)
	{
		return new LocaleModel($localeId);
	}

	/**
	 * Adds a new site locale.
	 *
	 * @param string $localeId
	 *
	 * @return bool
	 */
	public function addSiteLocale($localeId)
	{
		$maxSortOrder = craft()->db->createCommand()->select('max(sortOrder)')->from('locales')->queryScalar();
		$affectedRows = craft()->db->createCommand()->insert('locales', array('locale' => $localeId, 'sortOrder' => $maxSortOrder+1));
		$success = (bool) $affectedRows;

		if ($success)
		{
			$this->_siteLocales[] = new LocaleModel($localeId);

			// Add this locale to each of the category groups
			$categoryLocales = craft()->db->createCommand()
				->select('groupId, urlFormat, nestedUrlFormat')
				->from('categorygroups_i18n')
				->where('locale = :locale', array(':locale' => $this->getPrimarySiteLocaleId()))
				->queryAll();

			if ($categoryLocales)
			{
				$newCategoryLocales = array();

				foreach ($categoryLocales as $categoryLocale)
				{
					$newCategoryLocales[] = array($categoryLocale['groupId'], $localeId, $categoryLocale['urlFormat'], $categoryLocale['nestedUrlFormat']);
				}

				craft()->db->createCommand()->insertAll('categorygroups_i18n', array('groupId', 'locale', 'urlFormat', 'nestedUrlFormat'), $newCategoryLocales);
			}

			// Fire an 'onAddLocale' event
			$this->onAddLocale(new Event($this, array(
				'localeId' => $localeId,
			)));

			// Re-save all of the localizable elements
			if (!craft()->tasks->areTasksPending('ResaveAllElements'))
			{
				craft()->tasks->createTask('ResaveAllElements', null, array(
					'locale'          => $this->getPrimarySiteLocaleId(),
					'localizableOnly' => true,
				));
			}
		}

		return $success;
	}

	/**
	 * Reorders the site's locales.
	 *
	 * @param array $localeIds
	 *
	 * @return bool
	 */
	public function reorderSiteLocales($localeIds)
	{
		$oldPrimaryLocaleId = $this->getPrimarySiteLocaleId();

		foreach ($localeIds as $sortOrder => $localeId)
		{
			craft()->db->createCommand()->update('locales', array('sortOrder' => $sortOrder+1), array('locale' => $localeId));
		}

		$this->_siteLocales = null;
		$newPrimaryLocaleId = $this->getPrimarySiteLocaleId();

		// Did the primary site locale just change?
		if ($oldPrimaryLocaleId != $newPrimaryLocaleId)
		{
			$this->_processNewPrimaryLocale($oldPrimaryLocaleId, $newPrimaryLocaleId);
		}

		return true;
	}

	/**
	 * Deletes a site locale.
	 *
	 * @param string      $localeId          The locale to be deleted.
	 * @param string|null $transferContentTo The locale that should take over the deleted locale’s content.
	 *
	 * @throws \CDbException
	 * @throws \Exception
	 * @return bool Whether the locale was successfully deleted.
	 */
	public function deleteSiteLocale($localeId, $transferContentTo)
	{
		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

		try
		{
			// Fire an 'onBeforeDeleteLocale' event
			$event = new Event($this, array(
				'localeId'          => $localeId,
				'transferContentTo' => $transferContentTo
			));

			$this->onBeforeDeleteLocale($event);

			// Is the event is giving us the go-ahead?
			if ($event->performAction)
			{
				// Get the section IDs that are enabled for this locale
				$sectionIds = craft()->db->createCommand()
					->select('sectionId')
					->from('sections_i18n')
					->where(array('locale' => $localeId))
					->queryColumn();

				// Figure out which ones are *only* enabled for this locale
				$soloSectionIds = array();

				foreach ($sectionIds as $sectionId)
				{
					$sectionLocales = craft()->sections->getSectionLocales($sectionId);

					if (count($sectionLocales) == 1 && $sectionLocales[0]->locale == $localeId)
					{
						$soloSectionIds[] = $sectionId;
					}
				}

				// Did we find any?
				if ($soloSectionIds)
				{
					// Should we enable those for a different locale?
					if ($transferContentTo)
					{
						craft()->db->createCommand()->update(
							'sections_i18n',
							array('locale' => $transferContentTo),
							array('in', 'sectionId', $soloSectionIds)
						);

						// Get all of the entry IDs in those sections
						$entryIds = craft()->db->createCommand()
							->select('id')
							->from('entries')
							->where(array('in', 'sectionId', $soloSectionIds))
							->queryColumn();

						if ($entryIds)
						{
							// Delete their template caches
							craft()->templateCache->deleteCachesByElementId($entryIds);

							// Update the entry tables
							craft()->db->createCommand()->update(
								'content',
								array('locale' => $transferContentTo),
								array('in', 'elementId', $entryIds)
							);

							craft()->db->createCommand()->update(
								'elements_i18n',
								array('locale' => $transferContentTo),
								array('in', 'elementId', $entryIds)
							);

							craft()->db->createCommand()->update(
								'entrydrafts',
								array('locale' => $transferContentTo),
								array('in', 'entryId', $entryIds)
							);

							craft()->db->createCommand()->update(
								'entryversions',
								array('locale' => $transferContentTo),
								array('in', 'entryId', $entryIds)
							);

							craft()->db->createCommand()->update(
								'relations',
								array('sourceLocale' => $transferContentTo),
								array('and', array('in', 'sourceId', $entryIds), 'sourceLocale is not null')
							);

							// All the Matrix tables
							$blockIds = craft()->db->createCommand()
								->select('id')
								->from('matrixblocks')
								->where(array('in', 'ownerId', $entryIds))
								->queryColumn();

							if ($blockIds)
							{
								craft()->db->createCommand()->update(
									'matrixblocks',
									array('ownerLocale' => $transferContentTo),
									array('and', array('in', 'id', $blockIds), 'ownerLocale is not null')
								);

								craft()->db->createCommand()->delete(
									'elements_i18n',
									array('and', array('in', 'elementId', $blockIds), 'locale = :transferContentTo'),
									array(':transferContentTo' => $transferContentTo)
								);

								craft()->db->createCommand()->update(
									'elements_i18n',
									array('locale' => $transferContentTo),
									array('and', array('in', 'elementId', $blockIds), 'locale = :localeId'),
									array(':localeId' => $localeId)
								);

								$matrixTablePrefix = craft()->db->addTablePrefix('matrixcontent_');
								$matrixTablePrefixLength = strlen($matrixTablePrefix);
								$tablePrefixLength = strlen(craft()->db->tablePrefix);

								foreach (craft()->db->getSchema()->getTableNames() as $tableName)
								{
									if (strncmp($tableName, $matrixTablePrefix, $matrixTablePrefixLength) === 0)
									{
										$tableName = substr($tableName, $tablePrefixLength);

										craft()->db->createCommand()->delete(
											$tableName,
											array('and', array('in', 'elementId', $blockIds), 'locale = :transferContentTo'),
											array(':transferContentTo' => $transferContentTo)
										);

										craft()->db->createCommand()->update(
											$tableName,
											array('locale' => $transferContentTo),
											array('and', array('in', 'elementId', $blockIds), 'locale = :localeId'),
											array(':localeId' => $localeId)
										);
									}
								}

								craft()->db->createCommand()->update(
									'relations',
									array('sourceLocale' => $transferContentTo),
									array('and', array('in', 'sourceId', $blockIds), 'sourceLocale is not null')
								);
							}
						}
					}
					else
					{
						// Delete those sections
						foreach ($soloSectionIds as $sectionId)
						{
							craft()->sections->deleteSectionById($sectionId);
						}
					}
				}

				$primaryLocaleId = $this->getPrimarySiteLocaleId();

				// Did the primary locale ID just get deleted?
				if ($primaryLocaleId === $localeId)
				{
					// Find out what's *about* to be the new primary locale, since we're going to nuke the current one
					// a few lines down.
					$allLocales = $this->getSiteLocaleIds();

					if (isset($allLocales[1]))
					{
						$newPrimaryLocaleId = $allLocales[1];
					}
					else
					{
						$newPrimaryLocaleId = false;
					}

					// Did the primary site locale just change?
					if ($primaryLocaleId != $newPrimaryLocaleId)
					{
						$this->_processNewPrimaryLocale($primaryLocaleId, $newPrimaryLocaleId);
					}
				}

				// Delete the locale
				$affectedRows = craft()->db->createCommand()->delete('locales', array('locale' => $localeId));
				$success = (bool) $affectedRows;

				// If it didn't work, rollback the transaction in case something changed in onBeforeDeleteLocale
				if (!$success)
				{
					if ($transaction !== null)
					{
						$transaction->rollback();
					}

					return false;
				}
			}
			else
			{
				$success = false;
			}

			// Commit the transaction regardless of whether we deleted the locale,
			// in case something changed in onBeforeDeleteLocale
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
			// Fire an 'onDeleteLocale' event
			$this->onDeleteLocale(new Event($this, array(
				'localeId'          => $localeId,
				'transferContentTo' => $transferContentTo
			)));
		}

		return $success;
	}

	/**
	 * Returns the localization data for a given locale.
	 *
	 * @param string $localeId
	 *
	 * @return LocaleData|null
	 */
	public function getLocaleData($localeId = null)
	{
		if (!$localeId)
		{
			$localeId = craft()->language;
		}

		if (!isset($this->_localeData) || !array_key_exists($localeId, $this->_localeData))
		{
			if (LocaleData::exists($localeId))
			{
				$this->_localeData[$localeId] = LocaleData::getInstance($localeId);
			}
			else
			{
				$this->_localeData[$localeId] = null;
			}
		}

		return $this->_localeData[$localeId];
	}

	// Events
	// -------------------------------------------------------------------------

	/**
	 * Fires an 'onBeforeDeleteLocale' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onBeforeDeleteLocale(Event $event)
	{
		$this->raiseEvent('onBeforeDeleteLocale', $event);
	}

	/**
	 * Fires an 'onAddLocale' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onAddLocale(Event $event)
	{
		$this->raiseEvent('onAddLocale', $event);
	}

	/**
	 * Fires an 'onDeleteLocale' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onDeleteLocale(Event $event)
	{
		$this->raiseEvent('onDeleteLocale', $event);
	}

	// Private Methods
	// =========================================================================

	/**
	 * @param $oldPrimaryLocaleId
	 * @param $newPrimaryLocaleId
	 */
	private function _processNewPrimaryLocale($oldPrimaryLocaleId, $newPrimaryLocaleId)
	{
		craft()->config->maxPowerCaptain();

		// Update all of the non-localized elements
		$nonLocalizedElementTypes = array();

		foreach (craft()->elements->getAllElementTypes() as $elementType)
		{
			if (!$elementType->isLocalized())
			{
				$nonLocalizedElementTypes[] = $elementType->getClassHandle();
			}
		}

		if ($nonLocalizedElementTypes)
		{
			$elementIds = craft()->db->createCommand()
				->select('id')
				->from('elements')
				->where(array('in', 'type', $nonLocalizedElementTypes))
				->queryColumn();

			if ($elementIds)
			{
				// To be sure we don't hit any unique constraint MySQL errors, first make sure there are no rows for
				// these elements that don't currently use the old primary locale
				$deleteConditions = array('and', array('in', 'elementId', $elementIds), 'locale != :locale');
				$deleteParams = array(':locale' => $oldPrimaryLocaleId);

				craft()->db->createCommand()->delete('elements_i18n', $deleteConditions, $deleteParams);
				craft()->db->createCommand()->delete('content', $deleteConditions, $deleteParams);

				// Now convert the locales
				$updateColumns = array('locale' => $newPrimaryLocaleId);
				$updateConditions = array('in', 'elementId', $elementIds);

				craft()->db->createCommand()->update('elements_i18n', $updateColumns, $updateConditions);
				craft()->db->createCommand()->update('content', $updateColumns, $updateConditions);
			}
		}
	}
}
