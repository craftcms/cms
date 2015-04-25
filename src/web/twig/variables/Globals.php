<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web\twig\variables;

use craft\app\elements\GlobalSet;

/**
 * Globals functions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Globals
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
		return \Craft::$app->getGlobals()->getAllSets($indexBy);
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
		return \Craft::$app->getGlobals()->getEditableSets($indexBy, $localeId);
	}

	/**
	 * Returns the total number of global sets.
	 *
	 * @return int
	 */
	public function getTotalSets()
	{
		return \Craft::$app->getGlobals()->getTotalSets();
	}

	/**
	 * Returns the total number of global sets that are editable by the current user.
	 *
	 * @return int
	 */
	public function getTotalEditableSets()
	{
		return \Craft::$app->getGlobals()->getTotalEditableSets();
	}

	/**
	 * Returns a global set by its ID.
	 *
	 * @param int         $globalSetId
	 * @param string|null $localeId
	 *
	 * @return GlobalSet|null
	 */
	public function getSetById($globalSetId, $localeId = null)
	{
		return \Craft::$app->getGlobals()->getSetById($globalSetId, $localeId);
	}

	/**
	 * Returns a global set by its handle.
	 *
	 * @param string      $globalSetHandle
	 * @param string|null $localeId
	 *
	 * @return GlobalSet|null
	 */
	public function getSetByHandle($globalSetHandle, $localeId = null)
	{
		return \Craft::$app->getGlobals()->getSetByHandle($globalSetHandle, $localeId);
	}
}
