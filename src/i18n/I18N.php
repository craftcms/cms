<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\i18n;

use Craft;
use craft\app\db\Query;
use craft\app\events\DeleteLocaleEvent;
use craft\app\helpers\IOHelper;
use craft\app\tasks\ResaveAllElements;
use ResourceBundle;

/**
 * @inheritdoc
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class I18N extends \yii\i18n\I18N
{
	// Constants
	// =========================================================================

	/**
	 * @event DeleteLocaleEvent The event that is triggered before a locale is deleted.
	 *
	 * You may set [[DeleteLocaleEvent::performAction]] to `false` to prevent the locale from getting deleted.
	 */
	const EVENT_BEFORE_DELETE_LOCALE = 'beforeDeleteLocale';

	/**
	 * @event DeleteLocaleEvent The event that is triggered after a locale is deleted.
	 */
	const EVENT_AFTER_DELETE_LOCALE = 'afterDeleteLocale';

	// Properties
	// =========================================================================

	/**
	 * @var boolean Whether the [PHP intl extension](http://php.net/manual/en/book.intl.php) is loaded.
	 */
	private $_intlLoaded = false;

	/**
	 * @var array All of the known locales
	 * @see getAllLocales()
	 */
	private $_allLocaleIds;

	/**
	 * @var
	 */
	private $_appLocales;

	/**
	 * @var
	 */
	private $_siteLocales;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->_intlLoaded = extension_loaded('intl');
	}

	/**
	 * Returns whether the [Intl extension](http://php.net/manual/en/book.intl.php) is loaded.
	 *
	 * @return boolean Whether the Intl extension is loaded.
	 */
	public function getIsIntlLoaded()
	{
		return $this->_intlLoaded;
	}

	/**
	 * Returns a locale by its ID.
	 *
	 * @param string $localeId
	 *
	 * @return Locale
	 */
	public function getLocaleById($localeId)
	{
		return new Locale($localeId);
	}

	/**
	 * Returns an array of all known locale IDs.
	 *
	 * If the [PHP intl extension](http://php.net/manual/en/book.intl.php) is loaded, then this will be based on
	 * all of the locale IDs it knows about. Otherwise, it will be based on the locale data files located in
	 * craft/app/config/locales/ and craft/config/locales/.
	 *
	 * @return array An array of locale IDs.
	 * @link http://php.net/manual/en/resourcebundle.locales.php
	 */
	public function getAllLocaleIds()
	{
		if ($this->_allLocaleIds === null)
		{
			if ($this->getIsIntlLoaded())
			{
				$this->_allLocaleIds = ResourceBundle::getLocales(null);
			}
			else
			{
				$appLocalesPath = Craft::$app->getPath()->getAppPath().'/config/locales';
				$customLocalesPath = Craft::$app->getPath()->getConfigPath().'/locales';

				$localeFiles = IOHelper::getFolderContents($appLocalesPath, false, '\.php$');
				$customLocaleFiles = IOHelper::getFolderContents($customLocalesPath, false, '\.php$');

				if ($customLocaleFiles !== false)
				{
					$localeFiles = array_merge($localeFiles, $customLocaleFiles);
				}

				$this->_allLocaleIds = [];

				foreach ($localeFiles as $file)
				{
					$this->_allLocaleIds[] = IOHelper::getFilename($file, false);
				}
			}

			// Hyphens, not underscores
			foreach ($this->_allLocaleIds as $i => $locale)
			{
				$this->_allLocaleIds[$i] = str_replace('_', '-', $locale);
			}
		}

		return $this->_allLocaleIds;
	}

	/**
	 * Returns an array of all known locales.
	 *
	 * @return Locale[] An array of [[Locale]] objects.
	 * @see getAllLocaleIds()
	 */
	public function getAllLocales()
	{
		$locales = [];
		$localeIds = $this->getAllLocaleIds();

		foreach ($localeIds as $localeId)
		{
			$locales[] = new Locale($localeId);
		}

		return $locales;
	}

	// Application Locales
	// -------------------------------------------------------------------------

	/**
	 * Returns an array of locales that Craft is translated into. The list of locales is based on whatever files exist
	 * in craft/app/translations/.
	 *
	 * @return Locale[] An array of [[Locale]] objects.
	 */
	public function getAppLocales()
	{
		if ($this->_appLocales === null)
		{
			$this->_appLocales = [new Locale('en-US')];

			$path = Craft::$app->getPath()->getCpTranslationsPath();
			$folders = IOHelper::getFolderContents($path, false, ".*\.php");

			if (is_array($folders) && count($folders) > 0)
			{
				foreach ($folders as $dir)
				{
					$localeId = IOHelper::getFilename($dir, false);

					if ($localeId != 'en-US')
					{
						$this->_appLocales[] = new Locale($localeId);
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
		$localeIds = [];

		foreach ($locales as $locale)
		{
			$localeIds[] = $locale->id;
		}

		return $localeIds;
	}

	// Site Locales
	// -------------------------------------------------------------------------

	/**
	 * Returns an array of the site locales. The list of locales is based on whatever was defined in Settings > Locales
	 * in the control panel.
	 *
	 * @return Locale[] An array of [[Locale]] objects.
	 */
	public function getSiteLocales()
	{
		if ($this->_siteLocales === null)
		{
			$query = (new Query())
				->select('locale')
				->from('{{%locales}}')
				->orderBy('sortOrder');

			if (Craft::$app->getEdition() != Craft::Pro)
			{
				$query->limit(1);
			}

			$localeIds = $query->column();

			foreach ($localeIds as $localeId)
			{
				$this->_siteLocales[] = new Locale($localeId);
			}

			if (empty($this->_siteLocales))
			{
				$this->_siteLocales = [new Locale('en-US')];
			}
		}

		return $this->_siteLocales;
	}

	/**
	 * Returns the site's primary locale. The primary locale is whatever is listed first in Settings > Locales in the
	 * control panel.
	 *
	 * @return Locale A [[Locale]] object representing the primary locale.
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
		return $this->getPrimarySiteLocale()->id;
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
		$localeIds = [];

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
		if (Craft::$app->isLocalized())
		{
			$locales = $this->getSiteLocales();
			$editableLocales = [];

			foreach ($locales as $locale)
			{
				if (Craft::$app->getUser()->checkPermission('editLocale:'.$locale->id))
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
		$localeIds = [];

		foreach ($locales as $locale)
		{
			$localeIds[] = $locale->id;
		}

		return $localeIds;
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
		$maxSortOrder = (new Query())
			->from('{{%locales}}')
			->max('sortOrder');

		$affectedRows = Craft::$app->getDb()->createCommand()->insert('{{%locales}}', [
			'locale' => $localeId,
			'sortOrder' => $maxSortOrder+1
		])->execute();

		$success = (bool) $affectedRows;

		if ($success)
		{
			$this->_siteLocales[] = new Locale($localeId);

			// Add this locale to each of the category groups
			$categoryLocales = (new Query())
				->select(['groupId', 'urlFormat', 'nestedUrlFormat'])
				->from('{{%categorygroups_i18n}}')
				->where('locale = :locale', [':locale' => $this->getPrimarySiteLocaleId()])
				->all();

			if ($categoryLocales)
			{
				$newCategoryLocales = [];

				foreach ($categoryLocales as $categoryLocale)
				{
					$newCategoryLocales[] = [$categoryLocale['groupId'], $localeId, $categoryLocale['urlFormat'], $categoryLocale['nestedUrlFormat']];
				}

				Craft::$app->getDb()->createCommand()->batchInsert(
					'categorygroups_i18n',
					['groupId', 'locale', 'urlFormat', 'nestedUrlFormat'],
					$newCategoryLocales
				)->execute();
			}

			// Re-save all of the localizable elements
			if (!Craft::$app->getTasks()->areTasksPending(ResaveAllElements::className()))
			{
				Craft::$app->getTasks()->queueTask([
					'type'            => ResaveAllElements::className(),
					'locale'          => $this->getPrimarySiteLocaleId(),
					'localizableOnly' => true,
				]);
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
			Craft::$app->getDb()->createCommand()->update(
				'locales',
				['sortOrder' => $sortOrder+1],
				['locale' => $localeId]
			)->execute();
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
	 * @throws \Exception
	 * @return bool Whether the locale was successfully deleted.
	 */
	public function deleteSiteLocale($localeId, $transferContentTo)
	{
		$transaction = Craft::$app->getDb()->getTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;

		try
		{
			// Fire a 'beforeDeleteLocale' event
			$event = new DeleteLocaleEvent([
				'localeId'          => $localeId,
				'transferContentTo' => $transferContentTo
			]);

			$this->trigger(static::EVENT_BEFORE_DELETE_LOCALE, $event);

			// Is the event is giving us the go-ahead?
			if ($event->performAction)
			{
				// Get the section IDs that are enabled for this locale
				$sectionIds = (new Query())
					->select('sectionId')
					->from('{{%sections_i18n}}')
					->where(['locale' => $localeId])
					->column();

				// Figure out which ones are *only* enabled for this locale
				$soloSectionIds = [];

				foreach ($sectionIds as $sectionId)
				{
					$sectionLocales = Craft::$app->getSections()->getSectionLocales($sectionId);

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
						Craft::$app->getDb()->createCommand()->update(
							'sections_i18n',
							['locale' => $transferContentTo],
							['in', 'sectionId', $soloSectionIds]
						)->execute();

						// Get all of the entry IDs in those sections
						$entryIds = (new Query())
							->select('id')
							->from('{{%entries}}')
							->where(['in', 'sectionId', $soloSectionIds])
							->column();

						if ($entryIds)
						{
							// Delete their template caches
							Craft::$app->getTemplateCache()->deleteCachesByElementId($entryIds);

							// Update the entry tables
							Craft::$app->getDb()->createCommand()->update(
								'content',
								['locale' => $transferContentTo],
								['in', 'elementId', $entryIds]
							)->execute();

							Craft::$app->getDb()->createCommand()->update(
								'elements_i18n',
								['locale' => $transferContentTo],
								['in', 'elementId', $entryIds]
							)->execute();

							Craft::$app->getDb()->createCommand()->update(
								'entrydrafts',
								['locale' => $transferContentTo],
								['in', 'entryId', $entryIds]
							)->execute();

							Craft::$app->getDb()->createCommand()->update(
								'entryversions',
								['locale' => $transferContentTo],
								['in', 'entryId', $entryIds]
							)->execute();

							Craft::$app->getDb()->createCommand()->update(
								'relations',
								['sourceLocale' => $transferContentTo],
								['and', ['in', 'sourceId', $entryIds], 'sourceLocale is not null']
							)->execute();

							// All the Matrix tables
							$blockIds = (new Query())
								->select('id')
								->from('{{%matrixblocks}}')
								->where(['in', 'ownerId', $entryIds])
								->column();

							if ($blockIds)
							{
								Craft::$app->getDb()->createCommand()->update(
									'matrixblocks',
									['ownerLocale' => $transferContentTo],
									['and', ['in', 'id', $blockIds], 'ownerLocale is not null']
								)->execute();

								Craft::$app->getDb()->createCommand()->delete(
									'elements_i18n',
									['and', ['in', 'elementId', $blockIds], 'locale = :transferContentTo'],
									[':transferContentTo' => $transferContentTo]
								)->execute();

								Craft::$app->getDb()->createCommand()->update(
									'elements_i18n',
									['locale' => $transferContentTo],
									['and', ['in', 'elementId', $blockIds], 'locale = :localeId'],
									[':localeId' => $localeId]
								)->execute();

								$matrixTablePrefix = Craft::$app->getDb()->getSchema()->getRawTableName('{{%matrixcontent_}}');
								$matrixTablePrefixLength = strlen($matrixTablePrefix);
								$tablePrefixLength = strlen(Craft::$app->getDb()->tablePrefix);

								foreach (Craft::$app->getDb()->getSchema()->getTableNames() as $tableName)
								{
									if (strncmp($tableName, $matrixTablePrefix, $matrixTablePrefixLength) === 0)
									{
										$tableName = substr($tableName, $tablePrefixLength);

										Craft::$app->getDb()->createCommand()->delete(
											$tableName,
											['and', ['in', 'elementId', $blockIds], 'locale = :transferContentTo'],
											[':transferContentTo' => $transferContentTo]
										)->execute();

										Craft::$app->getDb()->createCommand()->update(
											$tableName,
											['locale' => $transferContentTo],
											['and', ['in', 'elementId', $blockIds], 'locale = :localeId'],
											[':localeId' => $localeId]
										)->execute();
									}
								}

								Craft::$app->getDb()->createCommand()->update(
									'relations',
									['sourceLocale' => $transferContentTo],
									['and', ['in', 'sourceId', $blockIds], 'sourceLocale is not null']
								)->execute();
							}
						}
					}
					else
					{
						// Delete those sections
						foreach ($soloSectionIds as $sectionId)
						{
							Craft::$app->getSections()->deleteSectionById($sectionId);
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
				$affectedRows = Craft::$app->getDb()->createCommand()->delete('{{%locales}}', ['locale' => $localeId])->execute();
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
			// Fire an 'afterDeleteLocale' event
			$this->trigger(static::EVENT_AFTER_DELETE_LOCALE, new DeleteLocaleEvent([
				'localeId'          => $localeId,
				'transferContentTo' => $transferContentTo
			]));
		}

		return $success;
	}

	// Private Methods
	// =========================================================================

	/**
	 * @param $oldPrimaryLocaleId
	 * @param $newPrimaryLocaleId
	 */
	private function _processNewPrimaryLocale($oldPrimaryLocaleId, $newPrimaryLocaleId)
	{
		Craft::$app->getConfig()->maxPowerCaptain();

		// Update all of the non-localized elements
		$nonLocalizedElementTypes = [];

		foreach (Craft::$app->getElements()->getAllElementTypes() as $elementType)
		{
			/** ElementInterface|Element $elementType */
			if (!$elementType::isLocalized())
			{
				$nonLocalizedElementTypes[] = $elementType::className();
			}
		}

		if ($nonLocalizedElementTypes)
		{
			$elementIds = (new Query())
				->select('id')
				->from('{{%elements}}')
				->where(['in', 'type', $nonLocalizedElementTypes])
				->column();

			if ($elementIds)
			{
				// To be sure we don't hit any unique constraint MySQL errors, first make sure there are no rows for
				// these elements that don't currently use the old primary locale
				$deleteConditions = ['and', array('in', 'elementId', $elementIds), 'locale != :locale'];
				$deleteParams = [':locale' => $oldPrimaryLocaleId];

				$db = Craft::$app->getDb();

				$db->createCommand()->delete('{{%elements_i18n}}', $deleteConditions, $deleteParams)->execute();
				$db->createCommand()->delete('{{%content}}', $deleteConditions, $deleteParams)->execute();

				// Now convert the locales
				$updateColumns = ['locale' => $newPrimaryLocaleId];
				$updateConditions = ['in', 'elementId', $elementIds];

				$db->createCommand()->update('{{%elements_i18n}}', $updateColumns, $updateConditions)->execute();
				$db->createCommand()->update('{{%content}}', $updateColumns, $updateConditions)->execute();
			}
		}
	}
}
