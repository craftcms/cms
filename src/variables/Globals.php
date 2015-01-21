<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\variables;

use craft\app\models\GlobalSet as GlobalSetModel;

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
		return \Craft::$app->globals->getAllSets($indexBy);
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
		return \Craft::$app->globals->getEditableSets($indexBy, $localeId);
	}

	/**
	 * Returns the total number of global sets.
	 *
	 * @return int
	 */
	public function getTotalSets()
	{
		return \Craft::$app->globals->getTotalSets();
	}

	/**
	 * Returns the total number of global sets that are editable by the current user.
	 *
	 * @return int
	 */
	public function getTotalEditableSets()
	{
		return \Craft::$app->globals->getTotalEditableSets();
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
		return \Craft::$app->globals->getSetById($globalSetId, $localeId);
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
		return \Craft::$app->globals->getSetByHandle($globalSetHandle, $localeId);
	}
}
