<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\assetsourcetypes;

use Craft;
use craft\app\errors\Exception;

/**
 * A temporary asset source type class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Temp extends Local
{
	// Constants
	// =========================================================================

	const sourceName = "Temporary source";
	const sourceType = "Temp";

	// Properties
	// =========================================================================

	/**
	 * @var bool
	 */
	protected $isSourceLocal = true;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc ComponentTypeInterface::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t(static::sourceName);
	}

	/**
	 * @inheritDoc SavableComponentTypeInterface::getSettingsHtml()
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		return null;
	}

	/**
	 * @inheritDoc SavableComponentTypeInterface::prepSettings()
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
	 * @inheritDoc BaseAssetSourceType::startIndex()
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
	 * @inheritDoc BaseAssetSourceType::processIndex()
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
	 * @inheritDoc ComponentTypeInterface::isSelectable()
	 *
	 * @return bool
	 */
	public function isSelectable()
	{
		return false;
	}
}
