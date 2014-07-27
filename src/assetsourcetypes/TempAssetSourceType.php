<?php
namespace Craft;

/**
 * Temp source type class.
 *
 * @package craft.app.assetsourcetypes
 */
class TempAssetSourceType extends LocalAssetSourceType
{
	protected $_isSourceLocal = true;

	const sourceName = "Temporary source";
	const sourceType = "Temp";

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
	 * @return array
	 * @throws Exception
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
	 * @return mixed
	 * @throws Exception
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
