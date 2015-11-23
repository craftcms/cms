<?php
namespace Craft;

/**
 * Globals functions.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.variables
 * @since     1.0
 */
class GlobalsVariable
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns all global sets.
	 *
	 * @param string|null $indexBy
	 *
	 * @return array
	 */
	public function getAllSets($indexBy = null)
	{
		return craft()->globals->getAllSets($indexBy);
	}

	/**
	 * Returns all global sets that are editable by the current user.
	 *
	 * @param string|null $indexBy
	 * @param string|null $localeId
	 *
	 * @return array
	 */
	public function getEditableSets($indexBy = null, $localeId = null)
	{
		return craft()->globals->getEditableSets($indexBy, $localeId);
	}

	/**
	 * Returns the total number of global sets.
	 *
	 * @return int
	 */
	public function getTotalSets()
	{
		return craft()->globals->getTotalSets();
	}

	/**
	 * Returns the total number of global sets that are editable by the current user.
	 *
	 * @return int
	 */
	public function getTotalEditableSets()
	{
		return craft()->globals->getTotalEditableSets();
	}

	/**
	 * Returns a global set by its ID.
	 *
	 * @param int         $globalSetId
	 * @param string|null $localeId
	 *
	 * @return GlobalSetModel|null
	 */
	public function getSetById($globalSetId, $localeId = null)
	{
		return craft()->globals->getSetById($globalSetId, $localeId);
	}

	/**
	 * Returns a global set by its handle.
	 *
	 * @param string      $globalSetHandle
	 * @param string|null $localeId
	 *
	 * @return GlobalSetModel|null
	 */
	public function getSetByHandle($globalSetHandle, $localeId = null)
	{
		return craft()->globals->getSetByHandle($globalSetHandle, $localeId);
	}
}
