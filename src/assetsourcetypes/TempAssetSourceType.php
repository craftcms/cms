<?php
namespace Craft;

/**
 * Temp source type class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.assetsourcetypes
 * @since     2.0
 */
class TempAssetSourceType extends LocalAssetSourceType
{
	////////////////////
	// CONSTANTS
	////////////////////

	const sourceName = "Temporary source";
	const sourceType = "Temp";

	////////////////////
	// PROPERTIES
	////////////////////

	/**
	 * @var bool
	 */
	protected $_isSourceLocal = true;

	////////////////////
	// PUBLIC METHODS
	////////////////////

	/**
	 * Returns the name of the source type.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t(static::sourceName);
	}

	/**
	 * Returns the component's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		return null;
	}

	/**
	 * Preps the settings before they're saved to the database.
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public function prepSettings($settings)
	{
		return $settings;
	}

	/**
	 * Starts an indexing session.
	 *
	 * @param $sessionId
	 *
	 * @throws Exception
	 * @return array
	 */
	public function startIndex($sessionId)
	{
		throw new Exception(Craft::t("This Source Type does not support indexing."));
	}

	/**
	 * Process an indexing session.
	 *
	 * @param $sessionId
	 * @param $offset
	 *
	 * @throws Exception
	 * @return mixed
	 */
	public function processIndex($sessionId, $offset)
	{
		throw new Exception(Craft::t("This Source Type does not support indexing."));
	}

	/**
	 * Cannot be selected. Ever.
	 *
	 * @return bool
	 */
	public function isSelectable()
	{
		return false;
	}
}
