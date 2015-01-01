<?php
namespace craft\app\assetsourcetypes;

/**
 * A temporary asset source type class.
 *
 * @author     Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright  Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license    http://buildwithcraft.com/license Craft License Agreement
 * @see        http://buildwithcraft.com
 * @package    craft.app.assetsourcetypes
 * @since      2.0
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
