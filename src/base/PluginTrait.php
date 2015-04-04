<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\base;

/**
 * PluginTrait implements the common methods and properties for plugin classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
trait PluginTrait
{
	// Properties
	// =========================================================================

	/**
	 * @var string The plugin’s display name
	 */
	public $name;

	/**
	 * @var string The plugin’s version number
	 */
	public $version;

	/**
	 * @var string The plugin developer’s name
	 */
	public $developer;

	/**
	 * @var string The plugin developer’s website URL
	 */
	public $developerUrl;

	/**
	 * @var string The language that the plugin’s messages were written in
	 */
	public $sourceLanguage = 'en-US';
}
